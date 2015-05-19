<?php
/**
 * Creates new order in Klarna checkout page
 *
 * @package WC_Gateway_Klarna
 */

// Start new session

// Check if country was set in WC session
// Used when changing countries in KCO page
$kco_session_country = WC()->session->get( 'klarna_country', '' );
$kco_session_locale = '';

if ( '' != $kco_session_country ) {
	if ( 'DE' == $kco_session_country ) {
		$kco_session_locale = 'de-de';
	} elseif ( 'FI' == $kco_session_country ) {
		// Check if WPML is used and determine if Finnish or Swedish is used as language
		if ( class_exists( 'woocommerce_wpml' ) && defined( 'ICL_LANGUAGE_CODE' ) && strtoupper( ICL_LANGUAGE_CODE ) == 'SV' ) {
			// Swedish
			$kco_session_locale = 'sv-fi';
		} else {
			// Finnish
			$kco_session_locale = 'fi-fi';
		}
	}
}

$kco_country = ( '' != $kco_session_country ) ? $kco_session_country : $this->klarna_country;
$kco_locale = ( '' != $kco_session_locale ) ? $kco_session_locale : $this->klarna_language;

$create['purchase_country'] = $kco_country;
$create['purchase_currency'] = $this->klarna_currency;
$create['locale'] = $kco_locale;

if ( ! $this->is_rest() ) {
	$create['merchant']['id'] = $eid; // Only needed in V2 of API
}

// Merchant URIs
$merchant_terms_uri = $this->terms_url;
$merchant_checkout_uri = esc_url_raw( add_query_arg( 
	'klarnaListener', 
	'checkout', 
	$this->klarna_checkout_url 
) );
$merchant_confirmation_uri = add_query_arg ( 
	array(
		'klarna_order' => '{checkout.order.uri}', 
		'sid' => $klarna_transient, 
		'order-received' => $klarna_transient 
	),
	$this->klarna_checkout_thanks_url
);
if ( $this->is_rest() ) {
	$merchant_push_uri = add_query_arg( 
		array(
			'sid' => $klarna_transient, 
			'scountry' => $this->klarna_country, 
			'klarna_order' => '{checkout.order.id}', 
			'wc-api' => 'WC_Gateway_Klarna_Checkout'
		),
		$this->klarna_checkout_url 
	);			
} else {
	$merchant_push_uri = add_query_arg( 
		array(
			'sid' => $klarna_transient, 
			'scountry' => $this->klarna_country, 
			'klarna_order' => '{checkout.order.uri}', 
			'wc-api' => 'WC_Gateway_Klarna_Checkout'
		),
		$this->klarna_checkout_url 
	);
}

// Different format for V3 and V2
if ( $this->is_rest() ) {
	$merchantUrls = array(
		'terms' =>        $merchant_terms_uri,
		'checkout' =>     $merchant_checkout_uri,
		'confirmation' => $merchant_confirmation_uri,
		'push' =>         $merchant_push_uri
	);
	$create['merchant_urls'] = $merchantUrls;
} else {
	$create['merchant']['terms_uri'] =        $merchant_terms_uri;
	$create['merchant']['checkout_uri'] =     $merchant_checkout_uri;
	$create['merchant']['confirmation_uri'] = $merchant_confirmation_uri;
	$create['merchant']['push_uri'] =         $merchant_push_uri;
}

// Make phone a mandatory field for German stores?
if ( $this->phone_mandatory_de == 'yes' ) {
	$create['options']['phone_mandatory'] = true;	
}

// Enable DHL packstation feature for German stores?
if ( $this->dhl_packstation_de == 'yes' ) {
	$create['options']['packstation_enabled'] = true;	
}

// Customer info if logged in
if ( $this->testmode !== 'yes' ) {
	if ( $current_user->user_email ) {
		$create['shipping_address']['email'] = $current_user->user_email;
	}

	if ( $woocommerce->customer->get_shipping_postcode() ) {
		$create['shipping_address']['postal_code'] = $woocommerce->customer->get_shipping_postcode();
	}
}

$create['gui']['layout'] = $klarna_checkout_layout;

foreach ( $cart as $item ) {
	if ( $this->is_rest() ) {
		$create['order_lines'][] = $item;				
	} else {
		$create['cart']['items'][] = $item;				
	}
}

// Colors
if ( '' != $this->color_button ) {
	$create['options']['color_button'] = $this->color_button;
}
if ( '' != $this->color_button_text ) {
	$create['options']['color_button_text'] = $this->color_button_text;
}
if ( '' != $this->color_checkbox ) {
	$create['options']['color_checkbox'] = $this->color_checkbox;
}
if ( '' != $this->color_checkbox_checkmark ) {
	$create['options']['color_checkbox_checkmark'] = $this->color_checkbox_checkmark;
}
if ( '' != $this->color_header ) {
	$create['options']['color_header'] = $this->color_header;
}
if ( '' != $this->color_link ) {
	$create['options']['color_link'] = $this->color_link;
}

if ( $this->is_rest() ) {
	$create['order_amount'] = WC()->cart->total * 100;
	$create['order_tax_amount'] = WC()->cart->get_taxes_total() * 100;
	$klarna_order = new \Klarna\Rest\Checkout\Order( $connector );
} else  {
	Klarna_Checkout_Order::$baseUri = $this->klarna_server;
	Klarna_Checkout_Order::$contentType = 'application/vnd.klarna.checkout.aggregated-order-v2+json';
	$klarna_order = new Klarna_Checkout_Order( $connector );
}

$klarna_order->create( apply_filters( 'kco_create_order', $create ) );
