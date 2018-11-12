<?php
/**
 * WooCommerce Klarna Gateway
 *
 * @link https://krokedil.com/klarna-for-woocommerce-v2/
 * @since 0.3
 *
 * @package WC_Gateway_Klarna
 *
 * @wordpress-plugin
 * Plugin Name:     WooCommerce Klarna Gateway
 * Plugin URI:      https://krokedil.com/klarna-for-woocommerce-v2/
 * Description:     Extends WooCommerce. Provides a <a href="https://www.klarna.com/" target="_blank">Klarna</a> gateway for WooCommerce.
 * Version:         2.6.2
 * Author:          Krokedil
 * Author URI:      https://woocommerce.com/
 * Developer:       Krokedil
 * Developer URI:   http://krokedil.com/
 * Text Domain:     woocommerce-gateway-klarna
 * Domain Path:     /languages
 * WC requires at least: 3.0.
 * WC tested up to: 3.5.1
 * Copyright:       © 2010-2018 Krokedil.
 * License:         GNU General Public License v3.0
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'WC_KLARNA_VER' ) ) {
	define( 'WC_KLARNA_VER', '2.6.2' );
}

/**
 * Plugin updates
 */
require 'includes/plugin_update_check.php';
$MyUpdateChecker = new PluginUpdateChecker_2_0(
	'https://kernl.us/api/v1/updates/5bb4bcffcbec8e10be4760f7/',
	__FILE__,
	'woocommerce-gateway-klarna',
	1
);

/**
 * Show welcome notice
 */
function woocommerce_gateway_klarna_welcome_notice() {
	// Check if either one of three payment methods is configured.
	if ( false === get_option( 'woocommerce_klarna_invoice_settings' ) && false === get_option( 'woocommerce_klarna_part_payment_settings' ) && false === get_option( 'woocommerce_klarna_checkout_settings' ) ) {
		$html  = '<div class="updated">';
		$html .= '<p>';
		$html .= __( 'Thank you for choosing Klarna as your payment provider. WooCommerce Klarna Gateway is almost ready. Please visit <a href="admin.php?page=wc-settings&tab=checkout&section=wc_gateway_klarna_checkout">Klarna Checkout</a>, <a href="admin.php?page=wc-settings&tab=checkout&section=wc_gateway_klarna_invoice">Klarna Invoice</a> or <a href="admin.php?page=wc-settings&tab=checkout&section=wc_gateway_klarna_part_payment">Klarna Part Payment</a> settings to enter your EID and shared secret for countries you have an agreement for with Klarna. ', 'woocommerce-gateway-klarna' );
		$html .= '</p>';
		$html .= '</div>';

		echo $html;
	}

	if ( version_compare( PHP_VERSION, '5.4', '<' ) ) {
		$html  = '<div class="error">';
		$html .= __( '<p><strong>WooCommerce Gateway Klarna</strong> plugin requires PHP version 5.4 or greater.</p>', 'woocommerce-gateway-klarna' );
		$html .= '</div>';

		echo $html;
	}
}
add_action( 'admin_notices', 'woocommerce_gateway_klarna_welcome_notice' );


/**
 * Check if update is from 1.x to 2.x
 *
 * Names for these two options for changed, for better naming standards, so option values
 * need to be copied from old options.
 */
function klarna_2_update() {
	// Invoice.
	if ( false === get_option( 'woocommerce_klarna_invoice_settings' ) ) {
		if ( get_option( 'woocommerce_klarna_settings' ) ) {
			add_option( 'woocommerce_klarna_invoice_settings', get_option( 'woocommerce_klarna_settings' ) );
		}
	}

	// Part Payment.
	if ( false === get_option( 'woocommerce_klarna_part_payment_settings' ) ) {
		if ( get_option( 'woocommerce_klarna_account_settings' ) ) {
			add_option( 'woocommerce_klarna_part_payment_settings', get_option( 'woocommerce_klarna_account_settings' ) );
		}
	}
}
add_action( 'plugins_loaded', 'klarna_2_update' );


/**
 * Init Klarna Gateway after WooCommerce has loaded.
 *
 * Hooks into 'plugins_loaded'.
 */
