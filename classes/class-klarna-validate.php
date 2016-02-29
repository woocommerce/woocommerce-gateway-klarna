<?php
/**
 * Validates Klarna order locally by checking stock
 *
 * @link  http://www.woothemes.com/products/klarna/
 * @since 1.0.0
 *
 * @package WC_Gateway_Klarna
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class that validates Klarna orders.
 */
class WC_Gateway_Klarna_Order_Validate {

	/**
	 * Validate Klarna order
	 * Checks order items' stock status.
	 *
	 * @since 1.0.0
	 */
	public static function validate_checkout_listener() {
		// Read the post body
		$post_body = file_get_contents( 'php://input' );

		// Convert post body into native object
		$data = json_decode( $post_body, true );

		$all_in_stock = true;
		if ( get_option( 'woocommerce_manage_stock' ) == 'yes' ) {
			$cart_items = $data['cart']['items'];
			foreach ( $cart_items as $cart_item ) {
				if ( 'physical' == $cart_item['type'] ) {
					$cart_item_product = new WC_Product( $cart_item['reference'] );

					if ( ! $cart_item_product->has_enough_stock( $cart_item['quantity'] ) ) {
						$all_in_stock = false;
					}
				}
			}
		}

		if ( $all_in_stock ) {
			header( 'HTTP/1.0 200 OK' );
		} else {
			header( 'HTTP/1.0 303 See Other' );
			header( 'Location: ' . WC()->cart->get_cart_url() );
		}
	} // End function validate_checkout_listener

}

$wc_gateway_klarna_order_validate = new WC_Gateway_Klarna_Order_Validate();