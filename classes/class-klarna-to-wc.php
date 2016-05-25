<?php
/**
 * Formats WC data for creating/updating Klarna orders
 *
 * @link  http://www.woothemes.com/products/klarna/
 * @since 2.0.0
 *
 * @package WC_Gateway_Klarna
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * This class grabs WC cart contents and formats them so they can
 * be sent to Klarna when a KCO order is being created or updated.
 *
 * Needs Klarna order object passed as parameter
 * Checks if Rest API is in use
 * WC log class needs to be instantiated
 *
 * Get customer data
 * Create WC order
 * Add order items
 * Add order note
 * Add order fees
 * Add order shipping
 * Add order addresses
 * Add order tax rows - ?
 * Add order coupons
 * Add order payment method
 * EITHER Store customer (user) ID as post meta
 * OR     Maybe create customer account
 * Empty WooCommerce cart
 *
 */
class WC_Gateway_Klarna_K2WC {

	/**
	 * Is this for Rest API.
	 *
	 * @since  2.0.0
	 * @access public
	 * @var    boolean
	 */
	public $is_rest;

	/**
	 * Klarna Eid.
	 *
	 * @since  2.0.0
	 * @access public
	 * @var    string
	 */
	public $eid;

	/**
	 * Klarna secret.
	 *
	 * @since  2.0.0
	 * @access public
	 * @var    string
	 */
	public $secret;

	/**
	 * Klarna order URI / ID.
	 *
	 * @since  2.0.0
	 * @access public
	 * @var    string
	 */
	public $klarna_order_uri;

	/**
	 * Klarna log object.
	 *
	 * @since  2.0.0
	 * @access public
	 * @var    object
	 */
	public $klarna_log;

	/**
	 * Klarna debug.
	 *
	 * @since  2.0.0
	 * @access public
	 * @var    string, yes or no
	 */
	public $klarna_debug;

	/**
	 * Klarna test mode.
	 *
	 * @since  2.0.0
	 * @access public
	 * @var    string, yes or no
	 */
	public $klarna_test_mode;

	/**
	 * Klarna server URI.
	 *
	 * @since  2.0.0
	 * @access public
	 * @var    string, yes or no
	 */
	public $klarna_server;

	/**
	 * Set is_rest value
	 *
	 * @since 2.0.0
	 */
	public function set_rest( $is_rest ) {
		$this->is_rest = $is_rest;
	}

	/**
	 * Set eid
	 *
	 * @since 2.0.0
	 */
	public function set_eid( $eid ) {
		$this->eid = $eid;
	}

	/**
	 * Set secret
	 *
	 * @since 2.0.0
	 */
	public function set_secret( $secret ) {
		$this->secret = $secret;
	}

	/**
	 * Set klarna_order_uri
	 *
	 * @since 2.0.0
	 */
	public function set_klarna_order_uri( $klarna_order_uri ) {
		$this->klarna_order_uri = $klarna_order_uri;
	}

	/**
	 * Set klarna_log
	 *
	 * @since 2.0.0
	 */
	public function set_klarna_log( $klarna_log ) {
		$this->klarna_log = $klarna_log;
	}

	/**
	 * Set klarna_debug
	 *
	 * @since 2.0.0
	 */
	public function set_klarna_debug( $klarna_debug ) {
		$this->klarna_debug = $klarna_debug;
	}

	/**
	 * Set klarna_debug
	 *
	 * @since 2.0.0
	 */
	public function set_klarna_test_mode( $klarna_test_mode ) {
		$this->klarna_test_mode = $klarna_test_mode;
	}

	/**
	 * Set klarna_server
	 *
	 * @since 2.0.0
	 */
	public function set_klarna_server( $klarna_server ) {
		$this->klarna_server = $klarna_server;
	}

	/**
	 * Prepares local order.
	 *
	 * Creates local order on Klarna's push notification.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  $customer_email KCO incomplete customer email
	 */
	public function prepare_wc_order( $customer_email ) {
		global $woocommerce;

		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}

