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
	if ( 'nl' === $kco_session_country ) {
		$kco_session_locale = 'en-nl';
	} else {
		if ( 'en_US' == get_locale() ) {
			$kco_session_locale = 'en-US';
		} else {
			$kco_session_locale = 'en-gb';
		}
	}
} elseif ( '' != $kco_session_country ) {
	if ( 'nl' === $kco_session_country ) {
		$kco_session_locale = 'nl-nl';
	} elseif ( 'DE' == $kco_session_country ) {
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

$kco_country = ( '' !== $kco_session_country ) ? $kco_session_country : $kco_klarna_country;
$kco_locale  = ( '' !== $kco_session_locale ) ? $kco_session_locale : $kco_klarna_language;

if ( $kco_is_rest ) {
	$kco_currency = strtolower( get_woocommerce_currency() );
	$kco_country  = strtolower( $kco_country );
} else {
	$kco_currency = get_woocommerce_currency();
}

$create['purchase_country']  = $kco_country;
$create['purchase_currency'] = $kco_currency;
$create['locale']            = $kco_locale;

// Set Euro country session value
if ( 'eur' == strtolower( $create['purchase_currency'] ) ) {
	WC()->session->set( 'klarna_euro_country', $create['purchase_country'] );
}

if ( ! $kco_is_rest ) {
	$create['merchant']['id'] = $eid; // Only needed in V2 of API
}

// Merchant URIs
$push_uri_base = get_home_url() . '/wc-api/WC_Gateway_Klarna_Checkout/';
$order_key     = get_post_meta( $local_order_id, '_order_key', true );
// REST
if ( $kco_is_rest ) {
	$merchant_terms_uri        = $kco_terms_url;
	$merchant_checkout_uri     = esc_url_raw( add_query_arg( 'klarnaListener', 'checkout', $kco_klarna_checkout_url ) );
	$merchant_push_uri         = add_query_arg(
		array(
			'sid'          => $local_order_id,
			'scountry'     => $kco_klarna_country,
			'klarna_order' => '{checkout.order.id}',
			'wc-api'       => 'WC_Gateway_Klarna_Checkout',
			'klarna-api'   => 'rest',
		), $push_uri_base
	);
	$merchant_confirmation_uri = add_query_arg(
		array(
			'klarna_order'   => '{checkout.order.id}',
			'sid'            => $local_order_id,
			'scountry'       => $kco_klarna_country,
			'order-received' => $local_order_id,
			'thankyou'       => 'yes',
			'key'            => $order_key,
		), $kco_klarna_checkout_thank_you_url
	);
	$address_update_uri        = add_query_arg(
		array(
			'address_update' => 'yes',
			'sid'            => $local_order_id,
		), $kco_klarna_checkout_url
	);
} else { // V2
	$merchant_terms_uri        = $kco_terms_url;
	$merchant_checkout_uri     = esc_url_raw( add_query_arg( 'klarnaListener', 'checkout', $kco_klarna_checkout_url ) );
	$merchant_push_uri         = add_query_arg(
		array(
			'sid'          => $local_order_id,
			'scountry'     => $kco_klarna_country,
			'klarna_order' => '{checkout.order.id}',
			'klarna-api'   => 'v2',
		), $push_uri_base
	);
	$merchant_confirmation_uri = add_query_arg(
		array(
			'klarna_order'   => '{checkout.order.id}',
			'sid'            => $local_order_id,
			'scountry'       => $kco_klarna_country,
			'order-received' => $local_order_id,
			'thankyou'       => 'yes',
			'key'            => $order_key,
		), $kco_klarna_checkout_thank_you_url
	);
}

// Different format for V3 and V2
if ( $kco_is_rest ) {
	$merchantUrls = array(
		'terms'        => $merchant_terms_uri,
		'checkout'     => $merchant_checkout_uri,
		'confirmation' => $merchant_confirmation_uri,
		'push'         => $merchant_push_uri,
	);
	if ( is_ssl() && 'yes' === $kco_validate_stock ) {
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
	if ( is_ssl() && 'yes' === $kco_validate_stock ) {
		$create['merchant']['validation_uri'] = get_home_url() . '/wc-api/WC_Gateway_Klarna_Order_Validate/';
	}
	if ( $kco_cancellation_terms_url ) {
		$create['merchant']['cancellation_terms_uri'] = $kco_cancellation_terms_url;
	}
}

// Make phone a mandatory field for German stores?
if ( 'yes' === $kco_phone_mandatory_de ) {
	$create['options']['phone_mandatory'] = true;
}

// Enable DHL packstation feature for German stores?
if ( 'yes' === $kco_dhl_packstation_de ) {
	$create['options']['packstation_enabled'] = true;
}

// Customer info if logged in
if ( $kco_testmode !== 'yes' ) {
	if ( $current_user->user_email ) {
		$create['shipping_address']['email'] = $current_user->user_email;
	}

	if ( WC()->customer->get_shipping_postcode() ) {
		$create['shipping_address']['postal_code'] = WC()->customer->get_shipping_postcode();
	}
}

$create['gui']['layout'] = $klarna_checkout_layout;
if ( wp_is_mobile() ) {
	$create['gui']['options'] = array( 'disable_autofocus' );
}

$klarna_order_total = 0;
$klarna_tax_total   = 0;
foreach ( $cart as $item ) {
	if ( $kco_is_rest ) {
		$create['order_lines'][] = $item;
		$klarna_order_total     += $item['total_amount'];

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
if ( '' !== $kco_color_options['color_button'] ) {
	$create['options']['color_button'] = $kco_color_options['color_button'];
}
if ( '' !== $kco_color_options['color_button_text'] ) {
	$create['options']['color_button_text'] = $kco_color_options['color_button_text'];
}
if ( '' !== $kco_color_options['color_checkbox'] ) {
	$create['options']['color_checkbox'] = $kco_color_options['color_checkbox'];
}
if ( '' !== $kco_color_options['color_checkbox_checkmark'] ) {
	$create['options']['color_checkbox_checkmark'] = $kco_color_options['color_checkbox_checkmark'];
}
if ( '' !== $kco_color_options['color_header'] ) {
	$create['options']['color_header'] = $kco_color_options['color_header'];
}
if ( '' !== $kco_color_options['color_link'] ) {
	$create['options']['color_link'] = $kco_color_options['color_link'];
}

// Customer types
if ( 'SE' == $kco_klarna_country || 'NO' == $kco_klarna_country || 'FI' == $kco_klarna_country ) {

	// B2B purchases is not available for subscriptions. allowed_customer_types defaults to person.
	// Just don't send allowed_customer_types if the order contains a subscription.
	if ( class_exists( 'WC_Subscriptions_Cart' ) && WC_Subscriptions_Cart::cart_contains_subscription() ) {
		// The sound of one hand clapping
	} else {
		if ( 'B2B' == $kco_allowed_customer_types ) {
			$create['options']['allowed_customer_types'] = array( 'organization' );
		} elseif ( 'B2BC' == $kco_allowed_customer_types ) {
			$create['options']['allowed_customer_types'] = array( 'person', 'organization' );
			$create['customer']['type']                  = 'organization';
		} elseif ( 'B2CB' == $kco_allowed_customer_types ) {
			$create['options']['allowed_customer_types'] = array( 'person', 'organization' );
			$create['customer']['type']                  = 'person';
		} else {
			$create['options']['allowed_customer_types'] = array( 'person' );
		}
	}
}

// Check if there's a subscription product in cart
if ( class_exists( 'WC_Subscriptions_Cart' ) && WC_Subscriptions_Cart::cart_contains_subscription() ) {
	$create['recurring'] = true;

	// Extra merchant data
	$subscription_product_id = false;
	if ( ! empty( $woocommerce->cart->cart_contents ) ) {
		foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
			if ( WC_Subscriptions_Product::is_subscription( $cart_item['product_id'] ) ) {
				$subscription_product_id = $cart_item['product_id'];
				break;
			}
		}
	}

	if ( $subscription_product_id ) {
		$subscription_expiration_time = WC_Subscriptions_Product::get_expiration_date( $subscription_product_id );
		if ( 0 !== $subscription_expiration_time ) {
			$end_time = date( 'Y-m-d\TH:i', strtotime( $subscription_expiration_time ) );
		} else {
			$end_time = date( 'Y-m-d\TH:i', strtotime( '+50 year' ) );
		}

		$klarna_subscription_info = array(
			'subscription_name'            => 'Subscription: ' . get_the_title( $subscription_product_id ),
			'start_time'                   => date( 'Y-m-d\TH:i' ),
			'end_time'                     => $end_time,
			'auto_renewal_of_subscription' => true,
		);
		if ( get_current_user_id() ) {
			$klarna_subscription_info['customer_account_info'] = array(
				'unique_account_identifier' => (string) get_current_user_id(),
			);
		}

		$klarna_subscription = array( $klarna_subscription_info );

		$body_attachment = json_encode(
			array(
				'subscription' => $klarna_subscription,
			)
		);

		if ( $body_attachment ) {
			$create['attachment']['content_type'] = 'application/vnd.klarna.internal.emd-v2+json';
			$create['attachment']['body']         = $body_attachment;
		}
	}
}

if ( $kco_is_rest ) {
	$create['order_amount']     = (int) $klarna_order_total;
	$create['order_tax_amount'] = (int) $klarna_tax_total;

	// Only add shipping options if the option is unchecked for UK
	$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
	if ( 'gb' == $kco_klarna_country && 'yes' == $checkout_settings['uk_ship_only_to_base'] ) {
		$create['shipping_countries'] = array();
	} elseif ( 'nl' == $kco_klarna_country ) {
		$create['shipping_countries'] = array( 'NL' );
	} else {
		// Add shipping countries
		$wc_countries                 = new WC_Countries();
		$create['shipping_countries'] = array_keys( $wc_countries->get_shipping_countries() );
	}

	if ( 'billing_only' != get_option( 'woocommerce_ship_to_destination' ) ) {
		$create['options']['allow_separate_shipping_address'] = true;
	}

	$klarna_order = new \Klarna\Rest\Checkout\Order( $connector );
} else {

	// Allow separate shipping address
	if ( 'yes' === $kco_allow_separate_shipping ) {
		$create['options']['allow_separate_shipping_address'] = true;
	}

	$klarna_order = new Klarna_Checkout_Order( $connector, $kco_klarna_server );
}

WC_Gateway_Klarna::log( 'Create request order data: ' . var_export( $create, true ) );

try {
	$klarna_order->create( apply_filters( 'kco_create_order', $create ) );
	$klarna_order->fetch();
} catch ( Exception $e ) {
	if ( 'yes' === $kco_debug ) {
		// $kco_log->add( 'klarna', 'Klarna API error: ' . $e->getCode() . ' - ' . $e->getMessage() );
	}

	if ( is_user_logged_in() && $kco_debug ) {
		// The purchase was denied or something went wrong, print the message:
		echo '<div class="kco-error woocommerce-error">';
		echo esc_html( $e->getCode() . ' - ' . $e->getMessage() );
		echo '</div>';
	}
}
