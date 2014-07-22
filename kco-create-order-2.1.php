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


// Give plugins the opportunity to create an order themselves
$order_id = apply_filters( 'woocommerce_create_order', null, $this );

if ( is_numeric( $order_id ) )
	return $order_id;

// Create Order (send cart variable so we can record items and reduce inventory). Only create if this is a new order, not if the payment was rejected.
$order_data = apply_filters( 'woocommerce_new_order_data', array(
	'post_type' 	=> 'shop_order',
	'post_title' 	=> sprintf( __( 'Order &ndash; %s', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'woocommerce' ) ) ),
	'post_status' 	=> 'publish',
	'ping_status'	=> 'closed',
	'post_excerpt' 	=> isset( $this->posted['order_comments'] ) ? $this->posted['order_comments'] : '',
	'post_author' 	=> 1,
	'post_password'	=> uniqid( 'order_' )	// Protects the post just in case
) );

// Insert or update the post data
$create_new_order = true;

if ( WC()->session->order_awaiting_payment > 0 ) {

	$order_id = absint( WC()->session->order_awaiting_payment );

	/* Check order is unpaid by getting its status */
	$terms = wp_get_object_terms( $order_id, 'shop_order_status', array( 'fields' => 'slugs' ) );
	$order_status = isset( $terms[0] ) ? $terms[0] : 'pending';

	// Resume the unpaid order if its pending
	if ( $order_status == 'pending' || $order_status == 'failed' ) {

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
	$order_id = wp_insert_post( $order_data, true );

	if ( is_wp_error( $order_id ) )
		throw new Exception( 'Error: Unable to create order. Please try again.' );
	else
		do_action( 'woocommerce_new_order', $order_id );
}




// Store the line items to the new/resumed order
foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {

	$_product = $values['data'];
	
   	// Add line item
   	$item_id = wc_add_order_item( $order_id, array(
 		'order_item_name' 		=> $_product->get_title(),
 		'order_item_type' 		=> 'line_item'
 	) );

 	// Add line item meta
 	if ( $item_id ) {
	 	wc_add_order_item_meta( $item_id, '_qty', apply_filters( 'woocommerce_stock_amount', $values['quantity'] ) );
	 	wc_add_order_item_meta( $item_id, '_tax_class', $_product->get_tax_class() );
	 	wc_add_order_item_meta( $item_id, '_product_id', $values['product_id'] );
	 	wc_add_order_item_meta( $item_id, '_variation_id', $values['variation_id'] );
	 	wc_add_order_item_meta( $item_id, '_line_subtotal', wc_format_decimal( $values['line_subtotal'] ) );
	 	wc_add_order_item_meta( $item_id, '_line_total', wc_format_decimal( $values['line_total'] ) );
	 	wc_add_order_item_meta( $item_id, '_line_tax', wc_format_decimal( $values['line_tax'] ) );
	 	wc_add_order_item_meta( $item_id, '_line_subtotal_tax', wc_format_decimal( $values['line_subtotal_tax'] ) );

	 	// Store variation data in meta so admin can view it
		if ( $values['variation'] && is_array( $values['variation'] ) ) {
			foreach ( $values['variation'] as $key => $value ) {
				$key = str_replace( 'attribute_', '', $key );
				wc_add_order_item_meta( $item_id, $key, $value );
			}
		}

	 	// Add line item meta for backorder status
	 	if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $values['quantity'] ) ) {
	 		wc_add_order_item_meta( $item_id, apply_filters( 'woocommerce_backordered_item_meta_name', __( 'Backordered', 'woocommerce' ), $cart_item_key, $order_id ), $values['quantity'] - max( 0, $_product->get_total_stock() ) );
	 	}

	 	// Allow plugins to add order item meta
	 	do_action( 'woocommerce_add_order_item_meta', $item_id, $values, $cart_item_key );
 	}
}

// Store fees
foreach ( WC()->cart->get_fees() as $fee_key => $fee ) {
	$item_id = wc_add_order_item( $order_id, array(
 		'order_item_name' 		=> $fee->name,
 		'order_item_type' 		=> 'fee'
 	) );

 	if ( $fee->taxable )
 		wc_add_order_item_meta( $item_id, '_tax_class', $fee->tax_class );
 	else
 		wc_add_order_item_meta( $item_id, '_tax_class', '0' );

 	wc_add_order_item_meta( $item_id, '_line_total', wc_format_decimal( $fee->amount ) );
	wc_add_order_item_meta( $item_id, '_line_tax', wc_format_decimal( $fee->tax ) );

	// Allow plugins to add order item meta to fees
	do_action( 'woocommerce_add_order_fee_meta', $order_id, $item_id, $fee, $fee_key );
}

// Store shipping for all packages
$packages = WC()->shipping->get_packages();
$this->shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