function init_klarna_gateway() {
	// Do nothing if PHP version is lower than 5.4.
	if ( version_compare( PHP_VERSION, '5.4', '<' ) ) {
		return;
	}

	// If the WooCommerce payment gateway class is not available, do nothing.
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	// Localisation.
	load_plugin_textdomain( 'woocommerce-gateway-klarna', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	/**
	 * Define plugin constants
	 */
	define( 'KLARNA_DIR', dirname( __FILE__ ) . '/' );         // Root dir.
	define( 'KLARNA_LIB', dirname( __FILE__ ) . '/library/' ); // Klarna library dir.
	define( 'KLARNA_URL', plugin_dir_url( __FILE__ ) );         // Plugin folder URL.
	define( 'KLARNA_MAIN_FILE', __FILE__ );
	define( 'KROKEDIL_LOGGER_GATEWAY', 'klarna_' );
	define( 'KROKEDIL_LOGGER_ON', true );

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

		public static $log = '';

		/**
		 * WC_Gateway_Klarna constructor.
		 */
		public function __construct() {

			global $woocommerce;

			$this->shop_country = get_option( 'woocommerce_default_country' );

			// Check if woocommerce_default_country includes state as well. If it does, remove state.
			if ( strstr( $this->shop_country, ':' ) ) {
				$this->shop_country = current( explode( ':', $this->shop_country ) );
			}

			// Get current customers selected language if this is a multi lanuage site.
			$iso_code            = explode( '_', get_locale() );
			$this->shop_language = strtoupper( $iso_code[0] ); // Country ISO code (SE).

			switch ( $this->shop_language ) {
				case 'NB':
					$this->shop_language = 'NO';
					break;
				case 'SV':
					$this->shop_language = 'SE';
					break;
			}

			// Currency.
			$this->selected_currency = get_woocommerce_currency();

			// Apply filters to shop_country.
			$this->shop_country = apply_filters( 'klarna_shop_country', $this->shop_country );

			// Actions.
			add_action( 'wp_enqueue_scripts', array( $this, 'klarna_load_scripts' ) );
		}

		public static function log( $message ) {
			if ( empty( self::$log ) ) {
				self::$log = new WC_Logger();
			}
			self::$log->add( 'klarna', $message );
		}

		/**
		 * Register and enqueue Klarna scripts.
		 */
		function klarna_load_scripts() {
			wp_enqueue_script( 'jquery' );

			if ( is_checkout() || defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
				wp_register_script( 'klarna-base-js', 'https://cdn.klarna.com/public/kitt/core/v1.0/js/klarna.min.js', array( 'jquery' ), '1.0', false );
				wp_register_script( 'klarna-terms-js', 'https://cdn.klarna.com/public/kitt/toc/v1.1/js/klarna.terms.min.js', array( 'klarna-base-js' ), '1.0', false );
				wp_enqueue_script( 'klarna-base-js' );
				wp_enqueue_script( 'klarna-terms-js' );
			}
		}

	}

	// Composer autoloader.
	require_once __DIR__ . '/vendor/autoload.php';

	// Include our Klarna classes.
	require_once 'classes/class-klarna-part-payment.php';                  // KPM Part Payment.
	require_once 'classes/class-klarna-invoice.php';                       // KPM Invoice.
	require_once 'classes/class-klarna-process-checkout-kpm.php';          // KPM process checkout fields.
	require_once 'classes/class-klarna-payment-method-widget.php';         // Partpayment widget.
	require_once 'classes/class-klarna-get-address.php';                   // Get address.
	require_once 'classes/class-klarna-pms.php';                           // PMS.
	require_once 'classes/class-klarna-order.php';                         // Handles Klarna orders.
	require_once 'classes/class-klarna-payment-method-display-widget.php'; // WordPress widget.
	require_once 'classes/class-klarna-status.php';                        // WooCommerce status page extension.
	require_once 'classes/class-klarna-cross-sells.php';                   // Klarna Checkout cross-sells.
	require_once 'includes/klarna-wc-30-compatibility-functions.php';      // WooCommerce 3.0 compatibility methods.
	require_once 'classes/class-klarna-gdpr.php';                          // WooCommerce 3.0 compatibility methods.
	require_once 'classes/class-klarna-banners.php';                          // WooCommerce 3.0 compatibility methods.

	/**
	 * Register Klarna Payment Method Display widget.
	 */
	function register_klarna_pmd_widget() {
		register_widget( 'WC_Klarna_Payment_Method_Display_Widget' );
	}

	add_action( 'widgets_init', 'register_klarna_pmd_widget' );

	// Klarna Checkout classes.
	require_once 'classes/class-klarna-checkout-variables.php';
	require_once 'classes/class-klarna-checkout-ajax.php';
	require_once 'classes/class-klarna-checkout.php';
	require_once 'classes/class-klarna-shortcodes.php';
	require_once 'classes/class-klarna-validate.php';
	require_once 'includes/klarna-template-hooks.php';

	/**
	 * Add kco-incomplete_to_processing to statuses that can send email.
	 *
	 * @param array $email_actions Email actions.
	 *
	 * @return array
	 */
	function wc_klarna_kco_add_kco_incomplete_email_actions( $email_actions ) {
		$email_actions[] = 'woocommerce_order_status_kco-incomplete_to_processing';

		return $email_actions;
	}
	add_filter( 'woocommerce_email_actions', 'wc_klarna_kco_add_kco_incomplete_email_actions' );

	/**
	 * Triggers the email.
	 *
	 * @param integer $orderid WC_Order ID.
	 */
	function wc_klarna_kco_incomplete_trigger( $orderid ) {
		$kco_mailer = WC()->mailer();
		$kco_mails  = $kco_mailer->get_emails();
		foreach ( $kco_mails as $kco_mail ) {
			$order = wc_get_order( $orderid );
			if ( 'new_order' === $kco_mail->id || 'customer_processing_order' === $kco_mail->id ) {
				$kco_mail->trigger( $orderid );
			}
		}
	}
	add_action( 'woocommerce_order_status_kco-incomplete_to_processing_notification', 'wc_klarna_kco_incomplete_trigger' );

}
add_action( 'plugins_loaded', 'init_klarna_gateway', 2 );

