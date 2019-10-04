<?php
/**
 * Validates Klarna order locally by checking stock
 *
 * @link  http://www.woothemes.com/products/klarna/
 * @since 1.0.0
 *
 * @package WC_Gateway_Klarna
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class that validates Klarna orders.
 */
class WC_Gateway_Klarna_Order_Validate {

	/**
	 * Validate Klarna order
	 * Checks order items' stock status and confirms there's a chosen shipping method
	 *
	 * @since 1.0.0
	 */
	public static function validate_checkout_listener() {
		// Read the post body.
		$post_body = file_get_contents( 'php://input' );

		// Convert post body into native object.
		$data = json_decode( $post_body, true );

		do_action( 'kco_before_validate_checkout', $data );

		$all_in_stock            = true;
		$shipping_chosen         = false;
		$shipping_needed         = false;
		$is_subscription_limited = false;

		if ( isset( $data['order_lines'] ) && is_array( $data['order_lines'] ) ) {
			$cart_items = $data['order_lines']; // V3.
		} elseif ( isset( $data['cart']['items'] ) && is_array( $data['cart']['items'] ) ) {
			$cart_items = $data['cart']['items']; // V2.
		}

		if ( is_array( $data['billing_address'] ) ) {
			$customer = $data['billing_address'];
		}

		foreach ( $cart_items as $cart_item ) {
			if ( 'physical' === $cart_item['type'] ) {
				// Get product by SKU or ID.
				if ( wc_get_product_id_by_sku( $cart_item['reference'] ) ) {
					$cart_item_product = wc_get_product( wc_get_product_id_by_sku( $cart_item['reference'] ) );
				} else {
					$cart_item_product = wc_get_product( $cart_item['reference'] );
				}
				if ( $cart_item_product ) {
					if ( ! self::product_has_enough_stock( $cart_item_product, $cart_item['quantity'] ) ) {
						$all_in_stock = false;
					}
					if ( self::product_needs_shipping( $cart_item_product ) ) {
						$shipping_needed = true;
					}
					if ( class_exists( 'WC_Subscriptions_Product' ) && WC_Subscriptions_Product::is_subscription( $cart_item_product ) ) {
						$is_subscription_limited = self::is_product_limited_for_user( $cart_item_product, $customer );
					}
				}
			} elseif ( 'shipping_fee' === $cart_item['type'] ) {
				$shipping_chosen = true;
			}
		}

		do_action( 'kco_validate_checkout', $data, $all_in_stock, $shipping_chosen );

		if ( $all_in_stock && ! $is_subscription_limited && ( $shipping_chosen || ! $shipping_needed ) ) {
			header( 'HTTP/1.0 200 OK' );
		} else {
			header( 'HTTP/1.0 303 See Other' );
			global $klarna_checkout_url;
			if ( ! $all_in_stock ) {
				header( 'Location: ' . wc_get_cart_url() . '?stock_validate_failed' );
			} elseif ( ! $shipping_chosen ) {
				header( 'Location: ' . $klarna_checkout_url . '?no_shipping' );
			} elseif ( $is_subscription_limited ) {
				header( 'Location: ' . $klarna_checkout_url . '?subscription_limit' );
			}
		}

		do_action( 'kco_after_validate_checkout', $data, $all_in_stock, $shipping_chosen );
	} // End function validate_checkout_listener.

	/**
	 * Checks if the product has enough stock remaining.
	 *
	 * @param object $product WooCommerce Product.
	 * @return bool
	 */
	public static function product_has_enough_stock( $product, $quantity ) {
		if ( ! $product->has_enough_stock( $quantity ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Checks if product needs shipping.
	 *
	 * @param object $product WooCommerce Product.
	 * @return bool
	 */
	public static function product_needs_shipping( $product ) {

		if ( $product->is_virtual() ) {
			$needs_shipping = false;
		} else {
			if ( class_exists( 'WC_Subscriptions_Product' ) && WC_Subscriptions_Product::is_subscription( $product ) ) {
				if ( 0 === WC_Subscriptions_Product::get_trial_length( $product ) ) {
					$needs_shipping = true;
				} else {
					$needs_shipping = false;
				}
			} else {
				$needs_shipping = true;
			}
		}

		return $needs_shipping;
	}

	/**
	 * Checks the product and the customer to see if they have a subscription of the product already.
	 *
	 * @param object $product WooCommerce Product.
	 * @param object $customer Customer data from Klarna.
	 * @return bool
	 */
	public static function is_product_limited_for_user( $product, $customer ) {
		$customer_mail = $customer['email'];
		$user          = get_user_by( 'email', $customer_mail );
		$user_id       = $user->ID;
		return wcs_is_product_limited_for_user( $product, $user_id );
	}

}

$wc_gateway_klarna_order_validate = new WC_Gateway_Klarna_Order_Validate();
