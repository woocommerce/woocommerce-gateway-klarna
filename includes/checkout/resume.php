<?php
/**
 * Updates ongoing order in Klarna checkout page
 *
 * @package WC_Gateway_Klarna
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Resume session
if ( $this->is_rest() ) {
	$klarna_order = new \Klarna\Rest\Checkout\Order( $connector, WC()->session->get( 'klarna_checkout' ) );
} else {
	$klarna_order = new Klarna_Checkout_Order( $connector, WC()->session->get( 'klarna_checkout' ) );
}
$local_order_id = WC()->session->get( 'ongoing_klarna_order' );
$kco_session_country = WC()->session->get( 'klarna_country', '' );

try {
	$klarna_order->fetch();

	// Reset session if the country in the store has changed since last time the checkout was loaded
	if ( strtolower( $this->klarna_country ) != strtolower( $klarna_order['purchase_country'] ) ) {
		// Reset session
		$klarna_order = null;
		WC()->session->__unset( 'klarna_checkout' );
		WC()->session->__unset( 'klarna_checkout_country' );
	} else {
		/**
		 * Update Klarna order
		 */

		// Reset cart
		$klarna_order_total = 0;
		$klarna_tax_total   = 0;
		foreach ( $cart as $item ) {
			if ( $this->is_rest() ) {
				$update['order_lines'][] = $item;
				$klarna_order_total += $item['total_amount'];

				// Process sales_tax item differently
				if ( array_key_exists( 'type', $item ) && 'sales_tax' == $item['type'] ) {
					$klarna_tax_total += $item['total_amount'];
				} else {
					$klarna_tax_total += $item['total_tax_amount'];
				}
			} else {
				$update['cart']['items'][] = $item;
			}
		}

		// Colors
		if ( '' != $this->color_button ) {
			$update['options']['color_button'] = $this->color_button;
		}
		if ( '' != $this->color_button_text ) {
			$update['options']['color_button_text'] = $this->color_button_text;
		}
		if ( '' != $this->color_checkbox ) {
			$update['options']['color_checkbox'] = $this->color_checkbox;
		}
		if ( '' != $this->color_checkbox_checkmark ) {
			$update['options']['color_checkbox_checkmark'] = $this->color_checkbox_checkmark;
		}
		if ( '' != $this->color_header ) {
			$update['options']['color_header'] = $this->color_header;
		}
		if ( '' != $this->color_link ) {
			$update['options']['color_link'] = $this->color_link;
		}

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

		// Update the order WC id
		$kco_country = ( '' != $kco_session_country ) ? $kco_session_country : $this->klarna_country;
		$kco_locale  = ( '' != $kco_session_locale ) ? $kco_session_locale : $this->klarna_language;

		$update['purchase_country']  = $kco_country;
		$update['purchase_currency'] = $this->klarna_currency;
		$update['locale']            = $kco_locale;

		// Set Euro country session value
		if ( 'eur' == strtolower( $update['purchase_currency'] ) ) {
			WC()->session->set( 'klarna_euro_country', $update['purchase_country'] );
		}

		$update['merchant']['id'] = $eid;

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
			$merchantUrls            = array(
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
			$update['merchant_urls'] = $merchantUrls;
		} else {
			$update['merchant']['terms_uri']        = $merchant_terms_uri;
			$update['merchant']['checkout_uri']     = $merchant_checkout_uri;
			$update['merchant']['confirmation_uri'] = $merchant_confirmation_uri;
			$update['merchant']['push_uri']         = $merchant_push_uri;
			if ( is_ssl() && 'yes' == $this->validate_stock ) {
				$update['merchant']['validation_uri']   = get_home_url() . '/wc-api/WC_Gateway_Klarna_Order_Validate/';
			}
		}

		// Customer info if logged in
		if ( $this->testmode !== 'yes' && is_user_logged_in() ) {
			if ( $current_user->user_email ) {
				$update['shipping_address']['email'] = $current_user->user_email;
			}

			if ( $woocommerce->customer->get_shipping_postcode() ) {
				$update['shipping_address']['postal_code'] = $woocommerce->customer->get_shipping_postcode();
			}
		}

		if ( $this->is_rest() ) {
			$update['order_amount']     = (int) $klarna_order_total;
			$update['order_tax_amount'] = (int) $klarna_tax_total;

			// Only add shipping options if the option is unchecked for UK
			$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
			if ( 'gb' == $this->klarna_country && 'yes' == $checkout_settings['uk_ship_only_to_base'] ) {
				$update['shipping_countries'] = array();
			} else {
				$wc_countries                 = new WC_Countries();
				$update['shipping_countries'] = array_keys( $wc_countries->get_shipping_countries() );
			}

			if ( 'billing_only' != get_option( 'woocommerce_ship_to_destination' ) ) {
				$update['options']['allow_separate_shipping_address'] = true;
			} else {
				$update['options']['allow_separate_shipping_address'] = false;
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
								'id' => $method->id,
								'name' => $method->label,
								'price' => round( ( $method->cost + array_sum( $method->taxes ) ) * 100 ),
								'tax_amount' => round( array_sum( $method->taxes ) * 100 ),
								'tax_rate' => $tax_rate,
								'description' => '',
								'preselected' => $preselected
							);
						}
					}
				}
			}
			$update['shipping_options'] = $shipping_options;
			*/
		}

		$klarna_order->update( apply_filters( 'kco_update_order', $update ) );

	} // End if country change
} catch ( Exception $e ) {
	if ( is_user_logged_in() && $this->debug ) {
		// Something went wrong, print the message:
		echo '<div class="woocommerce-error">';
		print_r( $e->getMessage() );
		echo '</div>';
	}
	// Reset session
	$klarna_order = null;
	WC()->session->__unset( 'klarna_checkout' );
	WC()->session->__unset( 'klarna_checkout_country' );
}