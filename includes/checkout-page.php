<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display Klarna Checkout page
 */

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
if ( $this->debug=='yes' ) {
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
	echo '<div class="woocommerce"><a href="' . get_permalink( get_option('woocommerce_checkout_page_id') ) . '" class="button std-checkout-button">' . $this->std_checkout_button_label . '</a></div>';
}


if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) {

	// Create a new order
	$order_id = $this->create_order();

	// Check that the order doesnt contain an error message (from check_cart_item_stock() 
	// fired in create_order())
	if ( ! is_numeric( $order_id ) ) {
		echo '<ul class="woocommerce-error"><li>' . __( $order_id, 'woocommerce' ) . '</li></ul>';
		exit();
	}

	do_action( 'woocommerce_checkout_order_processed', $order_id, false );

	// Store Order ID in session so it can be re-used if customer navigates away from the checkout and then return again
	$woocommerce->session->order_awaiting_payment = $order_id;

	// Get an instance of the created order
	$order = WC_Klarna_Compatibility::wc_get_order( $order_id );			
	$cart = array();

	// Cart Contents
	if ( sizeof( $order->get_items() ) > 0 ) {

		foreach ( $order->get_items() as $item ) {

			if ( $item['qty'] ) {

				$_product = $order->get_product_from_item( $item );	

				// We manually calculate the tax percentage here
				if ( $_product->is_taxable() && $order->get_line_tax($item) > 0 ) {
					// Calculate tax percentage
					$item_tax_percentage = round($order->get_item_tax( $item, false) / $order->get_item_total( $item, false, false ), 2)*100;
				} else {
					$item_tax_percentage = 00;
				}

				$item_name 	= $item['name'];

				$item_meta = new WC_Order_Item_Meta( $item['item_meta'] );
				if ( $meta = $item_meta->display( true, true ) )
					$item_name .= ' ( ' . $meta . ' )';
					
				// apply_filters to item price so we can filter this if needed
				$klarna_item_price_including_tax = $order->get_item_total( $item, true );
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

				$item_price = number_format( $order->get_item_total( $item, true ) * 100, 0, '', '' );
				$cart[] = array(
					'reference'      => strval( $reference ),
					'name'           => strip_tags( $item_name ),
					'quantity'       => (int) $item['qty'],
					'unit_price'     => (int) $item_price,
					'discount_rate'  => 0,
					'tax_rate'       => intval( $item_tax_percentage . '00' )
				);

			} // End if qty

		} // End foreach

	} // End if sizeof get_items()


	// Shipping
	if ( $order->get_total_shipping() > 0 ) {

		// We manually calculate the tax percentage here
		if ( $order->get_total_shipping() > 0 ) {
			// Calculate tax percentage
			$shipping_tax_percentage = round( $order->get_shipping_tax() / $order->get_total_shipping(), 2 ) * 100;
		} else {
			$shipping_tax_percentage = 00;
		}

		$shipping_price = number_format( ( $order->get_total_shipping() + $order->get_shipping_tax() ) * 100, 0, '', '' );

		$cart[] = array(  
			'type'       => 'shipping_fee',
			'reference'  => 'SHIPPING',
			'name'       => $order->get_shipping_method(),
			'quantity'   => 1,
			'unit_price' => (int)$shipping_price,
			'tax_rate'   => intval($shipping_tax_percentage . '00')
		);

	}

	// Discount
	if ( $order->order_discount > 0 ) {

		$klarna_order_discount = (int) number_format( $order->order_discount, 2, '', '' );

		$cart[] = array(    
			'reference'   => 'DISCOUNT',  
			'name'        => __( 'Discount', 'klarna' ),  
			'quantity'    => 1,  
			'unit_price'  => -$klarna_order_discount,  
			'tax_rate'    => 0  
		);

	}

	// Merchant ID
	$eid = $this->klarna_eid;

	// Shared secret
	$sharedSecret = $this->klarna_secret;

	Klarna_Checkout_Order::$baseUri = $this->klarna_server;
	Klarna_Checkout_Order::$contentType = 'application/vnd.klarna.checkout.aggregated-order-v2+json';

	$connector = Klarna_Checkout_Connector::create($sharedSecret);

	$klarna_order = null;

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
				unset($_SESSION['klarna_checkout']);
				
			} else {

				// Update order
				
				// Reset cart
				$update['cart']['items'] = array();
				foreach ($cart as $item) {
			    	$update['cart']['items'][] = $item;
				}

				// Update the order WC id
				$update['purchase_country']             = $this->klarna_country;
				$update['purchase_currency']            = $this->klarna_currency;
				$update['locale']                       = $this->klarna_language;
				$update['merchant']['id']               = $eid;
				$update['merchant']['terms_uri']        = $this->terms_url;
				$update['merchant']['checkout_uri']     = add_query_arg( 'klarnaListener', 'checkout', $this->klarna_checkout_url );
				$update['merchant']['confirmation_uri'] = add_query_arg ( array('klarna_order' => '{checkout.order.uri}', 'sid' => $order_id, 'order-received' => $order_id ), $this->klarna_checkout_thanks_url);
				$update['merchant']['push_uri']         = add_query_arg( array('sid' => $order_id, 'scountry' => $this->klarna_country, 'klarna_order' => '{checkout.order.uri}', 'wc-api' => 'WC_Gateway_Klarna_Checkout'), $this->klarna_checkout_url );


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
			unset($_SESSION['klarna_checkout']);

		}

	}

	if ( $klarna_order == null ) {

		// Start new session
		$create['purchase_country']             = $this->klarna_country;
		$create['purchase_currency']            = $this->klarna_currency;
		$create['locale']                       = $this->klarna_language;
		$create['merchant']['id']               = $eid;
		$create['merchant']['terms_uri']        = $this->terms_url;
		$create['merchant']['checkout_uri']     = add_query_arg( 'klarnaListener', 'checkout', $this->klarna_checkout_url );
		$create['merchant']['confirmation_uri'] = add_query_arg ( array('klarna_order' => '{checkout.order.uri}', 'sid' => $order_id, 'order-received' => $order_id ), $this->klarna_checkout_thanks_url);
		$create['merchant']['push_uri']         = add_query_arg( array('sid' => $order_id, 'scountry' => $this->klarna_country, 'klarna_order' => '{checkout.order.uri}', 'wc-api' => 'WC_Gateway_Klarna_Checkout'), $this->klarna_checkout_url );

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

		$klarna_order = new Klarna_Checkout_Order( $connector );
		$klarna_order->create( apply_filters( 'kco_create_order', $create ) );
		$klarna_order->fetch();

	}

	// Store location of checkout session
	$_SESSION['klarna_checkout'] = $sessionId = $klarna_order->getLocation();

	// Display checkout
	$snippet = $klarna_order['gui']['snippet'];

	ob_start();
	do_action( 'klarna_before_kco_checkout', $order_id );
	echo '<div>' . apply_filters( 'klarna_kco_checkout', $snippet ) . '</div>';
	do_action( 'klarna_after_kco_checkout', $order_id );
	return ob_get_clean();

} // End if sizeof cart 