		if ( $woocommerce->session->get( 'ongoing_klarna_order' ) && wc_get_order( $woocommerce->session->get( 'ongoing_klarna_order' ) ) ) {
			$orderid = $woocommerce->session->get( 'ongoing_klarna_order' );
			$order   = wc_get_order( $orderid );
		} else {
			// Create order in WooCommerce if we have an email
			$order = $this->create_order();
			update_post_meta( $order->id, '_kco_incomplete_customer_email', $customer_email, true );
			$woocommerce->session->set( 'ongoing_klarna_order', $order->id );
		}

		// If there's an order at this point, proceed
		if ( isset( $order ) ) {
			// Need to clean up the order first, to avoid duplicate items
			$order->remove_order_items();

			// Add order items
			$this->add_order_items( $order );

			// Add order fees
			$this->add_order_fees( $order );

			// Add order shipping
			$this->add_order_shipping( $order );

			// Add order taxes
			$this->add_order_tax_rows( $order );

			// Store coupons
			$this->add_order_coupons( $order );

			// Store payment method
			$this->add_order_payment_method( $order );

			// Calculate order totals
			$this->set_order_totals( $order );

			// Tie this order to a user
			if ( email_exists( $customer_email ) ) {
				$user    = get_user_by( 'email', $customer_email );
				$user_id = $user->ID;
				update_post_meta( $order->id, '_customer_user', $user_id );
			}

			// Let plugins add meta
			do_action( 'woocommerce_checkout_update_order_meta', $order->id, array() );

			// Store which KCO API was used
			if ( $this->is_rest ) {
				update_post_meta( $order->id, '_klarna_api', 'rest' );
			} else {
				update_post_meta( $order->id, '_klarna_api', 'v2' );
			}

			return $order->id;
		}
	}

	/**
	 * KCO listener function.
	 *
	 * Creates local order on Klarna's push notification.
	 *
	 * @since  2.0.0
	 * @access public
	 */
	public function listener() {
		if ( $this->klarna_debug == 'yes' ) {
			$this->klarna_log->add( 'klarna', 'Listener triggered...' );
		}

		global $woocommerce;

		// Retrieve Klarna order
		$klarna_order = $this->retrieve_klarna_order();

		// Check if order has been completed by Klarna, for V2 and Rest
		if ( $klarna_order['status'] == 'checkout_complete' || $klarna_order['status'] == 'AUTHORIZED' ) {
			$local_order_id = sanitize_key( $_GET['sid'] );
			$order          = wc_get_order( $local_order_id );

			// Check if order was recurring
			if ( isset( $klarna_order['recurring_token'] ) ) {
				update_post_meta( $order->id, '_klarna_recurring_token', $klarna_order['recurring_token'] );
			}

			if ( sanitize_key( $_GET['klarna-api'] ) && 'rest' == sanitize_key( $_GET['klarna-api'] ) ) {
				update_post_meta( $order->id, '_klarna_order_id', $klarna_order['order_id'] );
				$order->add_order_note( sprintf( __( 'Klarna order ID: %s.', 'woocommerce-gateway-klarna' ), $klarna_order['order_id'] ) );

			} else {
				update_post_meta( $order->id, '_klarna_order_reservation', $klarna_order['reservation'] );
			}

			// Change order currency
			$this->change_order_currency( $order, $klarna_order );

			// Add order addresses
			$this->add_order_addresses( $order, $klarna_order );

			// Store payment method
			$this->add_order_payment_method( $order );

			// Add order customer info
			$this->add_order_customer_info( $order, $klarna_order );

			// Confirm the order in Klarnas system
			$klarna_order = $this->confirm_klarna_order( $order, $klarna_order );

			$order->calculate_totals( false );

			// Other plugins need this hook
			do_action( 'woocommerce_checkout_order_processed', $order->id, false );

			// Process subscriptions for order
			if ( class_exists( 'WC_Subscriptions_Checkout' ) && get_post_meta( $order->id, '_klarna_recurring_carts', true ) ) {

				// First clear out any subscriptions created for a failed payment to give us a clean slate for creating new subscriptions
				$subscriptions = wcs_get_subscriptions_for_order( $order->id, array( 'order_type' => 'parent' ) );
				if ( ! empty( $subscriptions ) ) {
					foreach ( $subscriptions as $subscription ) {
						wp_delete_post( $subscription->id );
					}
				}

				// Create new subscriptions for each group of subscription products in the cart (that is not a renewal)
				foreach ( get_post_meta( $order->id, '_klarna_recurring_carts', true ) as $recurring_cart ) {
					$subscription = WC_Subscriptions_Checkout::create_subscription( $order, $recurring_cart ); // Exceptions are caught by WooCommerce

					$subscription->payment_complete();

					if ( is_wp_error( $subscription ) ) {
						throw new Exception( $subscription->get_error_message() );
					}

					do_action( 'woocommerce_checkout_subscription_created', $subscription, $order,
						$recurring_cart );
				}

				delete_post_meta( $order->id, '_klarna_recurring_carts' );

				do_action( 'subscriptions_created_for_order', $order->id ); // Backward compatibility

			}





			// Other plugins and themes can hook into here
			do_action( 'klarna_after_kco_push_notification', $order->id );
		}
	}

	/**
	 * Fetch KCO order.
	 *
	 * @since  2.0.0
	 * @access public
	 */
	public function retrieve_klarna_order() {
		if ( $this->klarna_debug == 'yes' ) {
			$this->klarna_log->add( 'klarna', 'Klarna order - ' . $this->klarna_order_uri );
		}

		if ( sanitize_key( $_GET['klarna-api'] ) && 'rest' == sanitize_key( $_GET['klarna-api'] ) ) {
			$klarna_country = sanitize_key( $_GET['scountry'] );

			if ( $this->klarna_test_mode == 'yes' ) {
				if ( 'gb' == $klarna_country ) {
					$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_TEST_BASE_URL;
				} elseif ( 'us' == $klarna_country ) {
					$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_TEST_BASE_URL;
				}
			} else {
				if ( 'gb' == $klarna_country ) {
					$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_BASE_URL;
				} elseif ( 'us' == $klarna_country ) {
					$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_BASE_URL;
				}
			}

			$connector = \Klarna\Rest\Transport\Connector::create( $this->eid, $this->secret, $klarna_server_url );

			$klarna_order = new \Klarna\Rest\OrderManagement\Order( $connector, $this->klarna_order_uri );
		} else {
			$connector    = Klarna_Checkout_Connector::create( $this->secret, $this->klarna_server );
			$checkoutId   = $this->klarna_order_uri;
			$klarna_order = new Klarna_Checkout_Order( $connector, $checkoutId );
		}

		$klarna_order->fetch();

		return $klarna_order;
	}

	/**
	 * Create WC order.
	 *
	 * @since  2.0.0
	 * @access public
	 */
	public function create_order() {
		if ( $this->klarna_debug == 'yes' ) {
			$this->klarna_log->add( 'klarna', 'Creating local order...' );
		}
		global $woocommerce;

		// Customer accounts
		$customer_id = apply_filters( 'woocommerce_checkout_customer_id', get_current_user_id() );

		// Order data
		$order_data = array(
			'status'      => apply_filters( 'klarna_checkout_incomplete_order_status', 'kco-incomplete' ),
			'customer_id' => $customer_id,
			'created_via' => 'klarna_checkout'
		);

		// Create the order
		$order = wc_create_order( $order_data );

		if ( is_wp_error( $order ) ) {
			throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
		}

		if ( $this->klarna_debug == 'yes' ) {
			$this->klarna_log->add( 'klarna', 'Local order created, order ID: ' . $order->id );
		}

		return $order;
	}

	/**
	 * Changes local order currency.
	 *
	 * When Aelia currency switcher is used, default store currency is always saved.
	 *
	 * @since  2.0.0
	 * @access public
	 */
	public function change_order_currency( $order, $klarna_order ) {
		if ( $this->klarna_debug == 'yes' ) {
			$this->klarna_log->add( 'klarna', 'Maybe fixing order currency...' );
		}

		if ( $order->get_order_currency != strtoupper( $klarna_order['purchase_currency'] ) ) {
			if ( $this->klarna_debug == 'yes' ) {
				$this->klarna_log->add( 'klarna', 'Updating order currency...' );
			}
			update_post_meta( $order->id, '_order_currency', strtoupper( $klarna_order['purchase_currency'] ) );
		}
	}

	/**
	 * Adds order items to local order.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  object $order Local WC order.
	 *
	 * @throws Exception
	 */
	public function add_order_items( $order ) {
		if ( $this->klarna_debug == 'yes' ) {
			$this->klarna_log->add( 'klarna', 'Adding order items...' );
		}
		global $woocommerce;
		$order_id = $order->id;

		foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
			$item_id = $order->add_product( $values['data'], $values['quantity'], array(
				'variation' => $values['variation'],
				'totals'    => array(
					'subtotal'     => $values['line_subtotal'],
					'subtotal_tax' => $values['line_subtotal_tax'],
					'total'        => $values['line_total'],
					'tax'          => $values['line_tax'],
					'tax_data'     => $values['line_tax_data'] // Since 2.2
				)
			) );

			if ( ! $item_id ) {
				if ( $this->klarna_debug == 'yes' ) {
					$this->klarna_log->add( 'klarna', 'Unable to add order item.' );
				}
				throw new Exception( __( 'Error: Unable to add order item. Please try again.', 'woocommerce' ) );
			}

			// Allow plugins to add order item meta
			do_action( 'woocommerce_add_order_item_meta', $item_id, $values, $cart_item_key );
		}
	}

	/**
	 * Adds order fees to local order.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  object $order Local WC order.
	 *
	 * @throws Exception
	 */
	public function add_order_fees( $order ) {
		if ( $this->klarna_debug == 'yes' ) {
			$this->klarna_log->add( 'klarna', 'Adding order fees...' );
		}
		global $woocommerce;
		$order_id = $order->id;

		foreach ( $woocommerce->cart->get_fees() as $fee_key => $fee ) {
			$item_id = $order->add_fee( $fee );

			if ( ! $item_id ) {
				if ( $this->klarna_debug == 'yes' ) {
					$this->klarna_log->add( 'klarna', 'Unable to add order fee.' );
				}
				throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
			}

			// Allow plugins to add order item meta to fees
			do_action( 'woocommerce_add_order_fee_meta', $order_id, $item_id, $fee, $fee_key );
		}
	}

	/**
	 * Adds order shipping to local order.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  object $order Local WC order.
	 *
	 * @throws Exception
	 * @internal param object $klarna_order Klarna order.
	 */
	public function add_order_shipping( $order ) {
		if ( $this->klarna_debug == 'yes' ) {
			$this->klarna_log->add( 'klarna', 'Adding order shipping...' );
		}
		global $woocommerce;

		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}
		$woocommerce->cart->calculate_shipping();
		$woocommerce->cart->calculate_fees();
		$woocommerce->cart->calculate_totals();

		$order_id              = $order->id;
		$this_shipping_methods = $woocommerce->session->get( 'chosen_shipping_methods' );

		// Store shipping for all packages
		foreach ( $woocommerce->shipping->get_packages() as $package_key => $package ) {
			if ( isset( $package['rates'][ $this_shipping_methods[ $package_key ] ] ) ) {
				$item_id = $order->add_shipping( $package['rates'][ $this_shipping_methods[ $package_key ] ] );

				if ( ! $item_id ) {
					if ( $this->klarna_debug == 'yes' ) {
						$this->klarna_log->add( 'klarna', 'Unable to add shipping item.' );
					}
					throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
				}

				// Allows plugins to add order item meta to shipping
				do_action( 'woocommerce_add_shipping_order_item', $order_id, $item_id, $package_key );
			}
		}
	}

	/**
	 * Adds order addresses to local order.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  object $order Local WC order.
	 * @param  object $klarna_order Klarna order.
	 */
	public function add_order_addresses( $order, $klarna_order ) {
		if ( $this->klarna_debug == 'yes' ) {
			$this->klarna_log->add( 'klarna', 'Adding order addresses...' );
			$this->klarna_log->add( 'klarna', var_export( $klarna_order, true ) );
		}

		$order_id = $order->id;

		// Different names on the returned street address if it's a German purchase or not
		$received__billing_address_1  = '';
		$received__shipping_address_1 = '';

		if ( $_GET['scountry'] == 'DE' || $_GET['scountry'] == 'AT' ) {
			$received__billing_address_1  = $klarna_order['billing_address']['street_name'] . ' ' . $klarna_order['billing_address']['street_number'];
			$received__shipping_address_1 = $klarna_order['shipping_address']['street_name'] . ' ' . $klarna_order['shipping_address']['street_number'];
		} else {
			$received__billing_address_1  = $klarna_order['billing_address']['street_address'];
			$received__shipping_address_1 = $klarna_order['shipping_address']['street_address'];
		}

		// Add customer billing address - retrieved from callback from Klarna
		update_post_meta( $order_id, '_billing_first_name', $klarna_order['billing_address']['given_name'] );
		update_post_meta( $order_id, '_billing_last_name', $klarna_order['billing_address']['family_name'] );
		update_post_meta( $order_id, '_billing_address_1', $received__billing_address_1 );
		if ( isset( $klarna_order['billing_address']['care_of'] ) ) {
			update_post_meta( $order_id, '_billing_address_2', $klarna_order['billing_address']['care_of'] );
		}
		update_post_meta( $order_id, '_billing_postcode', $klarna_order['billing_address']['postal_code'] );
		update_post_meta( $order_id, '_billing_city', $klarna_order['billing_address']['city'] );
		update_post_meta( $order_id, '_billing_country', strtoupper( $klarna_order['billing_address']['country'] ) );
		update_post_meta( $order_id, '_billing_email', $klarna_order['billing_address']['email'] );
		update_post_meta( $order_id, '_billing_phone', $klarna_order['billing_address']['phone'] );

		// Add customer shipping address - retrieved from callback from Klarna
		$allow_separate_shipping = ( isset( $klarna_order['options']['allow_separate_shipping_address'] ) ) ? $klarna_order['options']['allow_separate_shipping_address'] : '';

		update_post_meta( $order_id, '_shipping_first_name', $klarna_order['shipping_address']['given_name'] );
		update_post_meta( $order_id, '_shipping_last_name', $klarna_order['shipping_address']['family_name'] );
		update_post_meta( $order_id, '_shipping_address_1', $received__shipping_address_1 );
		if ( isset( $klarna_order['shipping_address']['care_of'] ) ) {
			update_post_meta( $order_id, '_shipping_address_2', $klarna_order['shipping_address']['care_of'] );
		}
		update_post_meta( $order_id, '_shipping_postcode', $klarna_order['shipping_address']['postal_code'] );
		update_post_meta( $order_id, '_shipping_city', $klarna_order['shipping_address']['city'] );
		update_post_meta( $order_id, '_shipping_country', strtoupper( $klarna_order['shipping_address']['country'] ) );

		// Store Klarna locale
		update_post_meta( $order_id, '_klarna_locale', $klarna_order['locale'] );
	}

	/**
	 * Adds order tax rows to local order.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  object $order Local WC order.
	 */
	public function add_order_tax_rows( $order ) {
		if ( $this->klarna_debug == 'yes' ) {
			$this->klarna_log->add( 'klarna', 'Adding order tax...' );
		}

		// Store tax rows
		foreach ( array_keys( WC()->cart->taxes + WC()->cart->shipping_taxes ) as $tax_rate_id ) {
			if ( $tax_rate_id && ! $order->add_tax( $tax_rate_id, WC()->cart->get_tax_amount( $tax_rate_id ), WC()->cart->get_shipping_tax_amount( $tax_rate_id ) ) && apply_filters( 'woocommerce_cart_remove_taxes_zero_rate_id', 'zero-rated' ) !== $tax_rate_id ) {
				if ( $this->klarna_debug == 'yes' ) {
					$this->klarna_log->add( 'klarna', 'Unable to add taxes.' );
				}
				throw new Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce' ), 405 ) );
			}
		}
	}

	/**
	 * Adds order coupons to local order.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  object $order Local WC order.
	 *
	 * @throws Exception
	 */
	public function add_order_coupons( $order ) {
		if ( $this->klarna_debug == 'yes' ) {
			$this->klarna_log->add( 'klarna', 'Adding order coupons...' );
		}

		global $woocommerce;

		foreach ( $woocommerce->cart->get_coupons() as $code => $coupon ) {
			if ( ! $order->add_coupon( $code, $woocommerce->cart->get_coupon_discount_amount( $code ) ) ) {
				if ( $this->klarna_debug == 'yes' ) {
					$this->klarna_log->add( 'klarna', 'Unable to add coupons.' );
				}
				throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
			}
		}
	}

	/**
	 * Adds payment method to local order.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  object $order Local WC order.
	 *
	 * @internal param object $klarna_order Klarna order.
	 */
	public function add_order_payment_method( $order ) {
		if ( $this->klarna_debug == 'yes' ) {
			$this->klarna_log->add( 'klarna', 'Adding order payment method...' );
		}

		global $woocommerce;

		$available_gateways = $woocommerce->payment_gateways->payment_gateways();
		$payment_method     = $available_gateways['klarna_checkout'];

		$order->set_payment_method( $payment_method );
	}

	/**
	 * Set local order totals.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  object $order Local WC order.
	 */
	public function set_order_totals( $order ) {
		if ( $this->klarna_debug == 'yes' ) {
			$this->klarna_log->add( 'klarna', 'Setting order totals...' );
		}

		global $woocommerce;

		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}

		$woocommerce->cart->calculate_shipping();
		$woocommerce->cart->calculate_fees();
		$woocommerce->cart->calculate_totals();

		$order->set_total( $woocommerce->cart->shipping_total, 'shipping' );
		$order->set_total( $woocommerce->cart->get_cart_discount_total(), 'order_discount' );
		$order->set_total( $woocommerce->cart->get_cart_discount_total(), 'cart_discount' );
		$order->set_total( $woocommerce->cart->tax_total, 'tax' );
		$order->set_total( $woocommerce->cart->shipping_tax_total, 'shipping_tax' );
		$order->set_total( $woocommerce->cart->total );
	}

	/**
	 * Create a new customer
	 *
	 * @param  string $email
	 * @param  string $username
	 * @param  string $password
	 *
	 * @return WP_Error on failure, Int (user ID) on success
	 *
	 * @since 1.0.0
	 */
	function create_new_customer( $email, $username = '', $password = '' ) {
		// Check the e-mail address
		if ( empty( $email ) || ! is_email( $email ) ) {
			return new WP_Error( "registration-error", __( "Please provide a valid email address.", "woocommerce" ) );
		}

		if ( email_exists( $email ) ) {
			return new WP_Error( "registration-error", __( "An account is already registered with your email address. Please login.", "woocommerce" ) );
		}


		// Handle username creation
		$username = sanitize_user( current( explode( '@', $email ) ) );

		// Ensure username is unique
		$append     = 1;
		$o_username = $username;

		while ( username_exists( $username ) ) {
			$username = $o_username . $append;
			$append ++;
		}

		// Handle password creation
		$password           = wp_generate_password();
		$password_generated = true;

		// WP Validation
		$validation_errors = new WP_Error();
		do_action( 'woocommerce_register_post', $username, $email, $validation_errors );
		$validation_errors = apply_filters( 'woocommerce_registration_errors', $validation_errors, $username, $email );
		if ( $validation_errors->get_error_code() ) {
			$this->klarna_log->add( 'klarna', __( 'Customer creation error', 'woocommerce-gateway-klarna' ) . ' - ' . $validation_errors->get_error_code() );

			return 0;
		}

		$new_customer_data = apply_filters( 'woocommerce_new_customer_data', array(
			'user_login' => $username,
			'user_pass'  => $password,
			'user_email' => $email,
			'role'       => 'customer'
		) );

		$customer_id = wp_insert_user( $new_customer_data );

		if ( is_wp_error( $customer_id ) ) {
			$validation_errors->add( "registration-error", '<strong>' . __( 'ERROR', 'woocommerce' ) . '</strong>: ' . __( 'Couldn&#8217;t register you&hellip; please contact us if you continue to have problems.', 'woocommerce' ) );
			$this->klarna_log->add( 'klarna', __( 'Customer creation error', 'woocommerce-gateway-klarna' ) . ' - ' . $validation_errors->get_error_code() );

			return 0;
		}

		// Send New account creation email to customer?
		$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
		if ( 'yes' == $checkout_settings['send_new_account_email'] ) {
			do_action( 'woocommerce_created_customer', $customer_id, $new_customer_data, $password_generated );
		}

		return $customer_id;
	}

	/**
	 * Adds customer info to local order.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  object $order Local WC order.
	 * @param  object $klarna_order Klarna order.
	 */
	public function add_order_customer_info( $order, $klarna_order ) {
		$order_id = $order->id;

		// Store user id in order so the user can keep track of track it in My account
		if ( email_exists( $klarna_order['billing_address']['email'] ) ) {
			if ( $this->klarna_debug == 'yes' ) {
				$this->klarna_log->add( 'klarna', 'Billing email: ' . $klarna_order['billing_address']['email'] );
			}

			$user = get_user_by( 'email', $klarna_order['billing_address']['email'] );

			if ( $this->klarna_debug == 'yes' ) {
				$this->klarna_log->add( 'klarna', 'Customer User ID: ' . $user->ID );
			}

			$this->customer_id = $user->ID;

			update_post_meta( $order->id, '_customer_user', $this->customer_id );
		} else {
			// Create new user
			$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
			if ( 'yes' == $checkout_settings['create_customer_account'] ) {
				$password     = '';
				$new_customer = $this->create_new_customer( $klarna_order['billing_address']['email'], $klarna_order['billing_address']['email'], $password );

				if ( 0 == $new_customer ) { // Creation failed
					$order->add_order_note( sprintf( __( 'Customer creation failed. Check error log for more details.', 'klarna' ) ) );
					$this->customer_id = 0;
				} else { // Creation succeeded
					$order->add_order_note( sprintf( __( 'New customer created (user ID %s).', 'klarna' ), $new_customer, $klarna_order['id'] ) );

					// Add customer billing address - retrieved from callback from Klarna
					update_user_meta( $new_customer, 'billing_first_name', $klarna_order['billing_address']['given_name'] );
					update_user_meta( $new_customer, 'billing_last_name', $klarna_order['billing_address']['family_name'] );
					update_user_meta( $new_customer, 'billing_address_1', $received__billing_address_1 );
					update_user_meta( $new_customer, 'billing_address_2', $klarna_order['billing_address']['care_of'] );
					update_user_meta( $new_customer, 'billing_postcode', $klarna_order['billing_address']['postal_code'] );
					update_user_meta( $new_customer, 'billing_city', $klarna_order['billing_address']['city'] );
					update_user_meta( $new_customer, 'billing_country', $klarna_order['billing_address']['country'] );
					update_user_meta( $new_customer, 'billing_email', $klarna_order['billing_address']['email'] );
					update_user_meta( $new_customer, 'billing_phone', $klarna_order['billing_address']['phone'] );

					// Add customer shipping address - retrieved from callback from Klarna
					$allow_separate_shipping = ( isset( $klarna_order['options']['allow_separate_shipping_address'] ) ) ? $klarna_order['options']['allow_separate_shipping_address'] : '';

					if ( $allow_separate_shipping == 'true' || $_GET['scountry'] == 'DE' || $_GET['scountry'] == 'AT' ) {
						update_user_meta( $new_customer, 'shipping_first_name', $klarna_order['shipping_address']['given_name'] );
						update_user_meta( $new_customer, 'shipping_last_name', $klarna_order['shipping_address']['family_name'] );
						update_user_meta( $new_customer, 'shipping_address_1', $received__shipping_address_1 );
						update_user_meta( $new_customer, 'shipping_address_2', $klarna_order['shipping_address']['care_of'] );
						update_user_meta( $new_customer, 'shipping_postcode', $klarna_order['shipping_address']['postal_code'] );
						update_user_meta( $new_customer, 'shipping_city', $klarna_order['shipping_address']['city'] );
						update_user_meta( $new_customer, 'shipping_country', $klarna_order['shipping_address']['country'] );
					} else {
						update_user_meta( $new_customer, 'shipping_first_name', $klarna_order['billing_address']['given_name'] );
						update_user_meta( $new_customer, 'shipping_last_name', $klarna_order['billing_address']['family_name'] );
						update_user_meta( $new_customer, 'shipping_address_1', $received__billing_address_1 );
						update_user_meta( $new_customer, 'shipping_address_2', $klarna_order['billing_address']['care_of'] );
						update_user_meta( $new_customer, 'shipping_postcode', $klarna_order['billing_address']['postal_code'] );
						update_user_meta( $new_customer, 'shipping_city', $klarna_order['billing_address']['city'] );
						update_user_meta( $new_customer, 'shipping_country', $klarna_order['billing_address']['country'] );
					}

					$this->customer_id = $new_customer;
				}

				update_post_meta( $order->id, '_customer_user', $this->customer_id );
			}
		}
	}

	/**
	 * Confirms Klarna order.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  object $order Local WC order.
	 * @param  object $klarna_order Klarna order.
	 *
	 * @return object $klarna_order Klarna order.
	 */
	public function confirm_klarna_order( $order, $klarna_order ) {
		// Rest API
		if ( isset( $_GET['klarna-api'] ) && 'rest' == sanitize_key( $_GET['klarna-api'] ) ) {
			if ( ! get_post_meta( $order->id, '_kco_payment_created', true ) ) {
				$order->add_order_note( sprintf( __( 'Klarna Checkout payment created. Klarna reference number: %s.', 'woocommerce-gateway-klarna' ), $klarna_order['klarna_reference'] ) );
				$klarna_order->acknowledge();
				$klarna_order->fetch();

				$klarna_order->updateMerchantReferences( array(
					'merchant_reference1' => ltrim( $order->get_order_number(), '#' )
				) );

				$order->calculate_totals( false );
				$order->payment_complete( $klarna_order['klarna_reference'] );

				delete_post_meta( $order->id, '_kco_incomplete_customer_email' );
				add_post_meta( $order->id, '_kco_payment_created', time() );
			}
			// V2 API
		} else {
			$order->add_order_note( sprintf( __( 'Klarna Checkout payment created. Reservation number: %s.  Klarna order number: %s', 'woocommerce-gateway-klarna' ), $klarna_order['reservation'], $klarna_order['id'] ) );

			// Add order expiration date
			$expiration_time = date( get_option( 'date_format' ) . ' - ' . get_option( 'time_format' ), strtotime( $klarna_order['expires_at'] ) );
			$order->add_order_note( sprintf( __( 'Klarna authorization expires at %s.', 'woocommerce-gateway-klarna' ), $expiration_time ) );

			$update['status']             = 'created';
			$update['merchant_reference'] = array(
				'orderid1' => ltrim( $order->get_order_number(), '#' )
			);
			$klarna_order->update( $update );

			// Confirm local order
			$order->calculate_totals( false );
			$order->payment_complete( $klarna_order['reservation'] );

			delete_post_meta( $order->id, '_kco_incomplete_customer_email' );
		}

		return $klarna_order;
	}

}