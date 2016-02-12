<?php
/**
 * Displays Klarna checkout page
 *
 * @package WC_Gateway_Klarna
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Check if iframe needs to be displayed
if ( ! $this->show_kco() ) {
	return;
}

// Check if selected Klarna country is in WooCommerce allowed countries
if ( ! array_key_exists( strtoupper( $this->get_klarna_country() ), WC()->countries->get_allowed_countries() ) ) {
	global $woocommerce;
	$checkout_url = $woocommerce->cart->get_checkout_url();
	wp_safe_redirect( $checkout_url );
	exit;
}

// Check if there are any recurring items in the cart and if it's a "recurring" country
if ( class_exists( 'WC_Subscriptions_Cart' ) && WC_Subscriptions_Cart::cart_contains_subscription() ) {
	if ( ! in_array( strtoupper( $this->get_klarna_country() ), array( 'SE', 'NO' ) ) ) {
		global $woocommerce;
		$checkout_url = $woocommerce->cart->get_checkout_url();
		wp_safe_redirect( $checkout_url );
		exit;
	}
}


// Process order via Klarna Checkout page
if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
	define( 'WOOCOMMERCE_CHECKOUT', true );
}

// Process order via Klarna Checkout page
if ( ! defined( 'WOOCOMMERCE_KLARNA_CHECKOUT' ) ) {
	define( 'WOOCOMMERCE_KLARNA_CHECKOUT', true );
}

// Set Klarna Checkout as the chosen payment method in the WC session
WC()->session->set( 'chosen_payment_method', 'klarna_checkout' );

// Set customer country so taxes and shipping can be calculated properly
WC()->customer->set_country( strtoupper( $this->get_klarna_country() ) );
WC()->customer->set_shipping_country( strtoupper( $this->get_klarna_country() ) );

// Debug
if ( $this->debug == 'yes' ) {
	$this->log->add( 'klarna', 'Rendering Checkout page...' );
}

// Mobile or desktop browser
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
$result = $woocommerce->cart->check_cart_item_stock();
if ( is_wp_error( $result ) ) {
	return $result->get_error_message();
}

// Check if there's anything in the cart
if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) {

	// Add button to Standard Checkout Page if this is enabled in the settings
	if ( $this->add_std_checkout_button == 'yes' ) {
		echo '<div class="woocommerce"><a href="' . get_permalink( get_option( 'woocommerce_checkout_page_id' ) ) . '" class="button std-checkout-button">' . $this->std_checkout_button_label . '</a></div>';
	}

	// Get Klarna credentials
	$eid          = $this->klarna_eid;
	$sharedSecret = $this->klarna_secret;

	// Process cart contents and prepare them for Klarna
	include_once( KLARNA_DIR . 'classes/class-wc-to-klarna.php' );
	$wc_to_klarna = new WC_Gateway_Klarna_WC2K( $this->is_rest(), $this->klarna_country );
	$cart         = $wc_to_klarna->process_cart_contents();

	// Initiate Klarna
	if ( $this->is_rest() ) {
		if ( $this->testmode == 'yes' ) {
			if ( 'gb' == $this->klarna_country ) {
				$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_TEST_BASE_URL;
			} elseif ( 'us' == $this->klarna_country ) {
				$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_TEST_BASE_URL;
			}
		} else {
			if ( 'gb' == $this->klarna_country ) {
				$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_BASE_URL;
			} elseif ( 'us' == $this->klarna_country ) {
				$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_BASE_URL;
			}
		}
		$connector = Klarna\Rest\Transport\Connector::create( $eid, $sharedSecret, $klarna_server_url );
	} else {
		$connector = Klarna_Checkout_Connector::create( $sharedSecret, $this->klarna_server );
	}
	$klarna_order = null;


	/**
	 * Create WooCommerce order
	 */
	$orderid = $this->update_or_create_local_order();

	/**
	 * Check if Klarna order already exists and if country was changed
	 */
	if ( WC()->session->get( 'klarna_checkout' ) && WC()->session->get( 'klarna_checkout_country' ) == WC()->customer->get_country() ) {
		include( KLARNA_DIR . 'includes/checkout/resume.php' );
	}
	// If it doesn't, create Klarna order
	if ( $klarna_order == null ) {
		include( KLARNA_DIR . 'includes/checkout/create.php' );
	}

	// Store location of checkout session
	if ( $this->is_rest() ) {
		$sessionId = $klarna_order['order_id'];
	} else {
		$sessionId = $klarna_order['id'];
	}

	// Set session values for Klarna order ID and Klarna order country
	WC()->session->set( 'klarna_checkout', $sessionId );
	WC()->session->set( 'klarna_checkout_country', WC()->customer->get_country() );

	// Display checkout
	do_action( 'klarna_before_kco_checkout' );
	if ( $this->is_rest() ) {
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
	wp_redirect( $woocommerce->cart->get_cart_url() ); // Redirect to cart page
} // End if sizeof cart