<?php
/**
 * Displays Klarna checkout page
 *
 * @package WC_Gateway_Klarna
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$kco_klarna_eid                    = WC_Gateway_Klarna_Checkout_Variables::get_klarna_eid();
$kco_klarna_secret                 = WC_Gateway_Klarna_Checkout_Variables::get_klarna_secret();
$kco_klarna_country                = WC_Gateway_Klarna_Checkout_Variables::get_klarna_country();
$kco_klarna_language               = WC_Gateway_Klarna_Checkout_Variables::get_klarna_language();
$kco_klarna_checkout_url           = WC_Gateway_Klarna_Checkout_Variables::get_klarna_checkout_url();
$kco_klarna_checkout_thank_you_url = WC_Gateway_Klarna_Checkout_Variables::get_klarna_checkout_thank_you_url();
$kco_terms_url                     = WC_Gateway_Klarna_Checkout_Variables::get_terms_url();
$kco_cancellation_terms_url        = WC_Gateway_Klarna_Checkout_Variables::get_cancellation_terms_url();
$kco_klarna_server                 = WC_Gateway_Klarna_Checkout_Variables::get_klarna_server();
$kco_testmode                      = WC_Gateway_Klarna_Checkout_Variables::get_klarna_checkout_testmode();
$kco_debug                         = WC_Gateway_Klarna_Checkout_Variables::get_klarna_checkout_debug();
// $kco_log                           = WC_Gateway_Klarna_Checkout_Variables::get_klarna_checkout_log();
$kco_is_rest                       = WC_Gateway_Klarna_Checkout_Variables::is_rest();
$kco_show_kco                      = WC_Gateway_Klarna_Checkout_Variables::show_kco();
$kco_add_std_checkout_button       = WC_Gateway_Klarna_Checkout_Variables::add_std_checkout_button();
$kco_add_std_checkout_button_label = WC_Gateway_Klarna_Checkout_Variables::add_std_checkout_button_label();
$kco_validate_stock                = WC_Gateway_Klarna_Checkout_Variables::validate_stock();
$kco_color_options                 = WC_Gateway_Klarna_Checkout_Variables::get_color_options();
$kco_allowed_customer_types        = WC_Gateway_Klarna_Checkout_Variables::get_allowed_customer_types();
$kco_allow_separate_shipping       = WC_Gateway_Klarna_Checkout_Variables::get_allow_separate_shipping();
$kco_phone_mandatory_de            = WC_Gateway_Klarna_Checkout_Variables::get_phone_mandatory_de();
$kco_dhl_packstation_de            = WC_Gateway_Klarna_Checkout_Variables::get_dhl_packstation_de();

// Check if iframe needs to be displayed
if ( ! $kco_show_kco ) {
	return;
}

// Check if there are any recurring items in the cart and if it's a "recurring" country
if ( class_exists( 'WC_Subscriptions_Cart' ) && WC_Subscriptions_Cart::cart_contains_subscription() ) {
	if ( ! in_array( strtoupper( $kco_klarna_country ), array( 'SE', 'NO', 'FI' ), true ) ) {
		$checkout_url = wc_get_checkout_url();
		wp_safe_redirect( $checkout_url );
		exit;
	}
}

// Check if selected Klarna country is in WooCommerce allowed countries
if ( ! array_key_exists( strtoupper( $kco_klarna_country ), WC()->countries->get_allowed_countries() ) ) {
	$checkout_url = wc_get_checkout_url();
	wp_safe_redirect( $checkout_url );
	exit;
}

// Let other plugins (like min/max) add their notices.
do_action( 'woocommerce_check_cart_items' );

if ( wc_notice_count( 'error' ) > 0 ) {
	wp_safe_redirect( wc_get_cart_url() );
} else {
	// Process order via Klarna Checkout page.
	if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
		define( 'WOOCOMMERCE_CHECKOUT', true );
	}

	// Process order via Klarna Checkout page.
	if ( ! defined( 'WOOCOMMERCE_KLARNA_CHECKOUT' ) ) {
		define( 'WOOCOMMERCE_KLARNA_CHECKOUT', true );
	}

	// Set Klarna Checkout as the chosen payment method in the WC session.
	WC()->session->set( 'chosen_payment_method', 'klarna_checkout' );

	// Set customer country so taxes and shipping can be calculated properly.
	if ( version_compare( WOOCOMMERCE_VERSION, '3.0', '<' ) ) {
		WC()->customer->set_country( strtoupper( $kco_klarna_country ) );
		WC()->customer->set_shipping_country( strtoupper( $kco_klarna_country ) );
	} else {
		if ( WC()->customer->get_id() ) {
			$wc_customer = new WC_Customer( WC()->customer->get_id() );
			$wc_customer->set_billing_country( strtoupper( $kco_klarna_country ) );
			$wc_customer->set_shipping_country( strtoupper( $kco_klarna_country ) );
			$wc_customer->save();
		} else {
			WC()->customer->set_billing_country( strtoupper( $kco_klarna_country ) );
			WC()->customer->set_shipping_country( strtoupper( $kco_klarna_country ) );
			WC()->customer->save();
		}
	}

	// Debug.
	if ( $kco_debug == 'yes' ) {
		// $kco_log->add( 'klarna', 'Rendering Checkout page...' );
	}

	// Mobile or desktop browser.
	if ( wp_is_mobile() ) {
		$klarna_checkout_layout = 'mobile';
	} else {
		$klarna_checkout_layout = 'desktop';
	}

	/**
	 * Set $add_klarna_window_size_script to true so that Window size
	 * detection script can load in the footer
	 */
	global $add_klarna_window_size_script;
	$add_klarna_window_size_script = true;

	// Recheck cart items so that they are in stock
	$result = WC()->cart->check_cart_item_stock();
	if ( is_wp_error( $result ) ) {
		return $result->get_error_message();
	}

	// Check if there's anything in the cart.
	if ( count( WC()->cart->get_cart() ) > 0 ) {

		if ( isset( $_GET['no_shipping'] ) ) {
			echo '<div class="woocommerce-error">';
			_e( 'Please select a shipping method', 'woocommerce-gateway-klarna' );
			echo '</div>';
		}

		if ( isset( $_GET['subscription_limit'] ) ) {
			echo '<div class="woocommerce-error">';
			esc_html_e( 'You have an active subscription to this product already.', 'woocommerce-subscriptions' );
			echo '</div>';
		}

		// Add button to Standard Checkout Page if this is enabled in the settings.
		if ( 'yes' === $kco_add_std_checkout_button ) {
			echo '<div class="woocommerce">';
			echo '<a href="' . get_permalink( get_option( 'woocommerce_checkout_page_id' ) ) . '" class="button std-checkout-button">';
			echo $kco_add_std_checkout_button_label;
			echo '</a>';
			echo '</div>';
		}

		// Get Klarna credentials.
		$eid          = $kco_klarna_eid;
		$sharedSecret = $kco_klarna_secret;

		// Process cart contents and prepare them for Klarna.
		include_once KLARNA_DIR . 'classes/class-wc-to-klarna.php';
		$wc_to_klarna = new WC_Gateway_Klarna_WC2K( $kco_is_rest, $kco_klarna_country );
		$cart         = $wc_to_klarna->process_cart_contents();

		// Initiate Klarna.
		if ( $kco_is_rest ) {
			if ( 'yes' === $kco_testmode ) {
				if ( in_array(
					strtoupper( $kco_klarna_country ), apply_filters(
						'klarna_is_rest_countries_eu', array(
							'DK',
							'GB',
							'NL',
						)
					)
				) ) {
					$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_TEST_BASE_URL;
				} elseif ( in_array( strtoupper( $kco_klarna_country ), apply_filters( 'klarna_is_rest_countries_na', array( 'US' ) ) ) ) {
					$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_TEST_BASE_URL;
				}
			} else {
				if ( in_array(
					strtoupper( $kco_klarna_country ), apply_filters(
						'klarna_is_rest_countries_eu', array(
							'DK',
							'GB',
							'NL',
						)
					)
				) ) {
					$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_BASE_URL;
				} elseif ( in_array( strtoupper( $kco_klarna_country ), apply_filters( 'klarna_is_rest_countries_na', array( 'US' ) ) ) ) {
					$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_BASE_URL;
				}
			}
			$connector = Klarna\Rest\Transport\Connector::create( $eid, $sharedSecret, $klarna_server_url );
		} else {
			$connector = Klarna_Checkout_Connector::create( $sharedSecret, $kco_klarna_server );
		}
		$klarna_order = null;


		/**
		 * Create WooCommerce order
		 */
		$customer_email = '';
		if ( is_user_logged_in() ) {
			global $current_user;
			$customer_email = $current_user->user_email;
		}
		if ( '' == $customer_email ) {
			$customer_email = 'guest_checkout@klarna.com';
		}
		if ( ! is_email( $customer_email ) ) {
			return;
		}
		// Check quantities
		$result = WC()->cart->check_cart_item_stock();
		if ( is_wp_error( $result ) ) {
			return $result->get_error_message();
		}
		// Update the local order
		include_once KLARNA_DIR . 'classes/class-klarna-to-wc.php';
		$klarna_to_wc = new WC_Gateway_Klarna_K2WC();
		$klarna_to_wc->set_rest( $kco_is_rest );
		$klarna_to_wc->set_eid( $kco_klarna_eid );
		$klarna_to_wc->set_secret( $kco_klarna_secret );
		// $klarna_to_wc->set_klarna_log( $kco_log );
		$klarna_to_wc->set_klarna_debug( $kco_debug );
		$klarna_to_wc->set_klarna_test_mode( $kco_testmode );
		$klarna_to_wc->set_klarna_server( $kco_klarna_server );
		$klarna_to_wc->set_klarna_credentials_country( $kco_klarna_country );
		if ( $customer_email ) {
			$orderid = $klarna_to_wc->prepare_wc_order( $customer_email );
		} else {
			$orderid = $klarna_to_wc->prepare_wc_order();
		}

		// Add GA cookie as custom field for GA Ecommerce plugin.
		if ( class_exists( 'Yoast_GA_Woo_eCommerce_Tracking' ) && isset( $_COOKIE['_ga'] ) ) {
			// The _ga cookie consists of GA[version_number][user_id], we are only interested in the user_id
			// so strip the version number.
			$cookie = preg_replace( '/^(GA\d\.\d\.)/', '', $_COOKIE['_ga'] );
			update_post_meta( $orderid, '_yoast_gau_uuid', $cookie );
		}

		// Add GA cookie as custom field for GA Ecommerce plugin.
		if ( class_exists( 'MonsterInsights_eCommerce' ) && isset( $_COOKIE['_ga'] ) ) {
			// The _ga cookie consists of GA[version_number][user_id], we are only interested in the user_id
			// so strip the version number.
			$cookie       = '';
			$ga_cookie    = $_COOKIE['_ga'];
			$cookie_parts = explode( '.', $ga_cookie );
			if ( is_array( $cookie_parts ) && ! empty( $cookie_parts[2] ) && ! empty( $cookie_parts[3] ) ) {
				$uuid = (string) $cookie_parts[2] . '.' . (string) $cookie_parts[3];
				if ( is_string( $uuid ) ) {
					$cookie = $uuid;
				} else {
					$cookie = false;
				}
			} else {
				$cookie = false;
			}
			update_post_meta( $orderid, '_yoast_gau_uuid', $cookie );

			$cookie = '';
			if ( empty( $_COOKIE['_ga'] ) ) {
				$cookie = 'FCE';
			} else {
				$ga_cookie    = $_COOKIE['_ga'];
				$cookie_parts = explode( '.', $ga_cookie );
				if ( is_array( $cookie_parts ) && ! empty( $cookie_parts[2] ) && ! empty( $cookie_parts[3] ) ) {
					$uuid = (string) $cookie_parts[2] . '.' . (string) $cookie_parts[3];
					if ( is_string( $uuid ) ) {
						$cookie = $ga_cookie;
					} else {
						$cookie = 'FA';
					}
				} else {
					$cookie = 'FAE';
				}
			}
			update_post_meta( $orderid, '_monsterinsights_cookie', $cookie );
		}


		// WC Subscriptions 2.0 needs this
		if ( class_exists( 'WC_Subscriptions_Cart' ) && WC_Subscriptions_Cart::cart_contains_subscription() ) {
			update_post_meta( $orderid, '_klarna_recurring_carts', WC()->cart->recurring_carts );
		}

		/**
		 * Check if Klarna order already exists and if country was changed
		 */
		if ( WC()->session->get( 'klarna_checkout' ) && WC()->session->get( 'klarna_checkout_country' ) === klarna_wc_get_customer_country( WC()->customer ) ) {
			include KLARNA_DIR . 'includes/checkout/resume.php';
		}
		// If it doesn't, create Klarna order
		if ( $klarna_order == null ) {
			include KLARNA_DIR . 'includes/checkout/create.php';
		}

		// Store location of checkout session
		if ( $kco_is_rest ) {
			$sessionId = $klarna_order['order_id'];
		} else {
			$sessionId = $klarna_order['id'];
		}

		// Setting these here because cross-sells need them
		update_post_meta( $local_order_id, '_klarna_order_id', $sessionId );
		update_post_meta( $local_order_id, '_billing_country', WC()->session->get( 'klarna_country' ) );

		// Set session values for Klarna order ID and Klarna order country
		WC()->session->set( 'klarna_checkout', $sessionId );
		WC()->session->set( 'klarna_checkout_country', klarna_wc_get_customer_country( WC()->customer ) );

		// Display checkout
		do_action( 'klarna_before_kco_checkout' );
		if ( $kco_is_rest ) {
			$snippet = $klarna_order['html_snippet'];
		} else {
			$snippet = $klarna_order['gui']['snippet'];
		}
		echo '<div>' . apply_filters( 'klarna_kco_checkout', $snippet ) . '</div>';

		do_action( 'klarna_after_kco_checkout' );
	} else {
		// If cart is empty, clear these variables
		wp_delete_post( WC()->session->get( 'ongoing_klarna_order' ), true ); // Delete WooCommerce order

		WC()->session->__unset( 'klarna_checkout' ); // Klarna order ID
		WC()->session->__unset( 'klarna_checkout_country' ); // Klarna order ID
		WC()->session->__unset( 'ongoing_klarna_order' ); // WooCommerce order ID
		WC()->session->__unset( 'klarna_order_note' ); // Order note

		wp_redirect( wc_get_cart_url() ); // Redirect to cart page
	} // End if sizeof cart
}
