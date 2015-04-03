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

/**
 * Don't render the Klarna Checkout form if the payment gateway isn't enabled.
 */
if ( $this->enabled != 'yes' ) {
	return;
}


/**
 * If no Klarna country is set - return.
 */
if ( empty( $this->klarna_country ) ) {
	echo apply_filters(
		'klarna_checkout_wrong_country_message', 
		sprintf( 
			__( 'Sorry, you can not buy via Klarna Checkout from your country or currency. Please <a href="%s">use another payment method</a>. ', 'klarna' ),
			get_permalink( get_option( 'woocommerce_checkout_page_id' ) )
		) 
	);

	return;
}


/**
 * If checkout registration is disabled and not logged in, the user cannot checkout
 */
global $woocommerce;
$checkout = $woocommerce->checkout();
if ( ! $checkout->enable_guest_checkout && ! is_user_logged_in() ) {
	echo apply_filters( 
		'woocommerce_checkout_must_be_logged_in_message',
		__( 'You must be logged in to checkout.', 'woocommerce' ) 
	);
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
 * If the WooCommerce terms page or the Klarna Checkout settings field 
 * Terms Page isn't set, do nothing.
 */
if ( empty( $this->terms_url ) ) {
	return;
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
	$klarna_wc = $woocommerce;
	$klarna_transient = md5( time() . rand( 1000, 1000000 ) );
	set_transient( $klarna_transient, $klarna_wc, 48 * 60 * 60 );
	

	/**
	 * Process cart contents
	 */
	if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) {

		foreach ( $woocommerce->cart->get_cart() as $cart_item ) {

			if ( $cart_item['quantity'] ) {

				$_product = wc_get_product( $cart_item['product_id'] );

				// We manually calculate the tax percentage here
				if ( $_product->is_taxable() && $cart_item['line_subtotal_tax'] > 0 ) {
					// Calculate tax percentage
					$item_tax_percentage = round( $cart_item['line_subtotal_tax'] / $cart_item['line_subtotal'], 2 ) * 100;
				} else {
					$item_tax_percentage = 00;
				}

				$cart_item_data = $cart_item['data'];
				$cart_item_name = $cart_item_data->post->post_title;

				$item_meta = new WC_Order_Item_Meta( $item['item_meta'] );
				if ( $meta = $item_meta->display( true, true ) ) {
					$item_name .= ' ( ' . $meta . ' )';
				}
					
				// apply_filters to item price so we can filter this if needed
				$klarna_item_price_including_tax = $cart_item['line_subtotal'] + $cart_item['line_subtotal_tax'];
				$item_price = apply_filters( 'klarna_item_price_including_tax', $klarna_item_price_including_tax );

				// Get SKU or product id
				$reference = '';
				if ( $_product->get_sku() ) {
					$reference = $_product->get_sku();
				} elseif ( $_product->variation_id ) {
					$reference = $_product->variation_id;
				} else {
					$reference = $_product->id;
				}

				// Check if there's a discount applied
				if ( $cart_item['line_subtotal'] > $cart_item['line_total'] ) {
					$item_discount = round( 1 - ( $cart_item['line_total'] / $cart_item['line_subtotal'] ), 2 ) * 10000;
				} else {
					$item_discount = 0;
				}

				$item_price = number_format( $item_price * 100, 0, '', '' ) / $cart_item['quantity'];
				
				$cart = array(
					array(
						'reference'      => strval( $reference ),
						'name'           => strip_tags( $cart_item_name ),
						'quantity'       => (int) $cart_item['quantity'],
						'unit_price'     => (int) $item_price,
						'discount_rate'  => $item_discount,
						'tax_rate'       => intval( $item_tax_percentage . '00' )
					)
				);

			} // End if qty

		} // End foreach

	} // End if sizeof get_items()


	/**
	 * Process shipping
	 */
	if ( $woocommerce->cart->shipping_total > 0 ) {

		// We manually calculate the tax percentage here
		if ( $woocommerce->cart->shipping_tax_total > 0 ) {
			// Calculate tax percentage
			$shipping_tax_percentage = round( $woocommerce->cart->shipping_tax_total / $woocommerce->cart->shipping_total, 2 ) * 100;
		} else {
			$shipping_tax_percentage = 00;
		}

		$shipping_price = number_format( ( $woocommerce->cart->shipping_total + $woocommerce->cart->shipping_tax_total ) * 100, 0, '', '' );

		// Get shipping method name				
		$shipping_packages = WC()->shipping->get_packages();
		foreach ( $shipping_packages as $i => $package ) {
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			if ( '' != $chosen_method ) {
			
				$package_rates = $package['rates'];
				foreach ( $package_rates as $rate_key => $rate_value ) {
					if ( $rate_key == $chosen_method ) {
						$klarna_shipping_method = $rate_value->label;
					}
				}

			}

		}
		if ( ! isset( $klarna_shipping_method ) ) {
			$klarna_shipping_method = __( 'Shipping', 'klarna' );
		}

		$cart[] = array(  
			'type'       => 'shipping_fee',
			'reference'  => 'SHIPPING',
			'name'       => $klarna_shipping_method,
			'quantity'   => 1,
			'unit_price' => (int) $shipping_price,
			'tax_rate'   => intval( $shipping_tax_percentage . '00' )
		);

	}

	/**
	 * Process discount
	 */
	/*
	if ( $woocommerce->cart->discount_cart > 0 ) {

		$klarna_order_discount = (int) number_format( $woocommerce->cart->discount_cart, 2, '', '' );

		$cart[] = array(    
			'reference'   => 'DISCOUNT',  
			'name'        => __( 'Discount', 'klarna' ),  
			'quantity'    => 1,  
			'unit_price'  => -$klarna_order_discount,  
			'tax_rate'    => 0  
		);

	}
	*/

	// Merchant ID
	$eid = $this->klarna_eid;

	// Shared secret
	$sharedSecret = $this->klarna_secret;

	Klarna_Checkout_Order::$baseUri = $this->klarna_server;
	Klarna_Checkout_Order::$contentType = 'application/vnd.klarna.checkout.aggregated-order-v2+json';

	$connector = Klarna_Checkout_Connector::create($sharedSecret);

	$klarna_order = null;

	
	/**
	 * Check if Klarna order already exists
	 *
	 * If it does, see if it needs to be updated
	 * If it doesn't, create Klarna order
	 */
	if ( array_key_exists( 'klarna_checkout', $_SESSION ) ) {

		// Resume session
		$klarna_order = new Klarna_Checkout_Order(
			$connector,
			$_SESSION['klarna_checkout']
		);

		try {

			$klarna_order->fetch();
			$klarna_order_as_array = $klarna_order->marshal();

			// Reset session if the country in the store has changed since last time the checkout was loaded
			if ( $this->klarna_country != $klarna_order_as_array['purchase_country'] ) {
				
				// Reset session
				$klarna_order = null;
				unset( $_SESSION['klarna_checkout'] );
				
			} else {

				/**
				 * Update Klarna order
				 */
				
				// Reset cart
				$update['cart']['items'] = array();
				foreach ( $woocommerce->cart->get_cart() as $item ) {
			    	$update['cart']['items'][] = $item;
				}

				// Update the order WC id
				$update['purchase_country']             = $this->klarna_country;
				$update['purchase_currency']            = $this->klarna_currency;
				$update['locale']                       = $this->klarna_language;
				$update['merchant']['id']               = $eid;
				$update['merchant']['terms_uri']        = $this->terms_url;
				$update['merchant']['checkout_uri']     = add_query_arg( 'klarnaListener', 'checkout', $this->klarna_checkout_url );
				$update['merchant']['confirmation_uri'] = add_query_arg ( array('klarna_order' => '{checkout.order.uri}', 'sid' => $klarna_transient, 'order-received' => $klarna_transient ), $this->klarna_checkout_thanks_url);
				$update['merchant']['push_uri']         = add_query_arg( array( 'sid' => $klarna_transient, 'scountry' => $this->klarna_country, 'klarna_order' => '{checkout.order.uri}', 'wc-api' => 'WC_Gateway_Klarna_Checkout'), $this->klarna_checkout_url );


				// Customer info if logged in
				if ( $this->testmode !== 'yes' ) {

					if ( $current_user->user_email ) {
						$update['shipping_address']['email'] = $current_user->user_email;
					}

					if ( $woocommerce->customer->get_shipping_postcode() ) {
						$update['shipping_address']['postal_code'] = $woocommerce->customer->get_shipping_postcode();
					}
					
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
	 * Update Klarna order
	 */
	if ( $klarna_order == null ) {

		// Start new session
		$create['purchase_country']             = $this->klarna_country;
		$create['purchase_currency']            = $this->klarna_currency;
		$create['locale']                       = $this->klarna_language;
		$create['merchant']['id']               = $eid;
		$create['merchant']['terms_uri']        = $this->terms_url;
		$create['merchant']['checkout_uri']     = add_query_arg( 'klarnaListener', 'checkout', $this->klarna_checkout_url );
		$create['merchant']['confirmation_uri'] = add_query_arg ( array('klarna_order' => '{checkout.order.uri}', 'sid' => $klarna_transient, 'order-received' => $klarna_transient ), $this->klarna_checkout_thanks_url);
		$create['merchant']['push_uri']         = add_query_arg( array('sid' => $klarna_transient, 'scountry' => $this->klarna_country, 'klarna_order' => '{checkout.order.uri}', 'wc-api' => 'WC_Gateway_Klarna_Checkout'), $this->klarna_checkout_url );

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
			$create['cart']['items'][] = $item;
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

		$klarna_order = new Klarna_Checkout_Order( $connector );
		$klarna_order->create( apply_filters( 'kco_create_order', $create ) );
		$klarna_order->fetch();

	}

	// Store location of checkout session
	$_SESSION['klarna_checkout'] = $sessionId = $klarna_order->getLocation();

	// Display checkout
	$snippet = $klarna_order['gui']['snippet'];

	do_action( 'klarna_before_kco_checkout', $klarna_transient );
	echo '<div>' . apply_filters( 'klarna_kco_checkout', $snippet ) . '</div>';
	do_action( 'klarna_after_kco_checkout', $klarna_transient );

} // End if sizeof cart 