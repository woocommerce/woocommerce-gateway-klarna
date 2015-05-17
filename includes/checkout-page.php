<?php
/**
 * Displays Klarna checkout page
 *
 * @package WC_Gateway_Klarna
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


WC()->session->set('12345','12345');
print_r( WC()->session->get('12345'));
WC()->session->__unset('12345');
print_r( WC()->session->get('12345'));

// Check if iframe needs to be displayed
if ( ! $this->show_kco() ) {
	return;
}


/**
 * Process order via Klarna Checkout page
 */
if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
	define( 'WOOCOMMERCE_CHECKOUT', true );
}


/**
 * Set Klarna Checkout as the choosen payment method in the WC session
 */
WC()->session->set( 'chosen_payment_method', 'klarna_checkout' );


/**
 * Debug
 */
if ( $this->debug == 'yes' ) {
	$this->log->add( 'klarna', 'Rendering Checkout page...' );
}


/**
 * Mobile or desktop browser
 */
if ( wp_is_mobile() ) {
	$klarna_checkout_layout = 'mobile';
} else {
	$klarna_checkout_layout = 'desktop';
}


/**
 * Set $add_klarna_window_size_script to true so that Window size 
 * detection script can load in the footer
 */
global $add_klarna_window_size_script;
$add_klarna_window_size_script = true;


/**
 * Add button to Standard Checkout Page if this is enabled in the settings
 */
if ( $this->add_std_checkout_button == 'yes' ) {
	echo '<div class="woocommerce"><a href="' . get_permalink( get_option( 'woocommerce_checkout_page_id' ) ) . '" class="button std-checkout-button">' . $this->std_checkout_button_label . '</a></div>';
}


/**
 * Recheck cart items so that they are in stock
 */
$result = $woocommerce->cart->check_cart_item_stock();
if ( is_wp_error( $result ) ) {
	return $result->get_error_message();
	exit();
}

/**
 * Check if there's anything in the cart
 */
