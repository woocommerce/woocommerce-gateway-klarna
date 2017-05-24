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