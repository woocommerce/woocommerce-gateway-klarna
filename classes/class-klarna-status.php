<?php
/**
 * WooCommerce status page extension
 *
 * @link http://www.woothemes.com/products/klarna/
 * @since 1.0.0
 *
 * @package WC_Gateway_Klarna
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Gateway_Klarna_Status {

	public function __construct() {
		add_action( 'woocommerce_system_status_report', array( $this, 'add_status_page_box' ) );
	}

	public function add_status_page_box() {
		include_once( KLARNA_DIR . 'views/admin/status.php' );
	}

}
$wc_gateway_klarna_status = new WC_Gateway_Klarna_Status();