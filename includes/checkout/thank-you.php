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

// Connect to Klarna
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

	$connector = \Klarna\Rest\Transport\Connector::create(
		$merchantId,
		$sharedSecret,
		$klarna_server_url
	);
	$klarna_order = new Klarna\Rest\Checkout\Order( $connector, $orderUri );
} else {
	// Klarna_Checkout_Order::$contentType = 'application/vnd.klarna.checkout.aggregated-order-v2+json';  
	$connector = Klarna_Checkout_Connector::create(
		$sharedSecret,
		$this->klarna_server
	);  
	$klarna_order = new Klarna_Checkout_Order(
		$connector, 
		$orderUri
	);
}

try {
	$klarna_order->fetch();
} catch( Exception $e ) {
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

do_action( 'klarna_before_kco_confirmation', intval( $_GET['sid'] ) );

// WC Subscriptions 2.0 needs this
if ( class_exists( 'WC_Subscriptions_Cart' ) && WC_Subscriptions_Cart::cart_contains_subscription() ) {
	sleep( 5 );
	$parent_order = new WC_Order( intval( $_GET['sid'] ) );
	$subscriptions = array();
	// First clear out any subscriptions created for a failed payment to give us a clean slate for creating new subscriptions
	$subscriptions = wcs_get_subscriptions_for_order( $parent_order->id, array( 'order_type' => 'parent' ) );
	if ( ! empty( $subscriptions ) ) {
		foreach ( $subscriptions as $subscription ) {
			wp_delete_post( $subscription->id );
		}
	}
	WC()->cart->calculate_totals();
	// Create new subscriptions for each group of subscription products in the cart (that is not a renewal)
	foreach ( WC()->cart->recurring_carts as $recurring_cart ) {
		$subscription = WC_Subscriptions_Checkout::create_subscription( $parent_order, $recurring_cart ); // Exceptions are caught by WooCommerce
		$subscription->payment_complete();
		if ( is_wp_error( $subscription ) ) {
			throw new Exception( $subscription->get_error_message() );
		}
		do_action( 'woocommerce_checkout_subscription_created', $subscription, $parent_order, $recurring_cart );
	}
	do_action( 'subscriptions_created_for_order', $parent_order ); // Backward compatibility
}

echo $snippet;	

do_action( 'klarna_after_kco_confirmation', intval( $_GET['sid'] ) );
do_action( 'woocommerce_thankyou', intval( $_GET['sid'] ) );

// Clear session and empty cart
WC()->session->__unset( 'klarna_checkout' );
WC()->session->__unset( 'klarna_checkout_country' );
WC()->session->__unset( 'ongoing_klarna_order' );
WC()->session->__unset( 'klarna_order_note' );
WC()->cart->empty_cart(); // Remove cart