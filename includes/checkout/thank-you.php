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
WC()->session->__unset( 'ongoing_klarna_order' );
WC()->cart->empty_cart(); // Remove cart






$orderid = $_GET['sid'];

$klarna_order_id = get_post_meta( $orderid, '_klarna_order_id', true );
$connector = Klarna\Rest\Transport\Connector::create(
	$this->eid_uk,
	$this->secret_uk,
	Klarna\Rest\Transport\ConnectorInterface::TEST_BASE_URL
);
$k_order = new Klarna\Rest\OrderManagement\Order(
	$connector,
	$klarna_order_id
);
$k_order->fetch();							
echo '<pre>';
print_r( $k_order['order_lines'] );
echo '</pre>';

echo '<pre>';
print_r( $k_order['order_amount'] );
echo '</pre>';

echo '<pre>';
$wc_order = wc_get_order( $orderid );
print_r( $wc_order->get_items() );
echo '</pre>';