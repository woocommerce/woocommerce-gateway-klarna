<?php
/**
 * WooCommerce Klarna Gateway
 *
 * @link http://www.woothemes.com/products/klarna/
 * @since 0.3
 *
 * @package WC_Gateway_Klarna
 *
 * @wordpress-plugin
 * Plugin Name:     WooCommerce Klarna Gateway
 * Plugin URI:      http://woothemes.com/woocommerce
 * Description:     Extends WooCommerce. Provides a <a href="http://www.klarna.se" target="_blank">Klarna</a> gateway for WooCommerce.
 * Version:         2.0.3
 * Author:          WooThemes
 * Author URI:      http://woothemes.com/
 * Developer:       Krokedil
 * Developer URI:   http://krokedil.com/
 * Text Domain:     woocommerce-gateway-klarna
 * Domain Path:     /languages
 * Copyright:       © 2009-2015 WooThemes.
 * License:         GNU General Public License v3.0
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Register activation hook
 */
function woocommerce_gateway_klarna_activate() {
	if ( version_compare( PHP_VERSION, '5.4', '<' ) ) {
		deactivate_plugins( basename( __FILE__ ) );
		wp_die( __( '<p><strong>WooCommerce Gateway Klarna</strong> plugin requires PHP version 5.4 or greater.</p>', 'woocommerce-gateway-klarna' ) );
	}
}
register_activation_hook( __FILE__, 'woocommerce_gateway_klarna_activate' );

/**
 * Show welcome notice
 */
function woocommerce_gateway_klarna_welcome_notice() {
	// Check if either one of three payment methods is configured
	if ( 
		false == get_option('woocommerce_klarna_invoice_settings') &&
		false == get_option('woocommerce_klarna_part_payment_settings') &&
		false == get_option( 'woocommerce_klarna_checkout_settings' )
	) {
		$html = '<div class="updated">';
			$html .= '<p>';
				$html .= __( 'Thank you for choosing Klarna as your payment provider. WooCommerce Klarna Gateway is almost ready. Please visit <a href="admin.php?page=wc-settings&tab=checkout&section=wc_gateway_klarna_checkout">Klarna Checkout</a>, <a href="admin.php?page=wc-settings&tab=checkout&section=wc_gateway_klarna_invoice">Klarna Invoice</a> or <a href="admin.php?page=wc-settings&tab=checkout&section=wc_gateway_klarna_part_payment">Klarna Part Payment</a> settings to enter your EID and shared secret for countries you have an agreement for with Klarna. ', 'woocommerce-gateway-klarna' );
			$html .= '</p>';
		$html .= '</div><!-- /.updated -->';

		echo $html;
	}
}
add_action( 'admin_notices', 'woocommerce_gateway_klarna_welcome_notice' );

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '4edd8b595d6d4b76f31b313ba4e4f3f6', '18624' );

/**
 * Check if update is from 1.x to 2.x
 *
 * Names for these two options for changed, for better naming standards, so option values
 * need to be copied from old options.
 */
function klarna_2_update() {
	// Invoice
	if ( false == get_option( 'woocommerce_klarna_invoice_settings' ) ) {
		if ( get_option( 'woocommerce_klarna_settings' ) ) {
			add_option(
				'woocommerce_klarna_invoice_settings',
				get_option( 'woocommerce_klarna_settings' )
			);
		}
	}

	// Part Payment
	if ( false == get_option( 'woocommerce_klarna_part_payment_settings' ) ) {
		if ( get_option( 'woocommerce_klarna_account_settings' ) ) {
			add_option(
				'woocommerce_klarna_part_payment_settings',
				get_option( 'woocommerce_klarna_account_settings' )
			);
		}
	}
}
add_action( 'plugins_loaded', 'klarna_2_update' );


/** Init Klarna Gateway after WooCommerce has loaded.
 *
 * Hooks into 'plugins_loaded'.
 */
