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

$ty_klarna_eid          = WC_Gateway_Klarna_Checkout_Variables::get_klarna_eid();
$ty_klarna_secret       = WC_Gateway_Klarna_Checkout_Variables::get_klarna_secret();
$ty_klarna_country      = WC_Gateway_Klarna_Checkout_Variables::get_klarna_country();
$ty_klarna_checkout_url = WC_Gateway_Klarna_Checkout_Variables::get_klarna_checkout_url();
$ty_klarna_server       = WC_Gateway_Klarna_Checkout_Variables::get_klarna_server();
$ty_testmode            = WC_Gateway_Klarna_Checkout_Variables::get_klarna_checkout_testmode();
$ty_debug               = WC_Gateway_Klarna_Checkout_Variables::get_klarna_checkout_debug();
$ty_log                 = WC_Gateway_Klarna_Checkout_Variables::get_klarna_checkout_log();
$ty_is_rest             = WC_Gateway_Klarna_Checkout_Variables::is_rest();

// Debug.
if ( 'yes' === $ty_debug ) {
	// $ty_log->add( 'klarna', 'Rendering Thank you page...' );
}

$merchant_id   = $ty_klarna_eid;
$shared_secret = $ty_klarna_secret;
$order_uri     = $_GET['klarna_order'];
$order_id      = (int) $_GET['order-received'];
$order         = wc_get_order( $order_id );

// Connect to Klarna.
if ( $ty_is_rest ) {
	if ( 'yes' === $ty_testmode ) {
		if ( in_array(
			strtoupper( $ty_klarna_country ), apply_filters(
				'klarna_is_rest_countries_eu', array(
					'DK',
					'GB',
					'NL',
				)
			)
		) ) {
			$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_TEST_BASE_URL;
		} elseif ( in_array( strtoupper( $ty_klarna_country ), apply_filters( 'klarna_is_rest_countries_na', array( 'US' ) ) ) ) {
			$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_TEST_BASE_URL;
		}
	} else {
		if ( in_array(
			strtoupper( $ty_klarna_country ), apply_filters(
				'klarna_is_rest_countries_eu', array(
					'DK',
					'GB',
					'NL',
				)
			)
		) ) {
			$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_BASE_URL;
		} elseif ( in_array( strtoupper( $ty_klarna_country ), apply_filters( 'klarna_is_rest_countries_na', array( 'US' ) ) ) ) {
			$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_BASE_URL;
		}
	}

	$connector    = \Klarna\Rest\Transport\Connector::create( $merchant_id, $shared_secret, $klarna_server_url );
	$klarna_order = new Klarna\Rest\Checkout\Order( $connector, $order_uri );
} else {
	// Klarna_Checkout_Order::$contentType = 'application/vnd.klarna.checkout.aggregated-order-v2+json';.
	$connector    = Klarna_Checkout_Connector::create( $shared_secret, $ty_klarna_server );
	$klarna_order = new Klarna_Checkout_Order( $connector, $order_uri );
}

try {
	$klarna_order->fetch();
	// WC_Gateway_Klarna::log( 'Klarna Thank you URL: ' . $_SERVER['REQUEST_URI'] . ' Order id: ' . $order_id . ' $klarna_order: ' . var_export( $klarna_order, true ) );
} catch ( Exception $e ) {
	if ( 'yes' === $ty_debug ) {
		// $ty_log->add( 'klarna', 'Klarna API error: ' . var_export( $e, true ) );
	}

	if ( is_user_logged_in() && $ty_debug ) {
		// The purchase was denied or something went wrong, print the message.
		echo '<div>';
		echo esc_html( $e->getCode() . ' - ' . $e->getMessage() );
		echo '</div>';
	}
}

if ( 'checkout_incomplete' === $klarna_order['status'] ) {
	echo '<script>window.location.replace("' . $ty_klarna_checkout_url . '")</script>';
	return;

	wp_safe_redirect( $ty_klarna_checkout_url );
	exit;
}

// Display Klarna iframe.
if ( $ty_is_rest ) {
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

// Add "posted" data from Klarna order.
$posted_data = array(
	'terms'               => true,
	'payment_method'      => 'klarna_checkout',
	'billing_first_name'  => $klarna_order['billing_address']['given_name'],
	'billing_last_name'   => $klarna_order['billing_address']['family_name'],
	'billing_country'     => $klarna_order['billing_address']['country'],
	'billing_address_1'   => $klarna_order['billing_address']['street_address'],
	'billing_city'        => $klarna_order['billing_address']['city'],
	'billing_postcode'    => $klarna_order['billing_address']['postal_code'],
	'billing_phone'       => $klarna_order['billing_address']['phone'],
	'billing_email'       => $klarna_order['billing_address']['email'],
	'shipping_first_name' => $klarna_order['billing_address']['given_name'],
	'shipping_last_name'  => $klarna_order['billing_address']['family_name'],
	'shipping_country'    => $klarna_order['billing_address']['country'],
	'shipping_address_1'  => $klarna_order['billing_address']['street_address'],
	'shipping_city'       => $klarna_order['billing_address']['city'],
	'shipping_postcode'   => $klarna_order['billing_address']['postal_code'],
);

// In some cases the order confirmation callback from Klarna happens after the display of the thankyou page.
// In these cases we need to add customer address to the order here. Plugins like WCS needs this when woocommerce_checkout_order_processed hook is run.
if ( '' === get_post_meta( $order_id, '_billing_address_1', true ) ) {
	include_once KLARNA_DIR . 'classes/class-klarna-to-wc.php';
	WC_Gateway_Klarna_K2WC::add_order_addresses( $order, $klarna_order );
}

do_action( 'woocommerce_checkout_order_processed', $order_id, $posted_data, $order );
do_action( 'klarna_before_kco_confirmation', $order_id, $klarna_order );

echo $snippet;

/**
 * Setting filter value to true will finalize the order in thank you page instead of push listener, and help
 * plugins like MonsterInsights pull data they need.
 */
if ( true === apply_filters( 'klarna_finalize_order_in_thank_you_page', false ) ) {
	$order->calculate_totals( false );
	$order->update_status( 'pending' ); // Set status to Pending Payment before completing the order.
	$order->payment_complete( $order_uri );
	$order->set_date_created( current_time( 'timestamp', true ) );
	delete_post_meta( klarna_wc_get_order_id( $order ), '_kco_incomplete_customer_email' );
	add_post_meta( klarna_wc_get_order_id( $order ), '_kco_payment_created', time() );
}

do_action( 'klarna_after_kco_confirmation', $order_id, $klarna_order );

if ( ! did_action( 'woocommerce_thankyou' ) ) {
	do_action( 'woocommerce_thankyou', $order_id );
}

wc_clear_cart_after_payment(); // Clear cart.
