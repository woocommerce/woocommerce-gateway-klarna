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
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Validation listener
		add_action( 'woocommerce_api_wc_gateway_klarna_order_validate', array( $this, 'validate_checkout_listener' ) );
	}


	/**
	 * Validate Klarna order
	 * Checks order items' stock status.
	 *
	 * @since 1.0.0
	 */
	function validate_checkout_listener() {
		error_log('test');
		// header( 'HTTP/1.0 303 See Other' );
		// header( 'Location: http://www.example.com/' );
	} // End function validate_checkout_listener

}

$wc_gateway_klarna_order_validate = new WC_Gateway_Klarna_Order_Validate();