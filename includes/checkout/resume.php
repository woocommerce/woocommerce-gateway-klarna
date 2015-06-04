<?php
/**
 * Updates ongoing order in Klarna checkout page
 *
 * @package WC_Gateway_Klarna
 */

// Resume session
if ( $this->is_rest() ) {
	$klarna_order = new \Klarna\Rest\Checkout\Order(
		$connector, 
		WC()->session->get( 'klarna_checkout' )
	);
} else {
	$klarna_order = new Klarna_Checkout_Order(
		$connector,
		WC()->session->get( 'klarna_checkout' )
	);
}

$local_order_id = WC()->session->get( 'ongoing_klarna_order' );

try {

	$klarna_order->fetch();
	if ( ! $this->is_rest() ) {
		$klarna_order_as_array = $klarna_order->marshal();
	}

	// Reset session if the country in the store has changed since last time the checkout was loaded
	if ( strtolower( $this->klarna_country ) != strtolower( $klarna_order_as_array['purchase_country'] ) ) {
		// Reset session
		$klarna_order = null;
		WC()->session->__unset( 'klarna_checkout' );
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
				'sid' => $local_order_id, 
				'order-received' => $local_order_id 
			),
			$this->klarna_checkout_thanks_url
		);
		$merchant_push_uri = add_query_arg( 
			array(
				'sid' => $local_order_id, 
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
		if ( $this->testmode !== 'yes' && is_user_logged_in() ) {
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
	WC()->session->__unset( 'klarna_checkout' );
}