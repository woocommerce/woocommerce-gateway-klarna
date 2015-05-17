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
	public function is_cart_empty() {
		if ( sizeof( $this->cart ) > 0 ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Check if Rest API is used.
	 * 
	 * @since  2.0.0
	 * @access public
	 *
	 * @return boolean
	 */
	public function is_rest() {
		if ( $this->is_rest() ) {
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
		$cart = array();

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
	
				if ( $this->is_rest() ) {
					$klarna_item = array(
						'reference'             => $item_reference,
						'name'                  => $item_name,
						'quantity'              => $item_quantity,
						'unit_price'            => $item_price,
						'tax_rate'              => $item_tax_rate,
						'total_amount'          => $item_total_amount,
						'total_tax_amount'      => $item_tax_amount,
						'total_discount_amount' => $item_discount
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
		$item_tax_amount = $cart_item['line_subtotal_tax'] * 100;

		return $item_tax_amount;
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
				$item_name .= ' ( ' . $meta . ' )';
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

		return (int) $item_price;
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
			$item_discount_amount = ( $item_price * $cart_item['quantity'] - $total_amount );
		} else {
			$item_discount_amount = 0;
		}

		return $item_discount_amount;
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

		return $item_discount_rate;
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
		$item_total_amount = (int) ( $cart_item['line_total'] + $cart_item['line_tax'] ) * 100;

		return $item_total_amount;
	}

}