/**
 * Add payment gateways to WooCommerce.
 *
 * @param  array $methods Available gateways.
 *
 * @return array $methods
 */
function add_klarna_gateway( $methods ) {
	// Do nothing if PHP version is lower than 5.4.
	if ( version_compare( PHP_VERSION, '5.4', '<' ) ) {
		return $methods;
	}

	$methods[] = 'WC_Gateway_Klarna_Part_Payment';
	$methods[] = 'WC_Gateway_Klarna_Invoice';
	$methods[] = 'WC_Gateway_Klarna_Checkout';

	return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'add_klarna_gateway' );

add_action( 'init', 'klarna_register_klarna_incomplete_order_status' );
/**
 * Register KCO Incomplete order status
 *
 * @since  2.0
 **/
function klarna_register_klarna_incomplete_order_status() {
	$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
	$show_status       = 'yes' === $checkout_settings['debug'];

	register_post_status(
		'wc-kco-incomplete', array(
			'label'                     => 'KCO incomplete',
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => $show_status,
			'label_count'               => _n_noop( 'KCO incomplete <span class="count">(%s)</span>', 'KCO incomplete <span class="count">(%s)</span>' ),
		)
	);
}

add_filter( 'wc_order_statuses', 'klarna_add_kco_incomplete_to_order_statuses' );
/**
 * Add KCO Incomplete to list of order status
 *
 * @since  2.0
 **/
function klarna_add_kco_incomplete_to_order_statuses( $order_statuses ) {
	// Add this status only if not in account page (so it doesn't show in My Account list of orders)
	if ( ! is_account_page() ) {
		$order_statuses['wc-kco-incomplete'] = 'Incomplete Klarna Checkout';
	}

	return $order_statuses;
}


/**
 * Helper function that determines if we're at KCO page or not.
 *
 * @return bool
 */
function is_klarna_checkout() {
	if ( is_page() ) {
		global $post;

		$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
		$checkout_pages    = array();
		$thank_you_pages   = array();

		// Clean request URI to remove all parameters.
		$clean_req_uri = explode( '?', $_SERVER['REQUEST_URI'] );
		$clean_req_uri = $clean_req_uri[0];
		$clean_req_uri = trailingslashit( $clean_req_uri );
		$length        = strlen( $clean_req_uri );

		// Get arrays of checkout and thank you pages for all countries.
		if ( is_array( $checkout_settings ) ) {
			foreach ( $checkout_settings as $cs_key => $cs_value ) {
				if ( strpos( $cs_key, 'klarna_checkout_url_' ) !== false ) {
					$checkout_pages[ $cs_key ] = substr( $cs_value, 0 - $length );
				}
				if ( strpos( $cs_key, 'klarna_checkout_thanks_url_' ) !== false ) {
					$thank_you_pages[ $cs_key ] = substr( $cs_value, 0 - $length );
				}
			}
		}

		// Start session if on a KCO or KCO Thank You page and KCO enabled.
		if ( in_array( $clean_req_uri, $checkout_pages, true ) || in_array( $clean_req_uri, $thank_you_pages, true ) ) {
			return true;
		}

		return false;
	}

	return false;
}