function init_klarna_gateway() {

	// If the WooCommerce payment gateway class is not available, do nothing
	if ( ! class_exists( 'WC_Payment_Gateway' ) )
		return;

	// Localisation
	load_plugin_textdomain( 'woocommerce-gateway-klarna', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	/**
	 * Define plugin constants
	 */
	define( 'KLARNA_DIR', dirname(__FILE__) . '/' );         // Root dir
	define( 'KLARNA_LIB', dirname(__FILE__) . '/library/' ); // Klarna library dir
	define( 'KLARNA_URL', plugin_dir_url( __FILE__ ) );      // Plugin folder URL

	// Set CURLOPT_SSL_VERIFYPEER via constant in library/src/Klarna/Checkout/HTTP/CURLTransport.php.
	// No need to set it to true if the store doesn't use https.
	if ( is_ssl() ) {
		define( 'KLARNA_WC_SSL_VERIFYPEER', true );
	} else {
		define( 'KLARNA_WC_SSL_VERIFYPEER', false );
	}

	/**
	 * WooCommerce Klarna Gateway class
	 *
	 * @class   WC_Gateway_Klarna
	 * @package WC_Gateway_Klarna
	 */
	class WC_Gateway_Klarna extends WC_Payment_Gateway {

		public function __construct() {

			global $woocommerce;

			$this->shop_country = get_option( 'woocommerce_default_country' );

			// Check if woocommerce_default_country includes state as well. If it does, remove state
			if ( strstr( $this->shop_country, ':' ) ) :
				$this->shop_country = current( explode( ':', $this->shop_country ) );
			else :
				$this->shop_country = $this->shop_country;
			endif;

			// Get current customers selected language if this is a multi lanuage site
			$iso_code = explode( '_', get_locale() );
			$this->shop_language = strtoupper( $iso_code[0] ); // Country ISO code (SE)

			switch ( $this->shop_language ) {
				case 'NB' :
					$this->shop_language = 'NO';
					break;
				case 'SV' :
					$this->shop_language = 'SE';
					break;
			}

			// Currency
			$this->selected_currency = get_woocommerce_currency();

			// Apply filters to shop_country
			$this->shop_country = apply_filters( 'klarna_shop_country', $this->shop_country );

			// Actions
			add_action( 'wp_enqueue_scripts', array( $this, 'klarna_load_scripts') );

		}

	 	/**
	 	 * Register and enqueue Klarna scripts
	 	 */
		function klarna_load_scripts() {

			wp_enqueue_script( 'jquery' );

			if ( is_checkout() ) {
				wp_register_script(
					'klarna-base-js',
					'https://cdn.klarna.com/public/kitt/core/v1.0/js/klarna.min.js',
					array('jquery'),
					'1.0',
					false
				);
				wp_register_script(
					'klarna-terms-js',
					'https://cdn.klarna.com/public/kitt/toc/v1.1/js/klarna.terms.min.js',
					array('klarna-base-js'),
					'1.0',
					false
				);
				wp_enqueue_script( 'klarna-base-js' );
				wp_enqueue_script( 'klarna-terms-js' );
			}

		}

	} // End class WC_Gateway_Klarna


	require_once 'vendor/autoload.php';


	// Include the WooCommerce Compatibility Utility class
	// The purpose of this class is to provide a single point of compatibility functions for dealing with supporting multiple versions of WooCommerce (currently 2.0.x and 2.1)
	require_once 'classes/class-wc-klarna-compatibility.php';

	// Include our Klarna classes
	require_once 'classes/class-klarna-part-payment.php'; // KPM Part Payment
	require_once 'classes/class-klarna-invoice.php'; // KPM Invoice
	require_once 'classes/class-klarna-payment-method-widget.php'; // Partpayment widget
	require_once 'classes/class-klarna-get-address.php'; // Get address
	require_once 'classes/class-klarna-pms.php'; // PMS
	require_once 'classes/class-klarna-order.php'; // Handles Klarna orders
	// require_once 'classes/class-klarna-validate.php'; // Validates Klarna orders
	require_once 'classes/class-klarna-payment-method-display-widget.php'; // WordPress widget

	// register Foo_Widget widget
	function register_klarna_pmd_widget() {
		register_widget( 'WC_Klarna_Payment_Method_Display_Widget' );
	}
	add_action( 'widgets_init', 'register_klarna_pmd_widget' );

	// Klarna Checkout class
	$klarna_shop_country = get_option( 'woocommerce_default_country' );
	$klarna_shop_country = substr( $klarna_shop_country, 0, 2 ); // Get first two characters to remove state
	$available_countries = array( 'SE', 'NO', 'FI', 'DE', 'GB', 'AT', 'US' );
	if ( in_array( $klarna_shop_country, $available_countries ) ) {
		require_once 'classes/class-klarna-checkout.php';
		require_once 'classes/class-klarna-shortcodes.php';
	}

	// Send customer and merchant emails for KCO Incomplete > Processing status change

	// Add kco-incomplete_to_processing to statuses that can send email
	add_filter( 'woocommerce_email_actions', 'wc_klarna_kco_add_kco_incomplete_email_actions' );
	function wc_klarna_kco_add_kco_incomplete_email_actions( $email_actions ) {
		$email_actions[] = 'woocommerce_order_status_kco-incomplete_to_processing';
		return $email_actions;
	}

	// Triggers the email
	add_action( 'woocommerce_order_status_kco-incomplete_to_processing_notification', 'wc_klarna_kco_incomplete_trigger' );
	function wc_klarna_kco_incomplete_trigger( $orderid ) {
		$kco_mailer = WC()->mailer();
		$kco_mails = $kco_mailer->get_emails();
		foreach ( $kco_mails as $kco_mail ) {
			$order =  new WC_Order( $orderid );
			if ( 'new_order' == $kco_mail->id || 'customer_processing_order' == $kco_mail->id ) {
				$kco_mail->trigger( $order->id );
			}
		}
	}

}
add_action( 'plugins_loaded', 'init_klarna_gateway', 2 );

/**
 * Add payment gateways to WooCommerce.
 *
 * @param  array $methods
 * @return array $methods
 */
function add_klarna_gateway( $methods ) {
	$klarna_shop_country = get_option( 'woocommerce_default_country' );
	$klarna_shop_country = substr( $klarna_shop_country, 0, 2 ); // Cut off first two characters to remove state

	$available_countries = array( 'SE', 'NO', 'FI', 'DK', 'DE', 'NL', 'AT' );
	if ( in_array( $klarna_shop_country, $available_countries ) ) {
		$methods[] = 'WC_Gateway_Klarna_Part_Payment';
		$methods[] = 'WC_Gateway_Klarna_Invoice';
	}

	// Only add the Klarna Checkout method if Sweden, Norway, Finland, Germany, Austria, UK or US is set as the base country
	$available_countries = array( 'SE', 'NO', 'FI', 'DE', 'GB', 'AT', 'US' );
	if ( in_array( $klarna_shop_country, $available_countries ) ) {
		$methods[] = 'WC_Gateway_Klarna_Checkout';
	}

	return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'add_klarna_gateway' );
