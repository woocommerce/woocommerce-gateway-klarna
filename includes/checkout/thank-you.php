<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
	require_once( KLARNA_LIB . 'vendor/autoload.php' );
	$connector = \Klarna\Rest\Transport\Connector::create(
	    $merchantId,
	    $sharedSecret,
	    \Klarna\Rest\Transport\ConnectorInterface::TEST_BASE_URL
	);
	$klarna_order = new \Klarna\Rest\Checkout\Order( $connector, $orderUri );
} else {
	// Klarna_Checkout_Order::$contentType = 'application/vnd.klarna.checkout.aggregated-order-v2+json';  
	$connector = Klarna_Checkout_Connector::create( $sharedSecret );  
	$klarna_order = new Klarna_Checkout_Order( $connector, $orderUri );
}
$klarna_order->fetch();

if ( $klarna_order['status'] == 'checkout_incomplete' ) {
	wp_redirect( $this->klarna_checkout_url );
	exit;  
}

// Display Klarna iframe
if ( $this->is_rest() ) {
	$snippet = "<div>{$klarna_order['html_snippet']}</div>";
} else {
	$snippet = '<div class="klarna-thank-you-snippet">' . $klarna_order['gui']['snippet'] . '</div>';	
}

do_action( 'klarna_before_kco_confirmation', $_GET['sid'] );
echo $snippet;	
do_action( 'klarna_after_kco_confirmation', $_GET['sid'] );
do_action( 'woocommerce_thankyou', $_GET['sid'] );

// Clear session and empty cart
WC()->session->__unset( 'klarna_checkout' );
$woocommerce->cart->empty_cart(); // Remove cart