/**
 * Displays admin error messages if some of Klarna Checkout and Klarna Thank You pages are not valid URLs.
 */
function klarna_checkout_admin_error_notices() {
	// Only show it on Klarna settings pages.
	if ( isset( $_GET['section'] ) && 'klarna_checkout' === $_GET['section'] ) {
		// Get arrays of checkout and thank you pages for all countries.
		$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );

		if ( is_array( $checkout_settings ) ) {
			foreach ( $checkout_settings as $cs_key => $cs_value ) {
				if ( strpos( $cs_key, 'klarna_checkout_url_' ) !== false || strpos( $cs_key, 'klarna_checkout_thanks_url_' ) !== false ) {
					$page = url_to_postid( $checkout_settings[ $cs_key ] );

					// Check if URL is valid.
					if ( '' !== $checkout_settings[ $cs_key ] ) {
						if ( 0 === $page || get_permalink( $page ) !== $checkout_settings[ $cs_key ] ) {
							WC_Admin_Settings::add_error( sprintf( __( '%s is not a valid WordPress page URL.', 'woocommerce-gateway-klarna' ), $checkout_settings[ $cs_key ] ) );
						}

						// If it's a Checkout page, check if it contains shortcode.
						if ( 0 !== $page ) {
							if ( strpos( $cs_key, 'klarna_checkout_url_' ) !== false ) {
								$kco_page = get_post( $page );
								if ( ! has_shortcode( $kco_page->post_content, 'woocommerce_klarna_checkout' ) ) {
									WC_Admin_Settings::add_error( sprintf( __( '%s Klarna Checkout page doesn\'t contain [woocommerce_klarna_checkout] shortcode.', 'woocommerce-gateway-klarna' ), $checkout_settings[ $cs_key ] ) );
								}
							}
						}
					}
				}
			}
		}
	}
}

if ( ! empty( $_POST ) ) { // Input var okay.
	add_action( 'woocommerce_settings_saved', 'klarna_checkout_admin_error_notices' );
} else {
	add_action( 'admin_init', 'klarna_checkout_admin_error_notices' );
}

/**
 * Function used to Init Klarna Template Functions - This makes them pluggable by plugins and themes.
 */
function wc_klarna_include_template_functions() {
	include_once 'includes/klarna-template-functions.php';
}
add_action( 'after_setup_theme', 'wc_klarna_include_template_functions', 11 );

// Check if is_order_received_page function needs to be overwritten.
$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
$should_filter     = isset( $checkout_settings['filter_is_order_received'] ) ? $checkout_settings['filter_is_order_received'] : 'no';
if ( 'yes' === $should_filter ) {
	$thank_you_pages = array();

	// Clean request URI to remove all parameters.
	$clean_req_uri = explode( '?', $_SERVER['REQUEST_URI'] ); // Input var okay.
	$clean_req_uri = $clean_req_uri[0];
	$clean_req_uri = trailingslashit( $clean_req_uri );
	$length        = strlen( $clean_req_uri );

	// Get arrays of checkout and thank you pages for all countries.
	if ( is_array( $checkout_settings ) ) {
		foreach ( $checkout_settings as $cs_key => $cs_value ) {
			if ( strpos( $cs_key, 'klarna_checkout_thanks_url_' ) !== false && '' !== $cs_value ) {
				$clean_thank_you_uri        = explode( '?', $cs_value );
				$clean_thank_you_uri        = $clean_thank_you_uri[0];
				$thank_you_pages[ $cs_key ] = substr( $clean_thank_you_uri, 0 - $length );
			}
		}
	}
	// Overwrite is_order_received_page() only in Klarna Checkout thank you pages.
	if ( in_array( $clean_req_uri, $thank_you_pages, true ) ) {
		/**
		 * Is current page order received?
		 *
		 * @return bool
		 */
		function is_order_received_page() {
			return true;
		}
	}
}
