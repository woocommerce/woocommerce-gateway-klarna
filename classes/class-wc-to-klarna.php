<?php
/**
 * Formats Klarna order data for creating/updating WC orders
 *
 * @link  http://www.woothemes.com/products/klarna/
 * @since 2.0.0
 *
 * @package WC_Gateway_Klarna
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * This class grabs WC cart contents and formats them so they can
 * be sent to Klarna when a KCO order is being created or updated.
 *
 * Needs Klarna order object passed as parameter
 * Checks if cart is empty
 * Checks if Rest API is in use
 * Process cart contents
 * - Rest and V2
 * Process shipping
 * - Rest and V2
 * Returns array formatted for Klarna
 *
 */
class WC_Gateway_Klarna_WC2K {

	/**
	 * WooCommerce cart contents.
	 *
	 * @since  2.0.0
	 * @access public
	 * @var    array
	 */
	public $cart;

	/**
	 * Is this for Rest API.
	 *
	 * @since  2.0.0
	 * @access public
	 * @var    boolean
	 */
	public $is_rest;

	/**
	 * Check which Klarna country is in use.
	 *
	 * @since  2.0.0
	 * @access public
	 * @var    boolean
	 */
	public $klarna_country;

	/**
	 * Constructor
	 *
	 * @since 2.0.0
	 *
	 * @param bool $is_rest
	 * @param string $klarna_country
	 */
	public function __construct( $is_rest = false, $klarna_country = '' ) {
		global $woocommerce;
		$this->cart           = $woocommerce->cart->get_cart();
		$this->is_rest        = $is_rest;
		$this->klarna_country = $klarna_country;
	}

