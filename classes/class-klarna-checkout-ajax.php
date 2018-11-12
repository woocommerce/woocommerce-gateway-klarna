<?php

/**
 * Klarna Checkout AJAX callbacks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class for Klarna shortodes.
 */
class WC_Gateway_Klarna_Checkout_Ajax {

	public function __construct() {

		/**
		 * Checkout page AJAX
		 */

		// Add coupon
		add_action( 'wp_ajax_klarna_checkout_coupons_callback', array( $this, 'klarna_checkout_coupons_callback' ) );
		add_action(
			'wp_ajax_nopriv_klarna_checkout_coupons_callback', array(
				$this,
				'klarna_checkout_coupons_callback',
			)
		);

		// Remove coupon
		add_action(
			'wp_ajax_klarna_checkout_remove_coupon_callback', array(
				$this,
				'klarna_checkout_remove_coupon_callback',
			)
		);
		add_action(
			'wp_ajax_nopriv_klarna_checkout_remove_coupon_callback', array(
				$this,
				'klarna_checkout_remove_coupon_callback',
			)
		);

		// Cart quantity
		add_action(
			'wp_ajax_klarna_checkout_cart_callback_update', array(
				$this,
				'klarna_checkout_cart_callback_update',
			)
		);
		add_action(
			'wp_ajax_nopriv_klarna_checkout_cart_callback_update', array(
				$this,
				'klarna_checkout_cart_callback_update',
			)
		);

		// Cart remove
		add_action(
			'wp_ajax_klarna_checkout_cart_callback_remove', array(
				$this,
				'klarna_checkout_cart_callback_remove',
			)
		);
		add_action(
			'wp_ajax_nopriv_klarna_checkout_cart_callback_remove', array(
				$this,
				'klarna_checkout_cart_callback_remove',
			)
		);

		// Shipping method selector
		add_action( 'wp_ajax_klarna_checkout_shipping_callback', array( $this, 'klarna_checkout_shipping_callback' ) );
		add_action(
			'wp_ajax_nopriv_klarna_checkout_shipping_callback', array(
				$this,
				'klarna_checkout_shipping_callback',
			)
		);

		// Shipping option inside KCO iframe
		add_action(
			'wp_ajax_kco_iframe_shipping_option_change_cb', array(
				$this,
				'kco_iframe_shipping_option_change_cb',
			)
		);
		add_action(
			'wp_ajax_nopriv_kco_iframe_shipping_option_change_cb', array(
				$this,
				'kco_iframe_shipping_option_change_cb',
			)
		);

		// Country selector
		add_action( 'wp_ajax_klarna_checkout_country_callback', array( $this, 'klarna_checkout_country_callback' ) );
		add_action(
			'wp_ajax_nopriv_klarna_checkout_country_callback', array(
				$this,
				'klarna_checkout_country_callback',
			)
		);

		// Order note
		add_action(
			'wp_ajax_klarna_checkout_order_note_callback', array(
				$this,
				'klarna_checkout_order_note_callback',
			)
		);
		add_action(
			'wp_ajax_nopriv_klarna_checkout_order_note_callback', array(
				$this,
				'klarna_checkout_order_note_callback',
			)
		);

		/**
		 * KCO iframe JS event callbacks
		 */

		// V2
		add_action( 'wp_ajax_kco_iframe_change_cb', array( $this, 'kco_iframe_change_cb' ) );
		add_action( 'wp_ajax_nopriv_kco_iframe_change_cb', array( $this, 'kco_iframe_change_cb' ) );

		add_action(
			'wp_ajax_kco_iframe_shipping_address_change_v2_cb', array(
				$this,
				'kco_iframe_shipping_address_change_v2_cb',
			)
		);
		add_action(
			'wp_ajax_nopriv_kco_iframe_shipping_address_change_v2_cb', array(
				$this,
				'kco_iframe_shipping_address_change_v2_cb',
			)
		);

		// V3
		add_action(
			'wp_ajax_kco_iframe_shipping_address_change_cb', array(
				$this,
				'kco_iframe_shipping_address_change_cb',
			)
		);
		add_action(
			'wp_ajax_nopriv_kco_iframe_shipping_address_change_cb', array(
				$this,
				'kco_iframe_shipping_address_change_cb',
			)
		);

	}