if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) {

	/**
	 * Store WC object as transient
	 */ 
	$klarna_wc = WC();
	$klarna_transient = md5( time() . rand( 1000, 1000000 ) );
	set_transient( $klarna_transient, $klarna_wc, 48 * 60 * 60 );
	WC()->session->set( 'klarna_sid', $klarna_transient );
	
	// Process cart contents and prepare them for Klarna
	$cart = $this->cart_to_klarna();

	// Merchant ID
	$eid = $this->klarna_eid;

	// Shared secret
	$sharedSecret = $this->klarna_secret;

	// Initiate Klarna
	if ( $this->is_rest() ) {
		require_once( KLARNA_LIB . 'vendor/autoload.php' );
		$connector = Klarna\Rest\Transport\Connector::create(
		    $eid,
		    $sharedSecret,
		    Klarna\Rest\Transport\ConnectorInterface::TEST_BASE_URL
		);
	} else {
		require_once( KLARNA_LIB . '/src/Klarna/Checkout.php' );
		$connector = Klarna_Checkout_Connector::create( $sharedSecret );
	}
	$klarna_order = null;

	// unset( $_SESSION['klarna_checkout'] );
	
	/**
	 * Check if Klarna order already exists
	 *
	 * If it does, see if it needs to be updated
	 * If it doesn't, create Klarna order
	 */
	if ( array_key_exists( 'klarna_checkout', $_SESSION ) ) {

		// Resume session
		if ( $this->is_rest() ) {
			$klarna_order = new \Klarna\Rest\Checkout\Order(
				$connector, 
				$_SESSION['klarna_checkout']
			);
		} else {
			$klarna_order = new Klarna_Checkout_Order(
				$connector,
				$_SESSION['klarna_checkout']
			);
		}

		try {

			$klarna_order->fetch();
			if ( ! $this->is_rest() ) {
				$klarna_order_as_array = $klarna_order->marshal();
			}

			// Reset session if the country in the store has changed since last time the checkout was loaded
			if ( ! $this->is_rest() ) {
				if ( strtolower( $this->klarna_country ) != strtolower( $klarna_order_as_array['purchase_country'] ) ) {
					// Reset session
					$klarna_order = null;
					unset( $_SESSION['klarna_checkout'] );
				}	
			} else {

				/**
				 * Update Klarna order
				 */
				
				// Reset cart
				foreach ( $cart as $item ) {
					if ( $this->is_rest() ) {
						$update['order_lines'][] = $item;				
					} else {
						$update['cart']['items'][] = $item;				
					}
				}

				// Update the order WC id
				$kco_country = $this->klarna_country;
				$kco_locale = $this->klarna_language;

				$update['purchase_country'] = $kco_country;
				$update['purchase_currency'] = $this->klarna_currency;
				$update['locale'] = $kco_locale;

				$update['merchant']['id']= $eid;

				//
				// Merchant URIs
				//
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
				$merchant_push_uri = add_query_arg( 
					array(
						'sid' => $klarna_transient, 
						'scountry' => $this->klarna_country, 
						'klarna_order' => '{checkout.order.uri}', 
						'wc-api' => 'WC_Gateway_Klarna_Checkout'
					),
					$this->klarna_checkout_url 
				);

				// Different format for V3 and V2
				if ( $this->is_rest() ) {
					$merchantUrls = array(
						'terms' =>        $merchant_terms_uri,
						'checkout' =>     $merchant_checkout_uri,
						'confirmation' => $merchant_confirmation_uri,
						'push' =>         $merchant_push_uri
					);
					$update['merchant_urls'] = $merchantUrls;
				} else {
					$update['merchant']['terms_uri'] =        $merchant_terms_uri;
					$update['merchant']['checkout_uri'] =     $merchant_checkout_uri;
					$update['merchant']['confirmation_uri'] = $merchant_confirmation_uri;
					$update['merchant']['push_uri'] =         $merchant_push_uri;
				}

				// Customer info if logged in
				if ( is_user_logged_in() ) {

					if ( $current_user->user_email ) {
						$update['shipping_address']['email'] = $current_user->user_email;
					}

					if ( $woocommerce->customer->get_shipping_postcode() ) {
						$update['shipping_address']['postal_code'] = $woocommerce->customer->get_shipping_postcode();
					}
					
				}

				if ( $this->is_rest() ) {
					$update['order_amount'] = WC()->cart->total * 100;
					$update['order_tax_amount'] = WC()->cart->get_taxes_total() * 100;
				}

				$klarna_order->update( apply_filters( 'kco_update_order', $update ) );

			} // End if country change

		} catch ( Exception $e ) {
			// Reset session
			$klarna_order = null;
			unset( $_SESSION['klarna_checkout'] );
		}

	}


	/**
	 * Create Klarna order
	 */
	if ( $klarna_order == null ) {

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

		//
		// Merchant URIs
		//
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
			$klarna_order = new \Klarna\Rest\Checkout\Order( $connector );
			$create['order_amount'] = WC()->cart->total * 100;
			$create['order_tax_amount'] = WC()->cart->get_taxes_total() * 100;
		} else  {
			Klarna_Checkout_Order::$baseUri = $this->klarna_server;
			Klarna_Checkout_Order::$contentType = 'application/vnd.klarna.checkout.aggregated-order-v2+json';
			$klarna_order = new Klarna_Checkout_Order( $connector );
		}

		$klarna_order->create( apply_filters( 'kco_create_order', $create ) );

	}

	// Store location of checkout session
	$_SESSION['klarna_checkout'] = $sessionId = $klarna_order->getLocation();

	$klarna_order->fetch();

	// Display checkout
	do_action( 'klarna_before_kco_checkout' );
	if ( $this->is_rest() ) {
		$snippet = $klarna_order['html_snippet'];
	} else {
		$snippet = $klarna_order['gui']['snippet'];
	}
	echo '<div>' . apply_filters( 'klarna_kco_checkout', $snippet ) . '</div>';
	do_action( 'klarna_after_kco_checkout' );

} // End if sizeof cart 