	/**
	 * Check if cart is empty.
	 *
	 * Checks if WooCommerce cart is empty. If it is, there's no reason to proceed.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @return boolean
	 */
	public function is_cart_not_empty() {
		if ( sizeof( $this->cart ) > 0 ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Formats cart contents for Klarna.
	 *
	 * Checks if WooCommerce cart is empty. If it is, there's no reason to proceed.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @return array $cart_contents Formatted array ready for Klarna.
	 */
	public function process_cart_contents() {
		global $woocommerce;

		$woocommerce->cart->calculate_shipping();
		$woocommerce->cart->calculate_totals();

		$cart = array();

		// We need to keep track of order total, in case a smart coupon exceeds it
		$order_total = 0;

		foreach ( $woocommerce->cart->get_cart() as $cart_item ) {
			if ( $cart_item['quantity'] ) {
				if ( $cart_item['variation_id'] ) {
					$_product = wc_get_product( $cart_item['variation_id'] );
				} else {
					$_product = wc_get_product( $cart_item['product_id'] );
				}

				$item_name            = $this->get_item_name( $cart_item );
				$item_price           = $this->get_item_price( $cart_item );
				$item_quantity        = $this->get_item_quantity( $cart_item );
				$item_reference       = $this->get_item_reference( $_product );
				$item_discount_amount = $this->get_item_discount_amount( $cart_item );
				$item_discount_rate   = $this->get_item_discount_rate( $cart_item );
				$item_tax_amount      = $this->get_item_tax_amount( $cart_item );
				$item_tax_rate        = $this->get_item_tax_rate( $cart_item, $_product );
				$item_total_amount    = $this->get_item_total_amount( $cart_item );

				if ( $this->is_rest ) {
					$klarna_item = array(
						'reference'             => $item_reference,
						'name'                  => $item_name,
						'quantity'              => $item_quantity,
						'unit_price'            => $item_price,
						'tax_rate'              => $item_tax_rate,
						'total_amount'          => $item_total_amount,
						'total_tax_amount'      => $item_tax_amount,
						'total_discount_amount' => $item_discount_amount
					);
				} else {
					$klarna_item = array(
						'reference'     => $item_reference,
						'name'          => $item_name,
						'quantity'      => $item_quantity,
						'unit_price'    => $item_price,
						'tax_rate'      => $item_tax_rate,
						'discount_rate' => $item_discount_rate
					);
				}

				$cart[] = $klarna_item;
				$order_total += $item_quantity * $item_price;
			}
		}

		// Process fees
		if ( $woocommerce->cart->fee_total > 0 ) {
			foreach ( $woocommerce->cart->get_fees() as $cart_fee ) {
				$fee_name         = $this->get_fee_name( $cart_fee );
				$fee_reference    = $this->get_fee_reference( $cart_fee );
				$fee_type         = 'surcharge';
				$fee_quantity     = 1;
				$fee_unit_price   = $this->get_fee_amount( $cart_fee );
				$fee_total_amount = $this->get_fee_amount( $cart_fee );
				$fee_tax_rate     = $this->get_fee_tax_rate( $cart_fee );
				$fee_tax_amount   = $this->get_fee_tax_amount( $cart_fee );

				if ( $this->is_rest ) {
					$klarna_fee_item = array(
						'type'             => $fee_type,
						'reference'        => $fee_reference,
						'name'             => $fee_name,
						'quantity'         => $fee_quantity,
						'unit_price'       => $fee_unit_price,
						'total_amount'     => $fee_total_amount,
						'tax_rate'         => $fee_tax_rate,
						'total_tax_amount' => $fee_tax_amount
					);
				} else {
					$klarna_fee_item = array(
						'reference'  => $fee_reference,
						'name'       => $fee_name,
						'quantity'   => $fee_quantity,
						'unit_price' => $fee_unit_price,
						'tax_rate'   => $fee_tax_rate
					);
				}

				$cart[] = $klarna_fee_item;
				$order_total += (int) $cart_fee->amount * 100;
			}
		}

		// Process shipping
		if ( $woocommerce->shipping->get_packages() && $woocommerce->session->get( 'chosen_shipping_methods' ) ) {
			$shipping_name       = $this->get_shipping_name();
			$shipping_reference  = $this->get_shipping_reference();
			$shipping_amount     = $this->get_shipping_amount();
			$shipping_tax_rate   = $this->get_shipping_tax_rate();
			$shipping_tax_amount = $this->get_shipping_tax_amount();

			if ( $this->is_rest ) {
				/*
				Temporarily return shipping to V3

				No need to do this any longer, shipping is sent to Klarna
				as shipping_options parameter
				*/
				$shipping = array(
					'type'             => 'shipping_fee',
					'reference'        => $shipping_reference,
					'name'             => $shipping_name,
					'quantity'         => 1,
					'unit_price'       => $shipping_amount,
					'tax_rate'         => $shipping_tax_rate,
					'total_amount'     => $shipping_amount,
					'total_tax_amount' => $shipping_tax_amount
				);
			} else {
				$shipping = array(
					'type'       => 'shipping_fee',
					'reference'  => $shipping_reference,
					'name'       => $shipping_name,
					'quantity'   => 1,
					'unit_price' => $shipping_amount,
					'tax_rate'   => $shipping_tax_rate
				);
			}
			$cart[] = $shipping;
			$order_total += $shipping_amount;
		}

		// Process sales tax for US
		if ( $this->is_rest && 'us' == $this->klarna_country ) {
			$sales_tax = round( ( $woocommerce->cart->tax_total + $woocommerce->cart->shipping_tax_total ) * 100 );

			// Add sales tax line item
			$cart[] = array(
				'type'                  => 'sales_tax',
				'reference'             => __( 'Sales Tax', 'woocommerce-gateway-klarna' ),
				'name'                  => __( 'Sales Tax', 'woocommerce-gateway-klarna' ),
				'quantity'              => 1,
				'unit_price'            => $sales_tax,
				'tax_rate'              => 0,
				'total_amount'          => $sales_tax,
				'total_discount_amount' => 0,
				'total_tax_amount'      => 0
			);

			$order_total += $sales_tax;
		}

		// Process discounts
		if ( WC()->cart->applied_coupons ) {
			foreach ( WC()->cart->applied_coupons as $code ) {
				$coupon = new WC_Coupon( $code );

				if ( ! $coupon->is_valid() ) {
					break;
				}

				$klarna_settings = get_option( 'woocommerce_klarna_checkout_settings' );
				if ( 'yes' != $klarna_settings['send_discounts_separately'] && $coupon->discount_type != 'smart_coupon' ) {
					break;
				}

				$coupon_name   = $this->get_coupon_name( $coupon );
				$coupon_amount = $this->get_coupon_amount( $coupon );

				// Check if coupon amount exceeds order total
				if ( $order_total < $coupon_amount ) {
					$coupon_amount = $order_total;
				}

				if ( $this->is_rest ) {
					$cart[]      = array(
						'type'             => 'discount',
						'reference'        => 'DISCOUNT',
						'name'             => $coupon_name,
						'quantity'         => 1,
						'unit_price'       => - $coupon_amount,
						'total_amount'     => - $coupon_amount,
						'tax_rate'         => 0,
						'total_tax_amount' => 0,
					);
					$order_total = $order_total - $coupon_amount;
				} else {
					$cart[]      = array(
						'type'       => 'discount',
						'reference'  => 'DISCOUNT',
						'name'       => $coupon_name,
						'quantity'   => 1,
						'unit_price' => - $coupon_amount,
						'tax_rate'   => 0,
					);
					$order_total = $order_total - $coupon_amount;
				}
			}
		}

		return $cart;
	}

	/**
	 * Calculate item tax percentage.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  array $cart_item Cart item.
	 *
	 * @return integer $item_tax_amount Item tax amount.
	 */
	public function get_item_tax_amount( $cart_item ) {
		if ( 'us' == $this->klarna_country ) {
			$item_tax_amount = 00;
		} else {
			$item_tax_amount = $cart_item['line_tax'] * 100;
		}

		return round( $item_tax_amount );
	}

	/**
	 * Calculate item tax percentage.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  array $cart_item Cart item.
	 * @param  object $_product Product object.
	 *
	 * @return integer $item_tax_rate Item tax percentage formatted for Klarna.
	 */
	public function get_item_tax_rate( $cart_item, $_product ) {
		// We manually calculate the tax percentage here
		if ( $_product->is_taxable() && $cart_item['line_subtotal_tax'] > 0 ) {
			// Calculate tax rate
			if ( 'us' == $this->klarna_country ) {
				$item_tax_rate = 00;
			} else {
				// $item_tax_rate = round( $cart_item['line_subtotal_tax'] / $cart_item['line_subtotal'], 2 ) * 100;
				$item_tax_rate = round( $cart_item['line_subtotal_tax'] / $cart_item['line_subtotal'] * 100 * 100 );
			}
		} else {
			$item_tax_rate = 00;
		}

		return intval( $item_tax_rate );
	}

	/**
	 * Get cart item name.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  array $cart_item Cart item.
	 *
	 * @return string $item_name Cart item name.
	 */
	public function get_item_name( $cart_item ) {
		$cart_item_data = $cart_item['data'];
		$item_name      = $cart_item_data->post->post_title;

		// Get variations as a string and remove line breaks
		$item_variations = rtrim( WC()->cart->get_item_data( $cart_item, true ) ); // Removes new line at the end
		$item_variations = str_replace( "\n", ', ', $item_variations ); // Replaces all other line breaks with commas

		// Add variations to name
		if ( '' != $item_variations ) {
			$item_name .= ' [' . $item_variations . ']';
		}

		return strip_tags( $item_name );
	}

	/**
	 * Get cart item price.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  array $cart_item Cart item.
	 *
	 * @return integer $item_price Cart item price.
	 */
	public function get_item_price( $cart_item ) {
		// apply_filters to item price so we can filter this if needed
		if ( 'us' == $this->klarna_country ) {
			$item_price_including_tax = $cart_item['line_subtotal'];
		} else {
			$item_price_including_tax = $cart_item['line_subtotal'] + $cart_item['line_subtotal_tax'];
		}
		$item_price = apply_filters( 'klarna_item_price_including_tax', $item_price_including_tax );
		$item_price = number_format( $item_price * 100, 0, '', '' ) / $cart_item['quantity'];

		// $item_price = $item_price * 100 / $cart_item['quantity'];

		return round( $item_price );
	}

	/**
	 * Get cart item quantity.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  array $cart_item Cart item.
	 *
	 * @return integer $item_quantity Cart item quantity.
	 */
	public function get_item_quantity( $cart_item ) {
		return (int) $cart_item['quantity'];
	}

	/**
	 * Get cart item reference.
	 *
	 * Returns SKU or product ID.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  object $product Product object.
	 *
	 * @return string $item_reference Cart item reference.
	 */
	public function get_item_reference( $_product ) {
		$item_reference = '';

		if ( $_product->get_sku() ) {
			$item_reference = $_product->get_sku();
		} elseif ( $_product->variation_id ) {
			$item_reference = $_product->variation_id;
		} else {
			$item_reference = $_product->id;
		}

		return strval( $item_reference );
	}

	/**
	 * Get cart item discount.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  array $cart_item Cart item.
	 *
	 * @return integer $item_discount_amount Cart item discount.
	 */
	public function get_item_discount_amount( $cart_item ) {
		if ( $cart_item['line_subtotal'] > $cart_item['line_total'] ) {
			$item_price           = $this->get_item_price( $cart_item );
			$item_total_amount    = $this->get_item_total_amount( $cart_item );
			$item_discount_amount = ( $item_price * $cart_item['quantity'] - $item_total_amount );
		} else {
			$item_discount_amount = 0;
		}

		return round( $item_discount_amount );
	}

	/**
	 * Get cart item discount rate.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  array $cart_item Cart item.
	 *
	 * @return integer $item_discount_rate Cart item discount rate.
	 */
	public function get_item_discount_rate( $cart_item ) {
		$klarna_settings = get_option( 'woocommerce_klarna_checkout_settings' );

		if ( 'yes' != $klarna_settings['send_discounts_separately'] && $cart_item['line_subtotal'] > $cart_item['line_total'] ) {
			$item_discount_rate = ( 1 - ( $cart_item['line_total'] / $cart_item['line_subtotal'] ) ) * 10000;
		} else {
			$item_discount_rate = 0;
		}

		return (int) $item_discount_rate;
	}

	/**
	 * Get cart item total amount.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  array $cart_item Cart item.
	 *
	 * @return integer $item_total_amount Cart item total amount.
	 */
	public function get_item_total_amount( $cart_item ) {
		if ( 'us' == $this->klarna_country ) {
			$item_total_amount = ( $cart_item['line_total'] * 100 );
		} else {
			$item_total_amount = ( ( $cart_item['line_total'] + $cart_item['line_tax'] ) * 100 );
		}

		return round( $item_total_amount );
	}

	/**
	 * Get cart fee name.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  array $cart_fee Cart fee.
	 *
	 * @return string $cart_fee_name Cart fee name.
	 */
	public function get_fee_name( $cart_fee ) {
		return strip_tags( $cart_fee->name );
	}

	/**
	 * Get cart fee reference.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  array $cart_fee Cart fee.
	 *
	 * @return string $cart_fee_reference Cart fee reference.
	 */
	public function get_fee_reference( $cart_fee ) {
		return strip_tags( $cart_fee->id );
	}

	/**
	 * Get cart fee amount.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  array $cart_fee Cart fee.
	 *
	 * @return int   $cart_fee_amount Cart fee name.
	 */
	public function get_fee_amount( $cart_fee ) {
		if ( 'us' == $this->klarna_country ) {
			$cart_fee_amount = (int) ( $cart_fee->amount * 100 );
		} else {
			$cart_fee_amount = (int) ( ( $cart_fee->amount + $cart_fee->tax ) * 100 );
		}

		return $cart_fee_amount;
	}

	/**
	 * Get cart fee tax amount.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  array $cart_fee Cart fee.
	 *
	 * @return int   $cart_fee_tax_amount Cart fee tax amount.
	 */
	public function get_fee_tax_amount( $cart_fee ) {
		if ( $cart_fee->taxable && 'us' != $this->klarna_country ) {
			$cart_fee_tax_amount = (int) ( $cart_fee->tax * 100 );
		} else {
			$cart_fee_tax_amount = 0;
		}

		return $cart_fee_tax_amount;
	}

	/**
	 * Get cart fee tax rate.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  array $cart_fee Cart fee.
	 *
	 * @return int   $cart_fee_tax_rate Cart fee tax rate.
	 */
	public function get_fee_tax_rate( $cart_fee ) {
		if ( $cart_fee->taxable && 'us' != $this->klarna_country ) {
			$cart_fee_tax_rate = $cart_fee->tax / $cart_fee->amount * 100 * 100;
		} else {
			$cart_fee_tax_rate = 0;
		}

		$cart_fee_tax_rate = (int) $cart_fee_tax_rate;

		return $cart_fee_tax_rate;
	}

	/**
	 * Get shipping method name.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @return string $shipping_name Name for selected shipping method.
	 */
	public function get_shipping_name() {
		global $woocommerce;

		$shipping_packages = $woocommerce->shipping->get_packages();
		foreach ( $shipping_packages as $i => $package ) {
			$chosen_method = isset( $woocommerce->session->chosen_shipping_methods[ $i ] ) ? $woocommerce->session->chosen_shipping_methods[ $i ] : '';

			if ( '' != $chosen_method ) {
				$package_rates = $package['rates'];
				foreach ( $package_rates as $rate_key => $rate_value ) {
					if ( $rate_key == $chosen_method ) {
						$shipping_name = $rate_value->label;
					}
				}
			}
		}

		if ( ! isset( $shipping_name ) ) {
			$shipping_name = __( 'Shipping', 'woocommerce-gateway-klarna' );
		}

		return $shipping_name;
	}

	/**
	 * Get shipping reference.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @return string $shipping_reference Reference for selected shipping method.
	 */
	public function get_shipping_reference() {
		global $woocommerce;

		$shipping_packages = $woocommerce->shipping->get_packages();
		foreach ( $shipping_packages as $i => $package ) {
			$chosen_method = isset( $woocommerce->session->chosen_shipping_methods[ $i ] ) ? $woocommerce->session->chosen_shipping_methods[ $i ] : '';

			if ( '' != $chosen_method ) {
				$package_rates = $package['rates'];
				foreach ( $package_rates as $rate_key => $rate_value ) {
					if ( $rate_key == $chosen_method ) {
						$shipping_reference = $rate_value->id;
					}
				}
			}
		}

		if ( ! isset( $shipping_reference ) ) {
			$shipping_reference = __( 'Shipping', 'woocommerce-gateway-klarna' );
		}

		return strval( $shipping_reference );
	}

	/**
	 * Get shipping method amount.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @return integer $shipping_amount Amount for selected shipping method.
	 */
	public function get_shipping_amount() {
		global $woocommerce;

		if ( 'us' == $this->klarna_country ) {
			$shipping_amount = (int) number_format( $woocommerce->cart->shipping_total * 100, 0, '', '' );
		} else {
			$shipping_amount = (int) number_format( ( $woocommerce->cart->shipping_total + $woocommerce->cart->shipping_tax_total ) * 100, 0, '', '' );
		}

		return (int) $shipping_amount;
	}

	/**
	 * Get shipping method tax rate.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @return integer $shipping_tax_rate Tax rate for selected shipping method.
	 */
	public function get_shipping_tax_rate() {
		global $woocommerce;

		if ( $woocommerce->cart->shipping_tax_total > 0 && 'us' != $this->klarna_country ) {
			$shipping_tax_rate = round( $woocommerce->cart->shipping_tax_total / $woocommerce->cart->shipping_total, 2 ) * 100;
		} else {
			$shipping_tax_rate = 00;
		}

		return intval( $shipping_tax_rate . '00' );
	}

	/**
	 * Get shipping method tax amount.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @return integer $shipping_tax_amount Tax amount for selected shipping method.
	 */
	public function get_shipping_tax_amount() {
		global $woocommerce;

		if ( 'us' == $this->klarna_country ) {
			$shipping_tax_amount = 0;
		} else {
			$shipping_tax_amount = $woocommerce->cart->shipping_tax_total * 100;
		}

		return (int) $shipping_tax_amount;
	}

	/**
	 * Get coupon method name.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @return string $coupon_name Name for selected coupon method.
	 */
	public function get_coupon_name( $coupon ) {
		$coupon_name = $coupon->code;

		return $coupon_name;
	}

	/**
	 * Get coupon method amount.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @return integer $coupon_amount Amount for selected coupon method.
	 */
	public function get_coupon_amount( $coupon ) {
		$coupon_amount = WC()->cart->get_coupon_discount_amount( $coupon->code, false );
		$coupon_amount = (int) number_format( ( $coupon_amount ) * 100, 0, '', '' );

		return $coupon_amount;
	}

}