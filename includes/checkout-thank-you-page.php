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

Klarna_Checkout_Order::$contentType = 'application/vnd.klarna.checkout.aggregated-order-v2+json';  

$orderUri = $_GET['klarna_order'];

$connector = Klarna_Checkout_Connector::create( $sharedSecret );  

$klarna_order = new Klarna_Checkout_Order( $connector, $orderUri );

$klarna_order->fetch();  

if ( $klarna_order['status'] == 'checkout_incomplete' ) {
	wp_redirect( $this->klarna_checkout_url );
	exit;  
}

$snippet = $klarna_order['gui']['snippet'];

// DESKTOP: Width of containing block shall be at least 750px
// MOBILE: Width of containing block shall be 100% of browser window (No
// padding or margin)
ob_start();
do_action( 'klarna_before_kco_confirmation', $_GET['sid'] );
echo '<div>' . $snippet . '</div>';	
do_action( 'klarna_after_kco_confirmation', $_GET['sid'] );
do_action( 'woocommerce_thankyou', $_GET['sid'] );
unset( $_SESSION['klarna_checkout'] );
$woocommerce->cart->empty_cart(); // Remove cart
return ob_get_clean();