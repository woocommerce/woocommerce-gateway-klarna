<?php
/**
 * WooCommerce Klarna Gateway uninstall
 *
 * Removes all Klarna options from DB when user deletes the plugin via WordPress backend.
 *
 * @link http://www.woothemes.com/products/klarna/
 * @since 0.3
 *
 * @package WC_Gateway_Klarna
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

delete_option( 'woocommerce_klarna_settings' );