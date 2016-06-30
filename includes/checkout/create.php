<?php
/**
 * Creates new order in Klarna checkout page
 *
 * @package WC_Gateway_Klarna
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Start new session

// Check if country was set in WC session
// Used when changing countries in KCO page
$kco_session_country = WC()->session->get( 'klarna_country', '' );
$local_order_id      = WC()->session->get( 'ongoing_klarna_order' );
$kco_session_locale  = '';

if ( ( 'en_US' == get_locale() || 'en_GB' == get_locale() ) && 'DE' != $kco_session_country ) {
	$kco_session_locale = 'en-gb';
} elseif ( '' != $kco_session_country ) {
	if ( 'DE' == $kco_session_country ) {
		$kco_session_locale = 'de-de';
	} elseif ( 'AT' == $kco_session_country ) {
		$kco_session_locale = 'de-at';
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
$kco_locale  = ( '' != $kco_session_locale ) ? $kco_session_locale : $this->klarna_language;

$create['purchase_country']  = $kco_country;
$create['purchase_currency'] = $this->klarna_currency;
$create['locale']            = $kco_locale;

// Set Euro country session value
if ( 'eur' == strtolower( $create['purchase_currency'] ) ) {
	WC()->session->set( 'klarna_euro_country', $create['purchase_country'] );
}

if ( ! $this->is_rest() ) {
	$create['merchant']['id'] = $eid; // Only needed in V2 of API
}

// Merchant URIs
$push_uri_base = get_home_url() . '/wc-api/WC_Gateway_Klarna_Checkout/';
$order_key = get_post_meta( $local_order_id, '_order_key', true );
// REST
if ( $this->is_rest() ) {
	$merchant_terms_uri        = $this->terms_url;
	$merchant_checkout_uri     = esc_url_raw( add_query_arg( 'klarnaListener', 'checkout', $this->klarna_checkout_url ) );
	$merchant_push_uri         = add_query_arg( array(
		'sid'          => $local_order_id,
		'scountry'     => $this->klarna_country,
		'klarna_order' => '{checkout.order.id}',
		'wc-api'       => 'WC_Gateway_Klarna_Checkout',
		'klarna-api'   => 'rest'
	), $push_uri_base );
	$merchant_confirmation_uri = add_query_arg( array(
		'klarna_order'   => '{checkout.order.id}',
		'sid'            => $local_order_id,
		'order-received' => $local_order_id,
		'thankyou'       => 'yes',
		'key'            => $order_key
	), $this->klarna_checkout_thanks_url );
	$address_update_uri        = add_query_arg( array(
		'address_update' => 'yes',
		'sid'            => $local_order_id,
	), $this->klarna_checkout_url );
} else { // V2
	$merchant_terms_uri        = $this->terms_url;
	$merchant_checkout_uri     = esc_url_raw( add_query_arg( 'klarnaListener', 'checkout', $this->klarna_checkout_url ) );
	$merchant_push_uri         = add_query_arg( array(
		'sid'          => $local_order_id,
		'scountry'     => $this->klarna_country,
		'klarna_order' => '{checkout.order.id}',
		'klarna-api'   => 'v2'
	), $push_uri_base );
	$merchant_confirmation_uri = add_query_arg( array(
		'klarna_order'   => '{checkout.order.id}',
		'sid'            => $local_order_id,
		'order-received' => $local_order_id,
		'thankyou'       => 'yes',
		'key'            => $order_key
	), $this->klarna_checkout_thanks_url );
}

// Different format for V3 and V2
if ( $this->is_rest() ) {
	$merchantUrls = array(
		'terms'          => $merchant_terms_uri,
		'checkout'       => $merchant_checkout_uri,
		'confirmation'   => $merchant_confirmation_uri,
		'push'           => $merchant_push_uri,
	);
	if ( is_ssl() && 'yes' == $this->validate_stock ) {
		$merchantUrls['validation'] = get_home_url() . '/wc-api/WC_Gateway_Klarna_Order_Validate/';
	}
	if ( is_ssl() ) {
		$merchantUrls['address_update'] = $address_update_uri;
	}
	$create['merchant_urls'] = $merchantUrls;
} else {
	$create['merchant']['terms_uri']        = $merchant_terms_uri;
	$create['merchant']['checkout_uri']     = $merchant_checkout_uri;
	$create['merchant']['confirmation_uri'] = $merchant_confirmation_uri;
	$create['merchant']['push_uri']         = $merchant_push_uri;
	if ( is_ssl() && 'yes' == $this->validate_stock ) {
		$create['merchant']['validation_uri']   = get_home_url() . '/wc-api/WC_Gateway_Klarna_Order_Validate/';
	}
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

$klarna_order_total = 0;
$klarna_tax_total   = 0;
foreach ( $cart as $item ) {
	if ( $this->is_rest() ) {
		$create['order_lines'][] = $item;
		$klarna_order_total += $item['total_amount'];

		// Process sales_tax item differently
		if ( array_key_exists( 'type', $item ) && 'sales_tax' == $item['type'] ) {
			$klarna_tax_total += $item['total_amount'];
		} else {
			$klarna_tax_total += $item['total_tax_amount'];
		}
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

// Check if there's a subscription product in cart
if ( class_exists( 'WC_Subscriptions_Cart' ) && WC_Subscriptions_Cart::cart_contains_subscription() ) {
	$create['recurring'] = true;

	// Extra merchant data
	$fetched_subscription_product_id = $this->get_subscription_product_id();
	if ( $fetched_subscription_product_id ) {
		$subscription_expiration_time = WC_Subscriptions_Product::get_expiration_date( $fetched_subscription_product_id );
		if ( 0 !== $subscription_expiration_time ) {
			$end_time = date( 'Y-m-d\TH:i', strtotime( $subscription_expiration_time ) );
		} else {
			$end_time = date( 'Y-m-d\TH:i', strtotime( '+50 year' ) );
		}

		$klarna_subscription_info = array(
			'subscription_name'            => 'Subscription: ' . get_the_title( $fetched_subscription_product_id ),
			'start_time'                   => date( 'Y-m-d\TH:i' ),
			'end_time'                     => $end_time,
			'auto_renewal_of_subscription' => true
		);
		if ( get_current_user_id() ) {
			$klarna_subscription_info['customer_account_info'] = array(
				'unique_account_identifier' => (string) get_current_user_id()
			);
		}

		$klarna_subscription = array( $klarna_subscription_info );

		$body_attachment = json_encode( array(
			'subscription' => $klarna_subscription
		) );

		if ( $body_attachment ) {
			$create['attachment']['content_type'] = 'application/vnd.klarna.internal.emd-v2+json';
			$create['attachment']['body']         = $body_attachment;
		}

	}
}

if ( $this->is_rest() ) {
	$create['order_amount']     = (int) $klarna_order_total;
	$create['order_tax_amount'] = (int) $klarna_tax_total;

	// Only add shipping options if the option is unchecked for UK
	$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
	if ( 'gb' == $this->klarna_country && 'yes' == $checkout_settings['uk_ship_only_to_base'] ) {
		$create['shipping_countries'] = array();
	} else {
		// Add shipping countries
		$wc_countries                 = new WC_Countries();
		$create['shipping_countries'] = array_keys( $wc_countries->get_shipping_countries() );
	}

	if ( 'billing_only' != get_option( 'woocommerce_ship_to_destination' ) ) {
		$create['options']['allow_separate_shipping_address'] = true;
	}

	/* 
	// Add shipping options
	WC()->cart->calculate_shipping();
	$packages = WC()->shipping->get_packages();

	foreach ( $packages as $i => $package ) {
		$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
		$available_methods = $package['rates'];
		$show_package_details = sizeof( $packages ) > 1;

		if ( ! empty( $available_methods ) ) {
			if ( count( $available_methods ) > 1 ) {
				$shipping_options = array();
				$method = current( $available_methods );

				foreach ( $available_methods as $method ) {
					$preselected = ( $method->id == $chosen_method ? true : false );

					// Avoid division by zero
					if ( $method->cost == 0 ) {
						$tax_rate = 0;
					} else {
						$tax_rate = round( array_sum( $method->taxes ) / $method->cost * 100 ) * 100;
					}

					$shipping_options[] = array(
						'id'          => $method->id,
						'name'        => $method->label,
						'price'       => round( ( $method->cost + array_sum( $method->taxes ) ) * 100 ),
						'tax_amount'  => round( array_sum( $method->taxes ) * 100 ),
						'tax_rate'    => $tax_rate,
						'description' => '',
						'preselected' => $preselected
					);
				}
			}
		}
	}
	$create['shipping_options'] = $shipping_options;
	*/

	$klarna_order = new \Klarna\Rest\Checkout\Order( $connector );
} else {
	// Klarna_Checkout_Order::$baseUri = $this->klarna_server;
	// Klarna_Checkout_Order::$contentType = 'application/vnd.klarna.checkout.aggregated-order-v2+json';
	$klarna_order = new Klarna_Checkout_Order( $connector, $this->klarna_server );
}

try {
	$klarna_order->create( apply_filters( 'kco_create_order', $create ) );
	$klarna_order->fetch();
} catch ( Exception $e ) {
	if ( is_user_logged_in() && $this->debug ) {
		// The purchase was denied or something went wrong, print the message:
		echo '<div class="woocommerce-error">';
		print_r( $e->getMessage() );
		echo '</div>';
	}
}