foreach ( $packages as $i => $package ) {
	
	if ( isset( $package['rates'][ $this->shipping_methods[ $i ] ] ) ) {

		$method = $package['rates'][ $this->shipping_methods[ $i ] ];
		
		$item_id = wc_add_order_item( $order_id, array(
	 		'order_item_name' 		=> $method->label,
	 		'order_item_type' 		=> 'shipping'
	 	) );

		if ( $item_id ) {
	 		wc_add_order_item_meta( $item_id, 'method_id', $method->id );
 			wc_add_order_item_meta( $item_id, 'cost', wc_format_decimal( $method->cost ) );
			do_action( 'woocommerce_add_shipping_order_item', $order_id, $item_id, $i );
 		}
	}
}

// Store tax rows
foreach ( array_keys( WC()->cart->taxes + WC()->cart->shipping_taxes ) as $key ) {
	$code = WC()->cart->tax->get_rate_code( $key );

	if ( $code ) {
		$item_id = wc_add_order_item( $order_id, array(
	 		'order_item_name' 		=> $code,
	 		'order_item_type' 		=> 'tax'
	 	) );

	 	// Add line item meta
	 	if ( $item_id ) {
	 		wc_add_order_item_meta( $item_id, 'rate_id', $key );
	 		wc_add_order_item_meta( $item_id, 'label', WC()->cart->tax->get_rate_label( $key ) );
		 	wc_add_order_item_meta( $item_id, 'compound', absint( WC()->cart->tax->is_compound( $key ) ? 1 : 0 ) );
		 	wc_add_order_item_meta( $item_id, 'tax_amount', wc_format_decimal( isset( WC()->cart->taxes[ $key ] ) ? WC()->cart->taxes[ $key ] : 0 ) );
		 	wc_add_order_item_meta( $item_id, 'shipping_tax_amount', wc_format_decimal( isset( WC()->cart->shipping_taxes[ $key ] ) ? WC()->cart->shipping_taxes[ $key ] : 0 ) );
		}
	}
}

// Store coupons
if ( $applied_coupons = WC()->cart->get_coupons() ) {
	foreach ( $applied_coupons as $code => $coupon ) {

		$item_id = wc_add_order_item( $order_id, array(
	 		'order_item_name' 		=> $code,
	 		'order_item_type' 		=> 'coupon'
	 	) );

	 	// Add line item meta
	 	if ( $item_id ) {
	 		wc_add_order_item_meta( $item_id, 'discount_amount', isset( WC()->cart->coupon_discount_amounts[ $code ] ) ? WC()->cart->coupon_discount_amounts[ $code ] : 0 );
		}
	}
}


update_post_meta( $order_id, '_payment_method', 		$this->id );
update_post_meta( $order_id, '_payment_method_title', 	$this->method_title );


if ( empty( $this->posted['billing_email'] ) && is_user_logged_in() ) {
	$current_user = wp_get_current_user();
	update_post_meta( $order_id, '_billing_email', $current_user->user_email );
}

// Customer ID
if ( is_user_logged_in() ) {
	$current_user = wp_get_current_user();
	update_post_meta( $order_id, '_customer_user', 			absint( $current_user->ID ) );
}

update_post_meta( $order_id, '_order_shipping', 		wc_format_decimal( WC()->cart->shipping_total ) );
update_post_meta( $order_id, '_order_discount', 		wc_format_decimal( WC()->cart->get_order_discount_total() ) );
update_post_meta( $order_id, '_cart_discount', 			wc_format_decimal( WC()->cart->get_cart_discount_total() ) );
update_post_meta( $order_id, '_order_tax', 				wc_format_decimal( WC()->cart->tax_total ) );
update_post_meta( $order_id, '_order_shipping_tax', 	wc_format_decimal( WC()->cart->shipping_tax_total ) );
update_post_meta( $order_id, '_order_total', 			wc_format_decimal( WC()->cart->total, get_option( 'woocommerce_price_num_decimals' ) ) );

update_post_meta( $order_id, '_order_key', 				'wc_' . apply_filters('woocommerce_generate_order_key', uniqid('order_') ) );
update_post_meta( $order_id, '_order_currency', 		get_woocommerce_currency() );
update_post_meta( $order_id, '_prices_include_tax', 	get_option( 'woocommerce_prices_include_tax' ) );
update_post_meta( $order_id, '_customer_ip_address',	isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'] );
update_post_meta( $order_id, '_customer_user_agent', 	isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '' );

// Let plugins add meta
do_action( 'woocommerce_checkout_update_order_meta', $order_id, '' );

// Order status
wp_set_object_terms( $order_id, 'pending', 'shop_order_status' );

// Update customer shipping and payment method to posted method
$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

if ( isset( $this->posted['shipping_method'] ) && is_array( $this->posted['shipping_method'] ) )
	foreach ( $this->posted['shipping_method'] as $i => $value )
		$chosen_shipping_methods[ $i ] = wc_clean( $value );

WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );
WC()->session->set( 'chosen_payment_method', $this->id );