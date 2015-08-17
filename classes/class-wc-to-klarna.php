<?php
/**
 * Formats Klarna order data for WC order
 *
 * @link  http://www.woothemes.com/products/klarna/
 * @since 2.0.0
 *
 * @package WC_Gateway_Klarna
 */


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
	 * Constructor
	 *
	 * @since 2.0.0
	 */
	public function __construct( $is_rest = false ) {
		global $woocommerce;
		$this->cart = $woocommerce->cart->get_cart();
		$this->is_rest = $is_rest;
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
				$_product = wc_get_product( $cart_item['product_id'] );

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
						'reference'      => $item_reference,
						'name'           => $item_name,
						'quantity'       => $item_quantity,
						'unit_price'     => $item_price,
						'tax_rate'       => $item_tax_rate,
						'discount_rate'  => $item_discount_rate
					);					
				}

				$cart[] = $klarna_item;
				$order_total += $item_quantity * $item_price;
			}
		}

		// Process shipping
		if ( $woocommerce->cart->shipping_total > 0 ) {
			$shipping_name       = $this->get_shipping_name();
			$shipping_amount     = $this->get_shipping_amount();
			$shipping_tax_rate   = $this->get_shipping_tax_rate();
			$shipping_tax_amount = $this->get_shipping_tax_amount();
	
			if ( $this->is_rest ) {
				$shipping = array(  
					'type'             => 'shipping_fee',
					'reference'        => 'SHIPPING',
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
					'reference'  => 'SHIPPING',
					'name'       => $shipping_name,
					'quantity'   => 1,
					'unit_price' => $shipping_amount,
					'tax_rate'   => $shipping_tax_rate
				);
			}
			$cart[] = $shipping;
			$order_total += $shipping_amount;
		}

		// Process discounts
		if ( WC()->cart->applied_coupons ) {
			foreach ( WC()->cart->applied_coupons as $code ) {
				$smart_coupon = new WC_Coupon( $code );

				if ( $smart_coupon->is_valid() && $smart_coupon->discount_type == 'smart_coupon' ) {
					$coupon_name     = $this->get_coupon_name( $smart_coupon );
					$coupon_amount   = $this->get_coupon_amount( $smart_coupon );

					// Check if coupon amount exceeds order total
					if ( $order_total < $coupon_amount ) {
						$coupon_amount = $order_total;
					}

					if ( $this->is_rest ) {
						$cart[] = array(
							'type'             => 'discount',
							'reference'        => 'DISCOUNT',
							'name'             => $coupon_name,
							'quantity'         => 1,
							'unit_price'       => -$coupon_amount,
							'total_amount'     => -$coupon_amount,
							'tax_rate'         => 0,
							'total_tax_amount' => 0,
						);
						$order_total = $order_total - $coupon_amount;
					} else {
						$cart[] = array(
							'type'       => 'discount',
							'reference'  => 'DISCOUNT',
							'name'       => $coupon_name,
							'quantity'   => 1,
							'unit_price' => $coupon_amount,
							'tax_rate'   => 0,
						);
						$order_total = $order_total - $coupon_amount;
					}
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
	 * @param  array   $cart_item       Cart item.
	 * @return integer $item_tax_amount Item tax amount.
	 */
	public function get_item_tax_amount( $cart_item ) {
		$item_tax_amount = $cart_item['line_tax'] * 100;

		return round( $item_tax_amount );
	}

	/**
	 * Calculate item tax percentage.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  array   $cart_item     Cart item.
	 * @param  object  $_product      Product object.
	 * @return integer $item_tax_rate Item tax percentage formatted for Klarna.
	 */
	public function get_item_tax_rate( $cart_item, $_product ) {
		// We manually calculate the tax percentage here
		if ( $_product->is_taxable() && $cart_item['line_subtotal_tax'] > 0 ) {
			// Calculate tax rate
			$item_tax_rate = round( $cart_item['line_subtotal_tax'] / $cart_item['line_subtotal'], 2 ) * 100;
		} else {
			$item_tax_rate = 00;
		}

		return intval( $item_tax_rate . '00' );
	}

	/**
	 * Get cart item name.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  array  $cart_item Cart item.
	 * @return string $item_name Cart item name.
	 */
	public function get_item_name( $cart_item ) {
		$cart_item_data = $cart_item['data'];
		$item_name = $cart_item_data->post->post_title;

		// Append item meta to the title, if it exists
		if ( isset( $cart_item['item_meta'] ) ) {
			$item_meta = new WC_Order_Item_Meta( $cart_item['item_meta'] );
			if ( $meta = $item_meta->display( true, true ) ) {
				$item_name .= ' (' . $meta . ')';
			}
		}

		return strip_tags( $item_name );
	}

	/**
	 * Get cart item price.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  array   $cart_item  Cart item.
	 * @return integer $item_price Cart item price.
	 */
	public function get_item_price( $cart_item ) {
		// apply_filters to item price so we can filter this if needed
		$item_price_including_tax = $cart_item['line_subtotal'] + $cart_item['line_subtotal_tax'];
		$item_price = apply_filters( 'klarna_item_price_including_tax', $item_price_including_tax );
		$item_price = number_format( $item_price * 100, 0, '', '' ) / $cart_item['quantity'];
		// $item_price = $item_price * 100 / $cart_item['quantity'];

		return $item_price;
	}

	/**
	 * Get cart item quantity.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  array   $cart_item     Cart item.
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
	 * @param  object $product        Product object.
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
	 * @param  array   $cart_item            Cart item.
	 * @return integer $item_discount_amount Cart item discount.
	 */
	public function get_item_discount_amount( $cart_item ) {
		if ( $cart_item['line_subtotal'] > $cart_item['line_total'] ) {
			$item_price = $this->get_item_price( $cart_item );
			$item_total_amount = $this->get_item_total_amount( $cart_item );
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
	 * @param  array   $cart_item          Cart item.
	 * @return integer $item_discount_rate Cart item discount rate.
	 */
	public function get_item_discount_rate( $cart_item ) {
		if ( $cart_item['line_subtotal'] > $cart_item['line_total'] ) {
			$item_discount_rate = round( 1 - ( $cart_item['line_total'] / $cart_item['line_subtotal'] ), 2 ) * 10000;
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
	 * @param  array   $cart_item         Cart item.
	 * @return integer $item_total_amount Cart item total amount.
	 */
	public function get_item_total_amount( $cart_item ) {
		$item_total_amount = ( ( $cart_item['line_total'] + $cart_item['line_tax'] ) * 100 );

		return round( $item_total_amount );
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
			$shipping_name = __( 'Shipping', 'klarna' );
		}

		return $shipping_name;
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

		$shipping_amount = (int) number_format( ( $woocommerce->cart->shipping_total + $woocommerce->cart->shipping_tax_total ) * 100, 0, '', '' );

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

		if ( $woocommerce->cart->shipping_tax_total > 0 ) {
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

		$shipping_tax_amount = $woocommerce->cart->shipping_tax_total * 100;

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
	public function get_coupon_name( $smart_coupon ) {
		$coupon_name = $smart_coupon->code;

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
	public function get_coupon_amount( $smart_coupon ) {
		$coupon_amount = (int) number_format( ( $smart_coupon->coupon_amount ) * 100, 0, '', '' );

		return (int) $coupon_amount;
	}

	/**
	 * Get coupon method tax rate.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @return integer $coupon_tax_rate Tax rate for selected coupon method.
	 */
	public function get_coupon_tax_rate( $smart_coupon ) {
		global $woocommerce;

		if ( $woocommerce->cart->coupon_tax_total > 0 ) {
			$coupon_tax_rate = round( $woocommerce->cart->coupon_tax_total / $woocommerce->cart->coupon_total, 2 ) * 100;
		} else {
			$coupon_tax_rate = 00;
		}

		return intval( $coupon_tax_rate . '00' );
	}

}