	/**
	 * Klarna Checkout coupons AJAX callback.
	 *
	 * @since  2.0
	 **/
	function klarna_checkout_coupons_callback() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'klarna_checkout_nonce' ) ) {
			exit( 'Nonce can not be verified.' );
		}

		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}

		$data                = array();
		$data['widget_html'] = '';

		// Adding coupon
		if ( ! empty( $_REQUEST['coupon'] ) && is_string( $_REQUEST['coupon'] ) ) {
			$coupon          = $_REQUEST['coupon'];
			$coupon_success  = WC()->cart->add_discount( $coupon );
			$applied_coupons = WC()->cart->applied_coupons;

			WC()->session->set( 'applied_coupons', $applied_coupons );
			WC()->cart->calculate_totals();

			wc_clear_notices(); // This notice handled by Klarna plugin

			WC()->cart->calculate_shipping();
			WC()->cart->calculate_fees();
			WC()->cart->calculate_totals();
			$this->update_or_create_local_order();

			$amount                 = wc_price( WC()->cart->get_coupon_discount_amount( $coupon, WC()->cart->display_cart_ex_tax ) );
			$data['amount']         = $amount;
			$data['coupon_success'] = $coupon_success;
			$data['coupon']         = $coupon;
			$data['widget_html']   .= $this->klarna_checkout_get_kco_widget_html();

			if ( WC()->session->get( 'klarna_checkout' ) ) {
				$this->ajax_update_klarna_order();
			}
		}
		wp_send_json_success( $data );
		wp_die();
	}

	/**
	 * Klarna Checkout coupons AJAX callback.
	 *
	 * @since  2.0
	 **/
	function klarna_checkout_remove_coupon_callback() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'klarna_checkout_nonce' ) ) {
			exit( 'Nonce can not be verified.' );
		}

		$data = array();
		// Removing coupon
		if ( isset( $_REQUEST['remove_coupon'] ) ) {
			$remove_coupon = $_REQUEST['remove_coupon'];
			WC()->cart->remove_coupon( $remove_coupon );
			$applied_coupons = WC()->cart->applied_coupons;
			WC()->session->set( 'applied_coupons', $applied_coupons );
			WC()->cart->calculate_totals();
			wc_clear_notices(); // This notice handled by Klarna plugin
			if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
				define( 'WOOCOMMERCE_CART', true );
			}
			WC()->cart->calculate_shipping();
			WC()->cart->calculate_fees();
			WC()->cart->calculate_totals();
			$this->update_or_create_local_order();
			$data['widget_html'] = $this->klarna_checkout_get_kco_widget_html();
			if ( WC()->session->get( 'klarna_checkout' ) ) {
				$this->ajax_update_klarna_order();
			}
		}
		wp_send_json_success( $data );
		wp_die();
	}

	/**
	 * Klarna Checkout cart AJAX callback.
	 *
	 * @since  2.0
	 **/
	function klarna_checkout_cart_callback_update() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'klarna_checkout_nonce' ) ) {
			exit( 'Nonce can not be verified.' );
		}

		$updated_item_key = $_REQUEST['cart_item_key'];
		$new_quantity     = $_REQUEST['new_quantity'];

		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}

		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Update WooCommerce cart and transient order item
		WC()->cart->set_quantity( $updated_item_key, $new_quantity );
		WC()->cart->calculate_shipping();
		WC()->cart->calculate_fees();
		WC()->cart->calculate_totals();

		if ( 'true' === $_REQUEST['min_max_flag'] ) {
			$data['cart_url'] = wc_get_page_permalink( 'cart' );
			wp_send_json_error( $data );
			wp_die();
		}

		$this->update_or_create_local_order();
		$data['widget_html'] = $this->klarna_checkout_get_kco_widget_html();

		if ( WC()->session->get( 'klarna_checkout' ) ) {
			$this->ajax_update_klarna_order();
		}

		wp_send_json_success( $data );
		wp_die();
	}

	/**
	 * Klarna Checkout cart AJAX callback.
	 *
	 * @since  2.0
	 **/
	function klarna_checkout_cart_callback_remove() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'klarna_checkout_nonce' ) ) {
			exit( 'Nonce can not be verified.' );
		}

		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}

		// Remove line item row
		$removed_item_key = esc_attr( $_REQUEST['cart_item_key_remove'] );
		WC()->cart->remove_cart_item( $removed_item_key );

		if ( count( WC()->cart->get_cart() ) > 0 ) {
			WC()->cart->calculate_shipping();
			WC()->cart->calculate_fees();
			WC()->cart->calculate_totals();
			$this->update_or_create_local_order();
		} else {
			if ( WC()->session->get( 'ongoing_klarna_order' ) ) {
				wp_delete_post( WC()->session->get( 'ongoing_klarna_order' ) );
				WC()->session->__unset( 'ongoing_klarna_order' );
			}
		}

		// This needs to be sent back to JS, so cart widget can be updated
		$data['item_count']  = WC()->cart->get_cart_contents_count();
		$data['cart_url']    = wc_get_cart_url();
		$data['widget_html'] = $this->klarna_checkout_get_kco_widget_html();

		// Update ongoing Klarna order
		if ( WC()->session->get( 'klarna_checkout' ) ) {
			$this->ajax_update_klarna_order();
		}

		wp_send_json_success( $data );
		wp_die();
	}

	/**
	 * Klarna Checkout shipping AJAX callback.
	 *
	 * @since  2.0
	 **/
	function klarna_checkout_shipping_callback() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'klarna_checkout_nonce' ) ) {
			exit( 'Nonce can not be verified.' );
		}

		$new_method                = $_REQUEST['new_method'];
		$chosen_shipping_methods[] = wc_clean( $new_method );

		WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );

		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}

		WC()->cart->calculate_shipping();
		WC()->cart->calculate_fees();
		WC()->cart->calculate_totals();

		$this->update_or_create_local_order();
		$data['new_method']  = $new_method;
		$data['widget_html'] = $this->klarna_checkout_get_kco_widget_html();

		if ( WC()->session->get( 'klarna_checkout' ) ) {
			$this->ajax_update_klarna_order();
		}

		wp_send_json_success( $data );
		wp_die();
	}

	/**
	 * Klarna order shipping option change callback function.
	 *
	 * @since  2.0
	 **/
	function kco_iframe_shipping_option_change_cb() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'klarna_checkout_nonce' ) ) {
			exit( 'Nonce can not be verified.' );
		}

		$new_method                = $_REQUEST['new_method'];
		$chosen_shipping_methods[] = wc_clean( $new_method );

		WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );
		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}

		WC()->cart->calculate_shipping();
		WC()->cart->calculate_fees();
		WC()->cart->calculate_totals();

		$this->update_or_create_local_order();
		$data['new_method']  = $new_method;
		$data['widget_html'] = $this->klarna_checkout_get_kco_widget_html();

		if ( WC()->session->get( 'klarna_checkout' ) ) {
			$this->ajax_update_klarna_order();
		}

		wp_send_json_success( $data );
		wp_die();
	}

	/**
	 * Klarna Checkout country selector AJAX callback.
	 *
	 * @since  2.0
	 **/
	function klarna_checkout_country_callback() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'klarna_checkout_nonce' ) ) {
			exit( 'Nonce can not be verified.' );
		}

		$data = array();

		if ( isset( $_REQUEST['new_country'] ) && is_string( $_REQUEST['new_country'] ) ) {
			$new_country = sanitize_text_field( $_REQUEST['new_country'] );
			// Reset session
			$klarna_order = null;
			WC()->session->__unset( 'klarna_checkout' );
			WC()->session->__unset( 'klarna_checkout_country' );
			// Store new country as WC session value
			WC()->session->set( 'klarna_euro_country', $new_country );
			// Get new checkout URL
			$lowercase_country = strtolower( $new_country );
			$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
			$data['new_url']   = $checkout_settings[ "klarna_checkout_url_$lowercase_country" ];
			// Send data back to JS function
			$data['klarna_euro_country'] = $new_country;
		}

		wp_send_json_success( $data );
		wp_die();
	}

	/**
	 * Klarna Checkout coupons AJAX callback.
	 *
	 * @since  2.0
	 **/
	function klarna_checkout_order_note_callback() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'klarna_checkout_nonce' ) ) {
			exit( 'Nonce can not be verified.' );
		}

		$data = array();

		// Adding order note.
		if ( isset( $_REQUEST['order_note'] ) && is_string( $_REQUEST['order_note'] ) ) {
			$order_note         = sanitize_text_field( $_REQUEST['order_note'] );
			$data['order_note'] = $order_note;
			if ( WC()->session->get( 'klarna_checkout' ) ) {
				WC()->cart->calculate_shipping();
				WC()->cart->calculate_fees();
				WC()->cart->calculate_totals();

				$orderid = $this->update_or_create_local_order();

				$order_details = array(
					'ID'           => $orderid,
					'post_excerpt' => $order_note,
				);

				wp_update_post( $order_details );

				WC()->session->set( 'klarna_order_note', $order_note );
				$this->ajax_update_klarna_order();
			}
		}
		wp_send_json_success( $data );
		wp_die();
	}

	/**
	 * Pushes Klarna order update in AJAX calls.
	 *
	 * Used to capture customer address, recalculate tax and shipping for order and user session
	 *
	 * @since  2.0
	 **/
	function kco_iframe_change_cb() {
		$klarna_secret  = WC_Gateway_Klarna_Checkout_Variables::get_klarna_secret();
		$klarna_server  = WC_Gateway_Klarna_Checkout_Variables::get_klarna_server();
		$klarna_country = WC_Gateway_Klarna_Checkout_Variables::get_klarna_country();
		$klarna_debug   = WC_Gateway_Klarna_Checkout_Variables::get_klarna_checkout_debug();
		// $klarna_log = WC_Gateway_Klarna_Checkout_Variables::get_klarna_checkout_log();
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'klarna_checkout_nonce' ) ) {
			exit( 'Nonce can not be verified.' );
		}

		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}

		$data                = array();
		$data['widget_html'] = '';

		// Check stock.
		if ( is_wp_error( WC()->cart->check_cart_item_stock() ) ) {
			wp_send_json_error();
			wp_die();
		}

		// Check coupons.
		if ( isset( $_REQUEST['email'] ) && is_email( $_REQUEST['email'] ) ) {
			if ( is_callable( array( WC()->customer, 'set_billing_email' ) ) ) {
				if ( 'guest_checkout@klarna.com' !== $_REQUEST['email'] ) {
					WC()->customer->set_billing_email( $_REQUEST['email'] );
				}
			}
			if ( is_callable( array( WC()->customer, 'save' ) ) ) {
				WC()->customer->save();
			}

			if ( count( WC()->cart->get_applied_coupons() ) > 0 ) {
				if ( WC()->customer->get_billing_email() ) {
					$coupons_before = count( WC()->cart->get_applied_coupons() );
					WC()->cart->check_customer_coupons( array( 'billing_email' => WC()->customer->get_billing_email() ) );
					if ( count( WC()->cart->get_applied_coupons() ) < $coupons_before ) {
						$coupon               = new WC_Coupon();
						$data['widget_html'] .= '<div class="woocommerce-error">' . $coupon->get_coupon_error( WC_Coupon::E_WC_COUPON_USAGE_LIMIT_REACHED ) . '</div>';
					}
				}
			}
		}

		// Capture email.
		if ( isset( $_REQUEST['email'] ) && is_string( $_REQUEST['email'] ) && ! is_user_logged_in() ) {
			$this->update_or_create_local_order( $_REQUEST['email'] );
			$orderid         = WC()->session->get( 'ongoing_klarna_order' );
			$data['orderid'] = $orderid;
			$connector       = Klarna_Checkout_Connector::create( $klarna_secret, $klarna_server );
			$klarna_order    = new Klarna_Checkout_Order( $connector, WC()->session->get( 'klarna_checkout' ) );

			$klarna_order->fetch();

			$update['merchant']['push_uri']         = add_query_arg( array( 'sid' => $orderid ), $klarna_order['merchant']['push_uri'] );
			$update['merchant']['confirmation_uri'] = add_query_arg(
				array(
					'sid'            => $orderid,
					'order-received' => $orderid,
				), $klarna_order['merchant']['confirmation_uri']
			);

			$klarna_order->update( $update );
		}

		$recalculate_shipping = false;

		// Capture country
		if ( isset( $_REQUEST['country'] ) && is_string( $_REQUEST['country'] ) ) {
			$recalculate_shipping = true;

			switch ( $_REQUEST['country'] ) {
				case 'swe':
					$req_country = 'SE';
					break;
				case 'nor':
					$req_country = 'NO';
					break;
				case 'fin':
					$req_country = 'FI';
					break;
				case 'aut':
					$req_country = 'AT';
					break;
				case 'deu':
				case 'get':
					$req_country = 'DE';
					break;
			}

			if ( $req_country ) {
				if ( is_callable( array( WC()->customer, 'set_billing_country' ) ) ) {
					WC()->customer->set_billing_country( $req_country );
				} else {
					WC()->customer->set_country( $req_country );
				}

				if ( ! WC()->session->get( 'klarna_separate_shipping' ) ) {
					WC()->customer->set_shipping_country( $req_country );
				}
			}
		}

		// Capture postal code.
		if ( isset( $_REQUEST['postal_code'] ) && is_string( $_REQUEST['postal_code'] ) && WC_Validation::is_postcode( $_REQUEST['postal_code'], $klarna_country ) ) {
			$recalculate_shipping = true;

			WC()->customer->set_billing_postcode( $_REQUEST['postal_code'] );

			if ( ! WC()->session->get( 'klarna_separate_shipping' ) ) {
				WC()->customer->set_shipping_postcode( $_REQUEST['postal_code'] );
			}
		}

		if ( $recalculate_shipping ) {
			if ( is_callable( array( WC()->customer, 'save' ) ) ) {
				WC()->customer->save();
			}

			// Update user session.
			WC()->cart->calculate_shipping();
			WC()->cart->calculate_fees();
			WC()->cart->calculate_totals();

			// Update ongoing WooCommerce order.
			$this->update_or_create_local_order();
			$data['widget_html'] .= $this->klarna_checkout_get_kco_widget_html();

			if ( WC()->session->get( 'klarna_checkout' ) ) {
				$this->ajax_update_klarna_order();
			}
		}

		if ( $klarna_debug == 'yes' ) {
			$klarna_order = WC()->session->get( 'ongoing_klarna_order' );
			krokedil_log_events( $klarna_order, 'Iframe change CB billing country', WC()->customer->get_billing_country() );
			krokedil_log_events( $klarna_order, 'Iframe change CB billing postcode', WC()->customer->get_billing_postcode() );
			krokedil_log_events( $klarna_order, 'Iframe change CB shipping country', WC()->customer->get_shipping_country() );
			krokedil_log_events( $klarna_order, 'Iframe change CB shipping postcode', WC()->customer->get_shipping_postcode() );
		}

		wp_send_json_success( $data );
		wp_die();
	}

	/**
	 * Klarna order shipping address change callback function (V2).
	 *
	 * @since  2.0
	 **/
	function kco_iframe_shipping_address_change_v2_cb() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'klarna_checkout_nonce' ) ) {
			exit( 'Nonce can not be verified.' );
		}

		$data = array();

		// Capture postal code.
		if ( isset( $_REQUEST['postal_code'] ) && is_string( $_REQUEST['postal_code'] ) ) {
			WC()->customer->set_shipping_postcode( $_REQUEST['postal_code'] );

			if ( is_callable( array( WC()->customer, 'save' ) ) ) {
				WC()->customer->save();
			}

			WC()->session->set( 'klarna_separate_shipping', true );
		}

		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}

		WC()->cart->calculate_shipping();
		WC()->cart->calculate_fees();
		WC()->cart->calculate_totals();

		$this->update_or_create_local_order();
		$data['widget_html'] = $this->klarna_checkout_get_kco_widget_html();

		if ( WC()->session->get( 'klarna_checkout' ) ) {
			$this->ajax_update_klarna_order();
		}

		wp_send_json_success( $data );
		wp_die();
	}

	/**
	 * Klarna order shipping address change callback function (V3).
	 *
	 * @since  2.0
	 **/
	function kco_iframe_shipping_address_change_cb() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'klarna_checkout_nonce' ) ) {
			exit( 'Nonce can not be verified.' );
		}

		$data                = array();
		$data['widget_html'] = '';

		// Add customer data to WC_Order
		$orderid = WC()->session->get( 'ongoing_klarna_order' );
		$order   = wc_get_order( $orderid );
		if ( $order ) {
			$address = array();

			if ( isset( $_REQUEST['given_name'] ) && is_string( $_REQUEST['given_name'] ) ) {
				$address['first_name'] = $_REQUEST['given_name'];
			}

			if ( isset( $_REQUEST['family_name'] ) && is_string( $_REQUEST['family_name'] ) ) {
				$address['last_name'] = $_REQUEST['family_name'];
			}

			if ( isset( $_REQUEST['street_address'] ) && is_string( $_REQUEST['street_address'] ) ) {
				$address['address_1'] = $_REQUEST['street_address'];
			}

			if ( isset( $_REQUEST['street_address2'] ) && is_string( $_REQUEST['street_address2'] ) ) {
				$address['address_2'] = $_REQUEST['street_address2'];
			}

			if ( isset( $_REQUEST['city'] ) && is_string( $_REQUEST['city'] ) ) {
				$address['city'] = $_REQUEST['city'];
			}

			if ( isset( $_REQUEST['region'] ) && is_string( $_REQUEST['region'] ) ) {
				$address['state'] = $_REQUEST['region'];
			}

			if ( isset( $_REQUEST['postal_code'] ) && is_string( $_REQUEST['postal_code'] ) ) {
				$address['postcode'] = $_REQUEST['postal_code'];
			}

			if ( isset( $_REQUEST['country'] ) && is_string( $_REQUEST['country'] ) ) {
				$address['country'] = $_REQUEST['country'];
			}

			if ( isset( $_REQUEST['email'] ) && is_string( $_REQUEST['email'] ) ) {
				$address['email'] = $_REQUEST['email'];
			}

			if ( isset( $_REQUEST['phone'] ) && is_string( $_REQUEST['phone'] ) ) {
				$address['phone'] = $_REQUEST['phone'];
			}

			$order->set_address( $address );
			$order->set_address( $address, 'shipping' );
			if ( is_callable( array( $order, 'save' ) ) ) {
				$order->save();
			}
		}

		// Capture postal code
		if ( isset( $_REQUEST['postal_code'] ) && is_string( $_REQUEST['postal_code'] ) ) {
			if ( method_exists( WC()->customer, 'set_billing_postcode' ) ) {
				WC()->customer->set_billing_postcode( $_REQUEST['postal_code'] );
			} else {
				WC()->customer->set_postcode( $_REQUEST['postal_code'] );
			}
			WC()->customer->set_shipping_postcode( $_REQUEST['postal_code'] );
		}

		if ( isset( $_REQUEST['region'] ) && is_string( $_REQUEST['region'] ) ) {
			if ( method_exists( WC()->customer, 'set_billing_state' ) ) {
				WC()->customer->set_billing_state( $_REQUEST['region'] );
			} else {
				WC()->customer->set_state( $_REQUEST['region'] );
			}

			WC()->customer->set_shipping_state( $_REQUEST['region'] );
		}

		if ( isset( $_REQUEST['country'] ) && is_string( $_REQUEST['country'] ) ) {
			if ( 'gbr' == $_REQUEST['country'] ) {
				$country = 'GB';
			} elseif ( 'usa' == $_REQUEST['country'] ) {
				$country = 'US';
			} elseif ( 'dnk' == $_REQUEST['country'] ) {
				$country = 'DK';
			} elseif ( 'nld' == $_REQUEST['country'] ) {
				$country = 'NL';
			}

			if ( is_callable( array( WC()->customer, 'set_billing_country' ) ) ) {
				WC()->customer->set_billing_country( $country );
			} else {
				WC()->customer->set_country( $country );
			}
			WC()->customer->set_shipping_country( $country );
		}

		// Check coupons.
		if ( isset( $_REQUEST['email'] ) && is_email( $_REQUEST['email'] ) ) {
			if ( is_callable( array( WC()->customer, 'set_billing_email' ) ) ) {
				if ( 'guest_checkout@klarna.com' !== $_REQUEST['email'] ) {
					WC()->customer->set_billing_email( $_REQUEST['email'] );
				}
			}

			if ( count( WC()->cart->get_applied_coupons() ) > 0 ) {
				if ( WC()->customer->get_billing_email() ) {
					$coupons_before = count( WC()->cart->get_applied_coupons() );
					WC()->cart->check_customer_coupons( array( 'billing_email' => WC()->customer->get_billing_email() ) );
					if ( count( WC()->cart->get_applied_coupons() ) < $coupons_before ) {
						$coupon               = new WC_Coupon();
						$data['widget_html'] .= '<div class="woocommerce-error">' . $coupon->get_coupon_error( WC_Coupon::E_WC_COUPON_USAGE_LIMIT_REACHED ) . '</div>';
					}
				}
			}
		}

		if ( version_compare( WOOCOMMERCE_VERSION, '3.0', '>=' ) ) {
			WC()->customer->save();
		}

		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}

		WC()->cart->calculate_shipping();
		WC()->cart->calculate_fees();
		WC()->cart->calculate_totals();

		$order_id = $this->update_or_create_local_order();

		if ( version_compare( WOOCOMMERCE_VERSION, '3.0', '>=' ) ) {
			$order = wc_get_order( $order_id );

			if ( isset( $_REQUEST['city'] ) && is_string( $_REQUEST['city'] ) ) {
				$order->set_billing_city( $_REQUEST['city'] );
			}

			if ( isset( $_REQUEST['country'] ) && is_string( $_REQUEST['country'] ) ) {
				if ( 'gbr' == $_REQUEST['country'] ) {
					$order->set_billing_country( 'GB' );
					$order->set_shipping_country( 'GB' );
				} elseif ( 'usa' == $_REQUEST['country'] ) {
					$order->set_billing_country( 'US' );
					$order->set_shipping_country( 'US' );
				} elseif ( 'dnk' == $_REQUEST['country'] ) {
					$order->set_billing_country( 'DK' );
					$order->set_shipping_country( 'DK' );
				} elseif ( 'nld' == $_REQUEST['country'] ) {
					$order->set_billing_country( 'NL' );
					$order->set_shipping_country( 'NL' );
				}
			}

			if ( is_callable( array( WC()->customer, 'set_billing_email' ) ) ) {
				if ( isset( $_REQUEST['email'] ) && is_string( $_REQUEST['email'] ) ) {
					$order->set_billing_email( $_REQUEST['email'] );
				}
			}

			if ( isset( $_REQUEST['given_name'] ) && is_string( $_REQUEST['given_name'] ) ) {
				$order->set_billing_first_name( $_REQUEST['given_name'] );
			}

			if ( isset( $_REQUEST['family_name'] ) && is_string( $_REQUEST['family_name'] ) ) {
				$order->set_billing_last_name( $_REQUEST['family_name'] );
			}

			if ( isset( $_REQUEST['phone'] ) && is_string( $_REQUEST['phone'] ) ) {
				$order->set_billing_phone( $_REQUEST['phone'] );
			}

			if ( isset( $_REQUEST['postal_code'] ) && is_string( $_REQUEST['postal_code'] ) ) {
				$order->set_billing_postcode( $_REQUEST['postal_code'] );
				$order->set_shipping_postcode( $_REQUEST['postal_code'] );
			}

			if ( isset( $_REQUEST['region'] ) && is_string( $_REQUEST['region'] ) ) {
				$order->set_billing_state( $_REQUEST['region'] );
				$order->set_shipping_state( $_REQUEST['region'] );
			}
			if ( isset( $_REQUEST['street_address'] ) && is_string( $_REQUEST['street_address'] ) ) {
				$order->set_billing_address_1( $_REQUEST['street_address'] );
			}

			$order->calculate_totals();
			$order->save();
		}

		$data['widget_html'] .= $this->klarna_checkout_get_kco_widget_html();

		if ( WC()->session->get( 'klarna_checkout' ) ) {
			$this->ajax_update_klarna_order();
		}

		wp_send_json_success( $data );
		wp_die();
	}

	// Helpers
	/**
	 * Creates a WooCommerce order, or updates if already created
	 *
	 * @since 1.0.0
	 */
	function update_or_create_local_order( $customer_email = null ) {
		if ( is_user_logged_in() ) {
			global $current_user;
			$customer_email = $current_user->user_email;
		}

		if ( ! $customer_email ) {
			$customer_email = 'guest_checkout@klarna.com';
		}

		if ( ! is_email( $customer_email ) ) {
			return;
		}

		// Check quantities
		$result = WC()->cart->check_cart_item_stock();

		if ( is_wp_error( $result ) ) {
			return $result->get_error_message();
		}

		// Update the local order
		include_once KLARNA_DIR . 'classes/class-klarna-to-wc.php';

		$klarna_to_wc = new WC_Gateway_Klarna_K2WC();
		$klarna_to_wc->set_rest( WC_Gateway_Klarna_Checkout_Variables::is_rest() );
		$klarna_to_wc->set_eid( WC_Gateway_Klarna_Checkout_Variables::get_klarna_eid() );
		$klarna_to_wc->set_secret( WC_Gateway_Klarna_Checkout_Variables::get_klarna_secret() );
		$klarna_to_wc->set_klarna_log( WC_Gateway_Klarna_Checkout_Variables::get_klarna_checkout_log() );
		$klarna_to_wc->set_klarna_debug( WC_Gateway_Klarna_Checkout_Variables::get_klarna_checkout_debug() );
		$klarna_to_wc->set_klarna_test_mode( WC_Gateway_Klarna_Checkout_Variables::get_klarna_checkout_testmode() );
		$klarna_to_wc->set_klarna_server( WC_Gateway_Klarna_Checkout_Variables::get_klarna_server() );
		$klarna_to_wc->set_klarna_credentials_country( WC_Gateway_Klarna_Checkout_Variables::get_klarna_country() );

		if ( $customer_email ) {
			$orderid = $klarna_to_wc->prepare_wc_order( $customer_email );
		} else {
			$orderid = $klarna_to_wc->prepare_wc_order();
		}

		return $orderid;
	}

	/**
	 * Gets Klarna checkout widget HTML.
	 * Used in KCO widget.
	 *
	 * @return String shortcode output
	 *
	 * @since  2.0
	 **/
	function klarna_checkout_get_kco_widget_html() {
		$klarna_shortcodes = new WC_Gateway_Klarna_Shortcodes();

		return $klarna_shortcodes->klarna_checkout_get_kco_widget_html();
	}

	/**
	 * Pushes Klarna order update in AJAX calls.
	 *
	 * @since  2.0
	 **/
	function ajax_update_klarna_order() {
		$settings        = WC_Gateway_Klarna_Checkout_Variables::get_klarna_checkout_settings();
		$klarna_eid      = WC_Gateway_Klarna_Checkout_Variables::get_klarna_eid();
		$klarna_secret   = WC_Gateway_Klarna_Checkout_Variables::get_klarna_secret();
		$klarna_is_rest  = WC_Gateway_Klarna_Checkout_Variables::is_rest();
		$klarna_country  = WC_Gateway_Klarna_Checkout_Variables::get_klarna_country();
		$klarna_testmode = WC_Gateway_Klarna_Checkout_Variables::get_klarna_checkout_testmode();
		$klarna_server   = WC_Gateway_Klarna_Checkout_Variables::get_klarna_server();
		$klarna_debug    = WC_Gateway_Klarna_Checkout_Variables::get_klarna_checkout_debug();
		// $klarna_log = WC_Gateway_Klarna_Checkout_Variables::get_klarna_checkout_log();
		// Check if Euro is selected, get correct country
		if ( 'EUR' == get_woocommerce_currency() && WC()->session->get( 'klarna_euro_country' ) ) {
			$klarna_c = strtolower( WC()->session->get( 'klarna_euro_country' ) );

			if ( in_array( strtoupper( $klarna_c ), array( 'DE', 'FI', 'NL' ), true ) ) {
				// Add correct EID & secret specific to country if the curency is EUR and the country is DE or FI.
				$eid          = $settings[ "eid_$klarna_c" ];
				$sharedSecret = html_entity_decode( $settings[ "secret_$klarna_c" ] );
			} else {
				// Otherwise use the general eid and secret (filterable) if we're using EUR as currency for a global KCO checkout
				$eid          = $klarna_eid;
				$sharedSecret = html_entity_decode( $klarna_secret );
			}
		} else {
			$eid          = $klarna_eid;
			$sharedSecret = html_entity_decode( $klarna_secret );
		}

		if ( $klarna_is_rest ) {
			if ( $klarna_testmode === 'yes' ) {
				if ( in_array( strtoupper( $klarna_country ), apply_filters( 'klarna_is_rest_countries_eu', array( 'DK', 'GB', 'NL' ) ), true ) ) {
					$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_TEST_BASE_URL;
				} elseif ( in_array( strtoupper( $klarna_country ), apply_filters( 'klarna_is_rest_countries_na', array( 'US' ) ), true ) ) {
					$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_TEST_BASE_URL;
				}
			} else {
				if ( in_array( strtoupper( $klarna_country ), apply_filters( 'klarna_is_rest_countries_eu', array( 'DK', 'GB', 'NL' ) ), true ) ) {
					$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_BASE_URL;
				} elseif ( in_array( strtoupper( $klarna_country ), apply_filters( 'klarna_is_rest_countries_na', array( 'US' ) ), true ) ) {
					$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_BASE_URL;
				}
			}

			$klarna_order_id = WC()->session->get( 'klarna_checkout' );
			$connector       = Klarna\Rest\Transport\Connector::create( $eid, $sharedSecret, $klarna_server_url );
			$klarna_order    = new \Klarna\Rest\Checkout\Order( $connector, $klarna_order_id );
		} else {
			$connector    = Klarna_Checkout_Connector::create( $sharedSecret, $klarna_server );
			$klarna_order = new Klarna_Checkout_Order( $connector, WC()->session->get( 'klarna_checkout' ) );
			$klarna_order->fetch();
		}

		// Process cart contents and prepare them for Klarna
		include_once KLARNA_DIR . 'classes/class-wc-to-klarna.php';

		$wc_to_klarna = new WC_Gateway_Klarna_WC2K( $klarna_is_rest, $klarna_country );
		$cart         = $wc_to_klarna->process_cart_contents();

		if ( 0 === count( $cart ) ) {
			$klarna_order = null;
		} else {
			// Reset cart
			if ( $klarna_is_rest ) {
				$update['order_lines'] = array();
				$klarna_order_total    = 0;
				$klarna_tax_total      = 0;

				foreach ( $cart as $item ) {
					$update['order_lines'][] = $item;
					$klarna_order_total     += $item['total_amount'];
					// Process sales_tax item differently
					if ( array_key_exists( 'type', $item ) && 'sales_tax' == $item['type'] ) {
						$klarna_tax_total += $item['total_amount'];
					} else {
						$klarna_tax_total += $item['total_tax_amount'];
					}
				}
				$update['order_amount']     = $klarna_order_total;
				$update['order_tax_amount'] = $klarna_tax_total;
			} else {
				$update['cart']['items'] = array();

				foreach ( $cart as $item ) {
					$update['cart']['items'][] = $item;
				}
			}
			WC_Gateway_Klarna::log( 'Update request order data: ' . stripslashes_deep( wp_json_encode( $update ) ) );
			try {
				$klarna_order->update( apply_filters( 'kco_update_order', $update ) );
			} catch ( Exception $e ) {
				if ( $klarna_debug == 'yes' ) {
					krokedil_log_events( $klarna_order_id, 'AJAX update Klarna order exception', $e->getCode() . ' - ' . $e->getMessage() );
				}
			}
		}
	}

}

$wc_gateway_klarna_checkout_ajax = new WC_Gateway_Klarna_Checkout_Ajax();
