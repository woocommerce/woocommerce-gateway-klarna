<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
	define( 'WOOCOMMERCE_CART', true );
}
if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
	define( 'WOOCOMMERCE_CHECKOUT', true );
}
if ( ! defined( 'WOOCOMMERCE_KLARNA_AVAILABLE' ) ) {
	define( 'WOOCOMMERCE_KLARNA_AVAILABLE', true ); // Used to make gateway available for Subscriptions 2.0
}

/**
 * Display Klarna Checkout Thank You page
 */

// Debug
if ( $this->debug == 'yes' ) {
	$this->log->add( 'klarna', 'Rendering Thank you page...' );
}

// Shared secret
$merchantId   = $this->klarna_eid;
$sharedSecret = $this->klarna_secret;
$orderUri     = $_GET['klarna_order'];
$order_id     = $_GET['order-received'];
$order        = wc_get_order( $order_id );

// Connect to Klarna
if ( $this->is_rest() ) {
	if ( $this->testmode == 'yes' ) {
		if ( in_array( strtoupper( $this->klarna_country ), apply_filters( 'klarna_is_rest_countries_eu', array( 'DK', 'GB', 'NL' ) ) ) ) {
			$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_TEST_BASE_URL;
		} elseif ( in_array( strtoupper( $this->klarna_country ), apply_filters( 'klarna_is_rest_countries_na', array( 'US' ) ) ) ) {
			$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_TEST_BASE_URL;
		}
	} else {
		if ( in_array( strtoupper( $this->klarna_country ), apply_filters( 'klarna_is_rest_countries_eu', array( 'DK', 'GB', 'NL' ) ) ) ) {
			$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_BASE_URL;
		} elseif ( in_array( strtoupper( $this->klarna_country ), apply_filters( 'klarna_is_rest_countries_na', array( 'US' ) ) ) ) {
			$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_BASE_URL;
		}
	}

	$connector    = \Klarna\Rest\Transport\Connector::create( $merchantId, $sharedSecret, $klarna_server_url );
	$klarna_order = new Klarna\Rest\Checkout\Order( $connector, $orderUri );
} else {
	// Klarna_Checkout_Order::$contentType = 'application/vnd.klarna.checkout.aggregated-order-v2+json';  
	$connector    = Klarna_Checkout_Connector::create( $sharedSecret, $this->klarna_server );
	$klarna_order = new Klarna_Checkout_Order( $connector, $orderUri );
}

try {
	$klarna_order->fetch();
} catch ( Exception $e ) {
	if ( $this->debug == 'yes' ) {
		$this->log->add( 'klarna', 'Klarna API error: ' . var_export( $e, true ) );
	}

	if ( is_user_logged_in() && $this->debug ) {
		// The purchase was denied or something went wrong, print the message:
		echo '<div>';
		print_r( $e->getMessage() );
		echo '</div>';
	}
}

if ( $klarna_order['status'] == 'checkout_incomplete' ) {
	wp_redirect( $this->klarna_checkout_url );
	exit;
}

// Display Klarna iframe
if ( $this->is_rest() ) {
	$snippet = '<div>' . $klarna_order['html_snippet'] . '</div>';
} else {
	$snippet = '<div class="klarna-thank-you-snippet">' . $klarna_order['gui']['snippet'] . '</div>';
}

// Need to calculate totals because of woocommerce_checkout_order_processed hook below, plugins like WCS need totals calculated.
WC()->cart->calculate_shipping();
WC()->cart->calculate_totals();

// Add user ID, in case listener did not do it already.
if ( $order->get_user_id() === 0 ) {
	if ( email_exists( $klarna_order['billing_address']['email'] ) ) {
		$user        = get_user_by( 'email', $klarna_order['billing_address']['email'] );
		$customer_id = $user->ID;
		update_post_meta( $order_id, '_customer_user', $customer_id );
	} else {
		// Create new user.
		$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
		if ( 'yes' === $checkout_settings['create_customer_account'] ) {
			$customer_id = wc_create_new_customer( $klarna_order['billing_address']['email'] );

			if ( is_int( $customer_id ) ) {
				update_post_meta( $order_id, '_customer_user', $customer_id );
			}
		}
	}
}

// Log the user in.
if ( ! is_user_logged_in() && $order->get_user_id() > 0 ) {
	wp_set_current_user( $order->get_user_id() );
	wc_set_customer_auth_cookie( $order->get_user_id() );
}

do_action( 'woocommerce_checkout_order_processed', intval( $_GET['sid'] ), false );
do_action( 'klarna_before_kco_confirmation', intval( $_GET['sid'] ) );

echo $snippet;

do_action( 'klarna_after_kco_confirmation', intval( $_GET['sid'] ), $klarna_order );
do_action( 'woocommerce_thankyou', intval( $_GET['sid'] ) );

// Clear session and empty cart
WC()->session->__unset( 'klarna_checkout' );
WC()->session->__unset( 'klarna_checkout_country' );
WC()->session->__unset( 'ongoing_klarna_order' );
WC()->session->__unset( 'klarna_order_note' );
wc_clear_cart_after_payment(); // Clear cart.
