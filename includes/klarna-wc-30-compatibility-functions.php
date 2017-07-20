<?php

/**
 * Because who needs backward compatibility?
 */

/**
 * Gets WooCommerce order ID in WC 3.0 and in previous versions
 *
 * @param $order WC_Order
 *
 * @return mixed | WC_Order ID
 */
function klarna_wc_get_order_id( $order ) {
	if ( method_exists( $order, 'get_id' ) ) {
		return $order->get_id();
	} else {
		return $order->id;
	}
}

/**
 * Gets WooCommerce order payment method in WC 3.0 and in previous versions
 *
 * @param $order WC_Order
 *
 * @return mixed | WC_Order payment method
 */
function klarna_wc_get_order_payment_method( $order ) {
	if ( method_exists( $order, 'get_payment_method' ) ) {
		return $order->get_payment_method();
	} else {
		return $order->payment_method;
	}
}

/**
 * Gets WooCommerce order ID in WC 3.0 and in previous versions
 *
 * @param $product WC_Product
 *
 * @return mixed | WC_Product ID
 */
function klarna_wc_get_product_id( $product ) {
	if ( method_exists( $product, 'get_id' ) ) {
		return $product->get_id();
	} else {
		return $product->id;
	}
}

/**
 * Gets Product variation ID, same as ID in 3.0+.
 *
 * @param $product WC_Product
 *
 * @return mixed
 */
function klarna_wc_get_product_variation_id( $product ) {
	if ( method_exists( $product, 'get_id' ) ) {
		return $product->get_id();
	} else {
		return $product->variation_id;
	}
}

/**
 * Gets customer billing country.
 *
 * @param $customer WC_Customer
 *
 * @return mixed
 */
function klarna_wc_get_customer_country( $customer ) {
	if ( $customer ) {
		if ( method_exists( $customer, 'get_billing_country' ) ) {
			return $customer->get_billing_country();
		} else {
			return $customer->get_country();
		}
	}
}

/**
 * Gets product post object.
 *
 * @param $cart_item
 *
 * @return mixed
 */
function klarna_wc_get_cart_item_post( $cart_item ) {
	if ( method_exists( $cart_item, 'get_id' ) ) {
		return get_post( $cart_item->get_id() );
	} else {
		return get_post( $cart_item->id );
	}
}

/**
 * Gets coupon discount type.
 *
 * @param $coupon WC_Coupon
 *
 * @return mixed
 */
function klarna_wc_get_coupon_discount_type( $coupon ) {
	if ( method_exists( $coupon, 'get_discount_type' ) ) {
		return $coupon->get_discount_type();
	} else {
		return $coupon->discount_type;
	}
}

/**
 * Gets coupon code.
 *
 * @param $coupon WC_Coupon
 *
 * @return mixed
 */
function klarna_wc_get_coupon_code( $coupon ) {
	if ( method_exists( $coupon, 'get_code' ) ) {
		return $coupon->get_code();
	} else {
		return $coupon->code;
	}
}