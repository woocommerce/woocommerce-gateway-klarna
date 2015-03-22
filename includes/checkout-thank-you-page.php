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
$sharedSecret = $this->klarna_secret;

// Connect to Klarna
Klarna_Checkout_Order::$contentType = 'application/vnd.klarna.checkout.aggregated-order-v2+json';  
$orderUri = $_GET['klarna_order'];
$connector = Klarna_Checkout_Connector::create( $sharedSecret );  
$klarna_order = new Klarna_Checkout_Order( $connector, $orderUri );
$klarna_order->fetch();

if ( $klarna_order['status'] == 'checkout_incomplete' ) {
	wp_redirect( $this->klarna_checkout_url );
	exit;  
}

// Display Klarna iframe
$snippet = $klarna_order['gui']['snippet'];
do_action( 'klarna_before_kco_confirmation', $_GET['sid'] );
echo '<div class="klarna-thank-you-snippet">' . $snippet . '</div>';	
do_action( 'klarna_after_kco_confirmation', $_GET['sid'] );
do_action( 'woocommerce_thankyou', $_GET['sid'] );

// Clear session and empty cart
unset( $_SESSION['klarna_checkout'] );
$woocommerce->cart->empty_cart(); // Remove cart