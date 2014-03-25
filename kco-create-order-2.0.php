<?php
global $woocommerce, $wpdb;
$order_id = "";


if ( sizeof( $woocommerce->cart->get_cart() ) == 0 )
	WC_Klarna_Compatibility::wc_add_notice(sprintf( __( 'Sorry, your session has expired. <a href="%s">Return to homepage &rarr;</a>', 'klarna' ), home_url() ), 'error');
	
	
// Recheck cart items so that they are in stock
$result = $woocommerce->cart->check_cart_item_stock();
if( is_wp_error($result) ) {
	return $result->get_error_message();
	exit();
}

// Update cart totals
$woocommerce->cart->calculate_totals();

// Create Order (send cart variable so we can record items and reduce inventory). Only create if this is a new order, not if the payment was rejected last time.
$order_data = apply_filters( 'woocommerce_new_order_data', array(
	'post_type' 	=> 'shop_order',
	'post_title' 	=> sprintf( __( 'Order &ndash; %s', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'woocommerce' ) ) ),
	'post_status' 	=> 'publish',
	'ping_status'	=> 'closed',
	'post_excerpt' 	=> '',
	'post_author' 	=> 1,
	'post_password'	=> uniqid( 'order_' )	// Protects the post just in case
) );

// Insert or update the post data
$create_new_order = true;

	
if ( $woocommerce->session->order_awaiting_payment > 0 ) {

	$order_id = absint( $woocommerce->session->order_awaiting_payment );
	
	/* Check order is unpaid by getting its status */
	$terms = wp_get_object_terms( $order_id, 'shop_order_status', array( 'fields' => 'slugs' ) );
	$order_status = isset( $terms[0] ) ? $terms[0] : 'pending';
	
	// Resume the unpaid order if its pending
	if ( $order_status == 'pending' ) {
		
		// Update the existing order as we are resuming it
		$create_new_order = false;
		$order_data['ID'] = $order_id;
		wp_update_post( $order_data );
		
		// Clear the old line items - we'll add these again in case they changed
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id IN ( SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d )", $order_id ) );

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d", $order_id ) );
		
		// Trigger an action for the resumed order
		do_action( 'woocommerce_resume_order', $order_id );
	}
}

if ( $create_new_order ) {
	$order_id = wp_insert_post( $order_data );
	
	if ( is_wp_error( $order_id ) )
		throw new MyException( 'Error: Unable to create order. Please try again.' );
	else
		do_action( 'woocommerce_new_order', $order_id );
}


// Add Cart items

foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {

	$_product = $values['data'];
	
	// Add line item
	$item_id = woocommerce_add_order_item( $order_id, array(
			'order_item_name' 		=> $_product->get_title(),
			'order_item_type' 		=> 'line_item'
		) );
		
		$klarna_product = get_product($values['product_id']);
		
		$klarna_price_inc_tax = $klarna_product->get_price_including_tax()*$values['quantity'];
		$klarna_price_ex_tax = $klarna_product->get_price_excluding_tax()*$values['quantity'];
		$klarna_tax = $klarna_price_inc_tax - $klarna_price_ex_tax;
		
		// Add line item meta
		if ( $item_id ) {
		/*
	 	woocommerce_add_order_item_meta( $item_id, '_qty', apply_filters( 'woocommerce_stock_amount', $values['quantity'] ) );
	 	woocommerce_add_order_item_meta( $item_id, '_tax_class', $_product->get_tax_class() );
	 	woocommerce_add_order_item_meta( $item_id, '_product_id', $values['product_id'] );
	 	woocommerce_add_order_item_meta( $item_id, '_variation_id', $values['variation_id'] );
	 	
	 	woocommerce_add_order_item_meta( $item_id, '_line_subtotal', woocommerce_format_decimal( $klarna_price_ex_tax ) );
	 	woocommerce_add_order_item_meta( $item_id, '_line_total', woocommerce_format_decimal( $klarna_price_ex_tax ) );
	 	woocommerce_add_order_item_meta( $item_id, '_line_tax', woocommerce_format_decimal( $klarna_tax, 4 ) );
	 	woocommerce_add_order_item_meta( $item_id, '_line_subtotal_tax', woocommerce_format_decimal( $klarna_tax, 4 ) );
	 	*/
	 	
	 	
	 	woocommerce_add_order_item_meta( $item_id, '_qty', apply_filters( 'woocommerce_stock_amount', $values['quantity'] ) );
	 	woocommerce_add_order_item_meta( $item_id, '_tax_class', $_product->get_tax_class() );
	 	woocommerce_add_order_item_meta( $item_id, '_product_id', $values['product_id'] );
	 	woocommerce_add_order_item_meta( $item_id, '_variation_id', $values['variation_id'] );
	 	woocommerce_add_order_item_meta( $item_id, '_line_subtotal', woocommerce_format_decimal( $values['line_subtotal'], 4 ) );
	 	woocommerce_add_order_item_meta( $item_id, '_line_total', woocommerce_format_decimal( $values['line_total'], 4 ) );
	 	woocommerce_add_order_item_meta( $item_id, '_line_tax', woocommerce_format_decimal( $values['line_tax'], 4 ) );
	 	woocommerce_add_order_item_meta( $item_id, '_line_subtotal_tax', woocommerce_format_decimal( $values['line_subtotal_tax'], 4 ) );
 	
 	
	 	// Store variation data in meta so admin can view it
	 	if ( $values['variation'] && is_array( $values['variation'] ) )
			foreach ( $values['variation'] as $key => $value )
				woocommerce_add_order_item_meta( $item_id, esc_attr( str_replace( 'attribute_', '', $key ) ), $value );

		// Add line item meta for backorder status
		if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $values['quantity'] ) )
 			woocommerce_add_order_item_meta( $item_id, __( 'Backordered', 'woocommerce' ), $values['quantity'] - max( 0, $_product->get_total_stock() ) );

 		//allow plugins to add order item meta
 		do_action( 'woocommerce_add_order_item_meta', $item_id, $values );
 	}
 }

 // Store fees
 foreach ( $woocommerce->cart->get_fees() as $fee ) {
	 $item_id = woocommerce_add_order_item( $order_id, array(
	 	'order_item_name' 		=> $fee->name,
	 	'order_item_type' 		=> 'fee'
	 ) );

	if ( $fee->taxable )
			woocommerce_add_order_item_meta( $item_id, '_tax_class', $fee->tax_class );
		else
			woocommerce_add_order_item_meta( $item_id, '_tax_class', '0' );

		woocommerce_add_order_item_meta( $item_id, '_line_total', woocommerce_format_decimal( $fee->amount ) );
		woocommerce_add_order_item_meta( $item_id, '_line_tax', woocommerce_format_decimal( $fee->tax ) );
	}

	// Store tax rows
	foreach ( array_keys( $woocommerce->cart->taxes + $woocommerce->cart->shipping_taxes ) as $key ) {

	$item_id = woocommerce_add_order_item( $order_id, array(
 		'order_item_name' 		=> $woocommerce->cart->tax->get_rate_code( $key ),
 		'order_item_type' 		=> 'tax'
 	) );

 	// Add line item meta
 	if ( $item_id ) {
			woocommerce_add_order_item_meta( $item_id, 'rate_id', $key );
			woocommerce_add_order_item_meta( $item_id, 'label', $woocommerce->cart->tax->get_rate_label( $key ) );
			woocommerce_add_order_item_meta( $item_id, 'compound', absint( $woocommerce->cart->tax->is_compound( $key ) ? 1 : 0 ) );
			woocommerce_add_order_item_meta( $item_id, 'tax_amount', woocommerce_clean( isset( $woocommerce->cart->taxes[ $key ] ) ? $woocommerce->cart->taxes[ $key ] : 0 ) );
			woocommerce_add_order_item_meta( $item_id, 'shipping_tax_amount', woocommerce_clean( isset( $woocommerce->cart->shipping_taxes[ $key ] ) ? $woocommerce->cart->shipping_taxes[ $key ] : 0 ) );
		}
	}

	// Store coupons
	if ( $applied_coupons = $woocommerce->cart->get_applied_coupons() ) {
	foreach ( $applied_coupons as $code ) {
		
		$item_id = woocommerce_add_order_item( $order_id, array(
 			'order_item_name' 		=> $code,
 			'order_item_type' 		=> 'coupon'
 		) );

 		// Add line item meta
 		if ( $item_id ) {
 			woocommerce_add_order_item_meta( $item_id, 'discount_amount', isset( $woocommerce->cart->coupon_discount_amounts[ $code ] ) ? $woocommerce->cart->coupon_discount_amounts[ $code ] : 0 );
 		}
	}
}

// Store meta
if ( $woocommerce->session->shipping_total ) {
	$shipping_method_id = strtolower(str_replace(' ', '_', $woocommerce->session->shipping_label));
	update_post_meta( $order_id, '_shipping_method', 		$shipping_method_id );
	update_post_meta( $order_id, '_shipping_method_title', 	$woocommerce->session->shipping_label );
}


update_post_meta( $order_id, '_payment_method', 		$this->id );
update_post_meta( $order_id, '_payment_method_title', 	$this->method_title );

update_post_meta( $order_id, '_order_shipping', 		WC_Klarna_Compatibility::wc_format_decimal( $woocommerce->cart->shipping_total ) );
update_post_meta( $order_id, '_order_discount', 		WC_Klarna_Compatibility::wc_format_decimal( $woocommerce->cart->get_order_discount_total() ) );
update_post_meta( $order_id, '_cart_discount', 			WC_Klarna_Compatibility::wc_format_decimal( $woocommerce->cart->get_cart_discount_total() ) );
update_post_meta( $order_id, '_order_tax', 				WC_Klarna_Compatibility::wc_format_decimal( $woocommerce->cart->tax_total ) );
update_post_meta( $order_id, '_order_shipping_tax', 	WC_Klarna_Compatibility::wc_format_decimal( $woocommerce->cart->shipping_tax_total ) );
update_post_meta( $order_id, '_order_total', 			WC_Klarna_Compatibility::wc_format_decimal( $woocommerce->cart->total ) );
//update_post_meta( $order_id, '_order_total', 			WC_Klarna_Compatibility::wc_format_decimal( $woocommerce->cart->subtotal ) );
update_post_meta( $order_id, '_order_key', 				apply_filters('woocommerce_generate_order_key', uniqid('order_') ) );
//update_post_meta( $order_id, '_customer_user', 			absint( $this->customer_id ) );
update_post_meta( $order_id, '_order_currency', 		get_woocommerce_currency() );
update_post_meta( $order_id, '_prices_include_tax', 	get_option( 'woocommerce_prices_include_tax' ) );
update_post_meta( $order_id, '_customer_ip_address',	isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'] );
update_post_meta( $order_id, '_customer_user_agent', 	isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '' );

// Let plugins add meta
do_action( 'woocommerce_checkout_update_order_meta', $order_id, array() );

// Order status
wp_set_object_terms( $order_id, 'pending', 'shop_order_status' );