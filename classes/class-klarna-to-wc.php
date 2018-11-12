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
	exit; // Exit if accessed directly.
}

/**
 * This class grabs WC cart contents and formats them so they can
 * be sent to Klarna when a KCO order is being created or updated.
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
	 * Klarna credentials country.
	 *
	 * @since  2.3.3
	 * @access public
	 * @var    string
	 */
	public $klarna_credentials_country;

	/**
	 * Set is_rest value
	 *
	 * @since 2.0.0
	 * @param boolean $is_rest Klarna Rest API or not.
	 */
	public function set_rest( $is_rest ) {
		$this->is_rest = $is_rest;
	}

	/**
	 * Set eid
	 *
	 * @since 2.0.0
	 * @param string $eid Klarna merchant Eid.
	 */
	public function set_eid( $eid ) {
		$this->eid = $eid;
	}

	/**
	 * Set secret
	 *
	 * @since 2.0.0
	 * @param string $secret Klarna merchant secret.
	 */
	public function set_secret( $secret ) {
		$this->secret = $secret;
	}

	/**
	 * Set klarna_order_uri
	 *
	 * @since 2.0.0
	 * @param string $klarna_order_uri Klarna order URI.
	 */
	public function set_klarna_order_uri( $klarna_order_uri ) {
		$this->klarna_order_uri = $klarna_order_uri;
	}

	/**
	 * Set klarna_log
	 *
	 * @since 2.0.0
	 * @param WC_Logger $klarna_log Logger.
	 */
	public function set_klarna_log( $klarna_log ) {
		$this->klarna_log = $klarna_log;
	}

	/**
	 * Set klarna_debug
	 *
	 * @since 2.0.0
	 * @param boolean $klarna_debug Debug enabled.
	 */
	public function set_klarna_debug( $klarna_debug ) {
		$this->klarna_debug = $klarna_debug;
	}

	/**
	 * Set klarna_debug
	 *
	 * @since 2.0.0
	 * @param boolean $klarna_test_mode Test mode enabled.
	 */
	public function set_klarna_test_mode( $klarna_test_mode ) {
		$this->klarna_test_mode = $klarna_test_mode;
	}

	/**
	 * Set klarna_server
	 *
	 * @since 2.0.0
	 * @param string $klarna_server Klarna server URI.
	 */
	public function set_klarna_server( $klarna_server ) {
		$this->klarna_server = $klarna_server;
	}

	/**
	 * Set klarna_credentials_country
	 *
	 * @since 2.3.3
	 * @param string $klarna_credentials_country Used credentials country for purchase.
	 */
	public function set_klarna_credentials_country( $klarna_credentials_country ) {
		$this->klarna_credentials_country = $klarna_credentials_country;
	}

	/**
	 * Prepares local order.
	 *
	 * Creates local order on Klarna's push notification.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  string $customer_email Incomplete order customer email.
	 * @return int    $order->id      WooCommerce order ID.
	 */
	public function prepare_wc_order( $customer_email ) {
		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}

		if ( WC()->session->get( 'ongoing_klarna_order' ) && wc_get_order( WC()->session->get( 'ongoing_klarna_order' ) ) ) {
			$orderid = WC()->session->get( 'ongoing_klarna_order' );
			$order   = wc_get_order( $orderid );
		} elseif ( WC()->session->get( 'order_awaiting_payment' ) && wc_get_order( WC()->session->get( 'order_awaiting_payment' ) ) ) {
			// An order exist, probably started with another payment method.
			$orderid = WC()->session->get( 'order_awaiting_payment' );
			$order   = wc_get_order( $orderid );
			// Set ongoing_klarna_order for this order_id.
			WC()->session->set( 'ongoing_klarna_order', klarna_wc_get_order_id( $order ) );
			$order->add_order_note( __( 'Changed to Klarna Checkout as payment method.', 'woocommerce-gateway-klarna' ) );
		} else {
			// Create order in WooCommerce if we have an email.
			$order = $this->create_order();
			update_post_meta( klarna_wc_get_order_id( $order ), '_kco_incomplete_customer_email', $customer_email, true );
			WC()->session->set( 'ongoing_klarna_order', klarna_wc_get_order_id( $order ) );
		}

		// If there's an order at this point, proceed.
		if ( isset( $order ) ) {
			$order_id = klarna_wc_get_order_id( $order );
			if ( 'yes' === $this->klarna_debug ) {
				// $this->klarna_log->add( 'klarna', microtime() . ": Current action for preparing order $order_id: " . current_action() );
				$e = new Exception();
				// $this->klarna_log->add( 'klarna', microtime() . ": Debug backtrace for preparing order $order_id: " . $e->getTraceAsString() );
			}

			// Need to clean up the order first, to avoid duplicate items.
			$order->remove_order_items();

			if ( 'yes' === $this->klarna_debug ) {
				// $this->klarna_log->add( 'klarna', microtime() . ": Removed order items from order $order_id..." );
				if ( get_post_meta( $order_id, '_customer_user_agent', true ) ) {
					$customer_user_agent = get_post_meta( $order_id, '_customer_user_agent', true );
					// $this->klarna_log->add( 'klarna', microtime() . ": Customer user agent for $order_id: $customer_user_agent" );
				}
			}

			// Add order items.
			$order_items = $order->get_items( array( 'line_item' ) );
			if ( empty( $order_items ) ) {
				$this->add_order_items( $order );
			}

			// Add order fees.
			$order_fees = $order->get_items( array( 'fee' ) );
			if ( empty( $order_fees ) ) {
				$this->add_order_fees( $order );
			}

			// Add order shipping.
			$order_shipping = $order->get_items( array( 'shipping' ) );
			if ( empty( $order_shipping ) ) {
				$this->add_order_shipping( $order );
			}

			// Add order taxes.
			$order_taxes = $order->get_items( array( 'tax' ) );
			if ( empty( $order_taxes ) ) {
				$this->add_order_tax_rows( $order );
			}

			// Store coupons.
			$order_coupons = $order->get_items( array( 'coupon' ) );
			if ( empty( $order_coupons ) ) {
				$this->add_order_coupons( $order );
			}

			// Store payment method.
			$this->add_order_payment_method( $order );

			// Calculate order totals.
			$this->set_order_totals( $order );

			// Tie this order to a user.
			if ( email_exists( $customer_email ) ) {
				$user    = get_user_by( 'email', $customer_email );
				$user_id = $user->ID;
				update_post_meta( $order_id, '_customer_user', $user_id );
			}

			// Let plugins add meta.
			do_action( 'woocommerce_checkout_update_order_meta', $order_id, array() );

			// Store which KCO API was used.
			if ( $this->is_rest ) {
				update_post_meta( $order_id, '_klarna_api', 'rest' );
			} else {
				update_post_meta( $order_id, '_klarna_api', 'v2' );
			}

			// Store which KCO credentials country was used.
			update_post_meta( $order_id, '_klarna_credentials_country', $this->klarna_credentials_country );

			return $order_id;
		} else {
			return false;
		}
	}

	/**
	 * KCO listener function.
	 *
	 * Creates local order on Klarna's push notification.
	 *
	 * @since  2.0.0
	 * @access public
	 * @throws Exception PHP Exception.
	 */
	public function listener() {
		// Retrieve Klarna order.
		$klarna_order = $this->retrieve_klarna_order();

		// Check if order has been completed by Klarna, for V2 and Rest.
		if ( 'checkout_complete' === $klarna_order['status'] || 'AUTHORIZED' === $klarna_order['status'] ) {
			$local_order_id = sanitize_key( $_GET['sid'] ); // Input var okay.
			$order          = wc_get_order( $local_order_id );

			// Log callback for v2
			if ( 'v2' === sanitize_key( $_GET['klarna-api'] ) ) {
				// Remove html_snippet from what we're logging
				$log_order                      = array();
				$log_order['id']                = $klarna_order['id'];
				$log_order['reservation']       = $klarna_order['reservation'];
				$log_order['purchase_country']  = $klarna_order['purchase_country'];
				$log_order['purchase_currency'] = $klarna_order['purchase_currency'];
				$log_order['locale']            = $klarna_order['locale'];
				$log_order['status']            = $klarna_order['status'];
				$log_order['cart']              = $klarna_order['cart'];
				$log_order['customer']          = $klarna_order['customer'];
				$log_order['options']           = $klarna_order['options'];
				$log_order['merchant']          = $klarna_order['merchant'];
				krokedil_log_events( $local_order_id, 'Klarna listener hit.', $log_order );
			}

			// Check if order was recurring.
			if ( isset( $klarna_order['recurring_token'] ) ) {
				update_post_meta( $local_order_id, '_klarna_recurring_token', $klarna_order['recurring_token'] );
			}
			if ( sanitize_key( $_GET['klarna-api'] ) && 'rest' === sanitize_key( $_GET['klarna-api'] ) ) { // Input var okay.
				update_post_meta( $local_order_id, '_klarna_order_id', $klarna_order['order_id'] );
				$order->add_order_note( sprintf( __( 'Klarna order ID: %s.', 'woocommerce-gateway-klarna' ), $klarna_order['order_id'] ) );
			} else {
				update_post_meta( $local_order_id, '_klarna_order_reservation', $klarna_order['reservation'] );
			}

			// Change order currency.
			$this->change_order_currency( $order, $klarna_order );

			// Add order addresses.
			self::add_order_addresses( $order, $klarna_order );

			// Store payment method.
			$this->add_order_payment_method( $order );

			// Add order customer info.
			$this->add_order_customer_info( $order, $klarna_order );

			do_action( 'kco_before_confirm_order', $local_order_id );

			// Confirm the order in Klarna's system.
			$klarna_order = $this->confirm_klarna_order( $order, $klarna_order );
			$order->calculate_totals( false );

			// Other plugins and themes can hook into here.
			do_action( 'klarna_after_kco_push_notification', $local_order_id, $klarna_order );
		}
	}

	/**
	 * Fetch KCO order.
	 *
	 * @since  2.0.0
	 * @access public
	 */
	public function retrieve_klarna_order() {
		if ( 'yes' === $this->klarna_debug ) {
			// $this->klarna_log->add( 'klarna', 'Klarna order - ' . $this->klarna_order_uri );
		}

		if ( sanitize_key( $_GET['klarna-api'] ) && 'rest' === sanitize_key( $_GET['klarna-api'] ) ) { // Input var okay.
			$klarna_country = sanitize_key( $_GET['scountry'] ); // Input var okay.

			if ( 'yes' === $this->klarna_test_mode ) {
				if ( in_array( strtoupper( $klarna_country ), apply_filters( 'klarna_is_rest_countries_eu', array( 'DK', 'GB', 'NL' ), sanitize_key( $_GET['sid'] ) ) ) ) {
					$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_TEST_BASE_URL;
				} elseif ( in_array( strtoupper( $klarna_country ), apply_filters( 'klarna_is_rest_countries_na', array( 'US' ), sanitize_key( $_GET['sid'] ) ) ) ) {
					$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_TEST_BASE_URL;
				}
			} else {
				if ( in_array( strtoupper( $klarna_country ), apply_filters( 'klarna_is_rest_countries_eu', array( 'DK', 'GB', 'NL' ), sanitize_key( $_GET['sid'] ) ) ) ) {
					$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_BASE_URL;
				} elseif ( in_array( strtoupper( $klarna_country ), apply_filters( 'klarna_is_rest_countries_na', array( 'US' ), sanitize_key( $_GET['sid'] ) ) ) ) {
					$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_BASE_URL;
				}
			}
			$connector    = \Klarna\Rest\Transport\Connector::create( $this->eid, $this->secret, $klarna_server_url );
			$klarna_order = new \Klarna\Rest\OrderManagement\Order( $connector, $this->klarna_order_uri );
		} else {
			$connector    = Klarna_Checkout_Connector::create( $this->secret, $this->klarna_server );
			$checkout_id  = $this->klarna_order_uri;
			$klarna_order = new Klarna_Checkout_Order( $connector, $checkout_id );
		}

		$klarna_order->fetch();

		return $klarna_order;
	}

	/**
	 * Create WC order.
	 *
	 * @since  2.0.0
	 * @access public
	 * @throws Exception PHP Exception.
	 */
	public function create_order() {
		if ( 'yes' === $this->klarna_debug ) {
			// $this->klarna_log->add( 'klarna', 'Creating local order...' );
		}

		// Customer accounts.
		$customer_id = apply_filters( 'woocommerce_checkout_customer_id', get_current_user_id() );

		// Order data.
		$order_data = array(
			'status'      => apply_filters( 'klarna_checkout_incomplete_order_status', 'kco-incomplete' ),
			'customer_id' => $customer_id,
			'created_via' => 'klarna_checkout',
		);

		// Create the order.
		$order    = wc_create_order( $order_data );
		$order_id = klarna_wc_get_order_id( $order );

		if ( is_wp_error( $order ) ) {
			throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce-gateway-klarna' ) );
		}

		if ( 'yes' === $this->klarna_debug ) {
			// $this->klarna_log->add( 'klarna', 'Local order created, order ID: ' . $order_id );
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
	 * @param  WC_Order $order WooCommerce order.
	 * @param  Klarna   $klarna_order Klarna order.
	 */
	public function change_order_currency( $order, $klarna_order ) {
		if ( 'yes' === $this->klarna_debug ) {
			// $this->klarna_log->add( 'klarna', 'Maybe fixing order currency...' );
		}

		if ( strtoupper( $klarna_order['purchase_currency'] !== $order->get_currency() ) ) {
			if ( 'yes' === $this->klarna_debug ) {
				// $this->klarna_log->add( 'klarna', 'Updating order currency...' );
			}

			update_post_meta( klarna_wc_get_order_id( $order ), '_order_currency', strtoupper( $klarna_order['purchase_currency'] ) );
		}
	}

	/**
	 * Adds order items to local order.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  object $order Local WC order.
	 * @throws Exception PHP Exception.
	 */
	public function add_order_items( $order ) {
		$order_id = klarna_wc_get_order_id( $order );

		if ( 'yes' === $this->klarna_debug ) {
			// $this->klarna_log->add( 'klarna', microtime() . ": Adding items to order $order_id..." );
		}

		if ( version_compare( WOOCOMMERCE_VERSION, '3.0', '>=' ) ) {
			WC()->checkout->create_order_line_items( $order, WC()->cart );
			$order->save();
		} else {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
				$item_id = $order->add_product(
					$values['data'], $values['quantity'], array(
						'variation' => $values['variation'],
						'totals'    => array(
							'subtotal'     => $values['line_subtotal'],
							'subtotal_tax' => $values['line_subtotal_tax'],
							'total'        => $values['line_total'],
							'tax'          => $values['line_tax'],
							'tax_data'     => $values['line_tax_data'],
						),
					)
				);

				if ( ! $item_id ) {
					if ( 'yes' === $this->klarna_debug ) {
						// $this->klarna_log->add( 'klarna', microtime() . ': Unable to add order item.' );
					}

					throw new Exception( __( 'Error: Unable to add item. Please try again.', 'woocommerce-gateway-klarna' ) );
				} else {
					if ( 'yes' === $this->klarna_debug ) {
						// $this->klarna_log->add( 'klarna', microtime() . ": Added item $item_id (cart item key: $cart_item_key) to order $order_id..." );
					}
				}

				// Allow plugins to add order item meta.
				do_action( 'woocommerce_add_order_item_meta', $item_id, $values, $cart_item_key );
			}
		}

		if ( 'yes' === $this->klarna_debug ) {
			// $this->klarna_log->add( 'klarna', microtime() . ": Finished adding items to order $order_id..." );
		}
	}

	/**
	 * Adds order fees to local order.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  object $order Local WC order.
	 * @throws Exception PHP Exception.
	 */
	public function add_order_fees( $order ) {
		$order_id = klarna_wc_get_order_id( $order );

		if ( 'yes' === $this->klarna_debug ) {
			// $this->klarna_log->add( 'klarna', microtime() . ": Adding fees to order $order_id..." );
		}

		foreach ( WC()->cart->get_fees() as $fee_key => $fee ) {
			$item_id = $order->add_fee( $fee );

			if ( ! $item_id ) {
				if ( 'yes' === $this->klarna_debug ) {
					// $this->klarna_log->add( 'klarna', microtime() . ': Unable to add fee.' );
				}

				throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce-gateway-klarna' ) );
			} else {
				if ( 'yes' === $this->klarna_debug ) {
					// $this->klarna_log->add( 'klarna', microtime() . ": Added fee $item_id (fee key: $fee_key) to order $order_id..." );
				}
			}

			// Allow plugins to add order item meta to fees.
			do_action( 'woocommerce_add_order_fee_meta', $order_id, $item_id, $fee, $fee_key );
		}

		if ( 'yes' === $this->klarna_debug ) {
			// $this->klarna_log->add( 'klarna', microtime() . ": Finished adding fees to order $order_id..." );
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
	 * @throws Exception PHP Exception.
	 */
	public function add_order_shipping( $order ) {
		$order_id = klarna_wc_get_order_id( $order );

		if ( 'yes' === $this->klarna_debug ) {
			// $this->klarna_log->add( 'klarna', microtime() . ": Adding shipping to order $order_id..." );
		}

		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}

		WC()->cart->calculate_shipping();
		WC()->cart->calculate_fees();
		WC()->cart->calculate_totals();

		$this_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

		// Store shipping for all packages.
		foreach ( WC()->shipping->get_packages() as $package_key => $package ) {
			if ( isset( $package['rates'][ $this_shipping_methods[ $package_key ] ] ) ) {
				if ( class_exists( 'WC_Order_Item_Shipping' ) ) {
					$shipping_rate = $package['rates'][ $this_shipping_methods[ $package_key ] ];

					$item = new WC_Order_Item_Shipping();
					$item->set_props(
						array(
							'method_title' => $shipping_rate->label,
							'method_id'    => $shipping_rate->id,
							'total'        => wc_format_decimal( $shipping_rate->cost ),
							'taxes'        => $shipping_rate->taxes,
							'order_id'     => $order->get_id(),
						)
					);
					foreach ( $shipping_rate->get_meta_data() as $key => $value ) {
						$item->add_meta_data( $key, $value, true );
					}
					$item->save();
					$order->add_item( $item );

					$item_id = $item->get_id();
				} else {
					$item_id = $order->add_shipping( $package['rates'][ $this_shipping_methods[ $package_key ] ] );
				}

				if ( ! $item_id ) {
					if ( 'yes' === $this->klarna_debug ) {
						// $this->klarna_log->add( 'klarna', microtime() . ': Unable to add shipping item.' );
					}

					throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce-gateway-klarna' ) );
				} else {
					if ( 'yes' === $this->klarna_debug ) {
						// $this->klarna_log->add( 'klarna', microtime() . ": Added shipping $item_id (package key: $package_key) to order $order_id..." );
					}
				}

				// Allows plugins to add order item meta to shipping.
				do_action( 'woocommerce_add_shipping_order_item', $order_id, $item_id, $package_key );
			}
		}

		if ( 'yes' === $this->klarna_debug ) {
			// $this->klarna_log->add( 'klarna', microtime() . ": Finished adding shipping to order $order_id..." );
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
	public static function add_order_addresses( $order, $klarna_order ) {
		$order_id = klarna_wc_get_order_id( $order );

		// Different names on the returned street address if it's a German purchase or not.
		if ( 'DE' === $_GET['scountry'] || 'AT' === $_GET['scountry'] ) { // Input var okay.
			$received__billing_address_1  = $klarna_order['billing_address']['street_name'] . ' ' . $klarna_order['billing_address']['street_number'];
			$received__shipping_address_1 = $klarna_order['shipping_address']['street_name'] . ' ' . $klarna_order['shipping_address']['street_number'];
		} else {
			$received__billing_address_1  = $klarna_order['billing_address']['street_address'];
			$received__shipping_address_1 = $klarna_order['shipping_address']['street_address'];
		}

		// Add customer billing address - retrieved from callback from Klarna.
		if ( isset( $klarna_order['billing_address']['care_of'] ) ) {
			$billing_address_2 = $klarna_order['billing_address']['care_of'];
		} elseif ( isset( $klarna_order['billing_address']['street_address2'] ) ) {
			$billing_address_2 = $klarna_order['billing_address']['street_address2'];
		} else {
			$billing_address_2 = '';
		}
		$billing_address = array(
			'first_name' => $klarna_order['billing_address']['given_name'],
			'last_name'  => $klarna_order['billing_address']['family_name'],
			'address_1'  => $received__billing_address_1,
			'address_2'  => $billing_address_2,
			'postcode'   => $klarna_order['billing_address']['postal_code'],
			'city'       => $klarna_order['billing_address']['city'],
			'country'    => strtoupper( $klarna_order['billing_address']['country'] ),
			'email'      => $klarna_order['billing_address']['email'],
			'phone'      => $klarna_order['billing_address']['phone'],
		);

		// Company.
		if ( isset( $klarna_order['customer']['type'] ) ) {
			if ( 'organization' === $klarna_order['customer']['type'] ) {
				$billing_address['company'] = $klarna_order['billing_address']['organization_name'];
				$reference                  = isset( $klarna_order['billing_address']['reference'] ) ? $klarna_order['billing_address']['reference'] : '';
				update_post_meta( $order_id, 'klarna_organization_reference', $reference );
				update_post_meta( $order_id, 'klarna_organization_registration_id', $klarna_order['customer']['organization_registration_id'] );
			}
		}

		$order->set_address( apply_filters( 'wc_klarna_returned_billing_address', $billing_address ), 'billing' );

		if ( isset( $klarna_order['shipping_address']['care_of'] ) ) {
			$shipping_address_2 = $klarna_order['shipping_address']['care_of'];
		} elseif ( isset( $klarna_order['shipping_address']['street_address2'] ) ) {
			$shipping_address_2 = $klarna_order['shipping_address']['street_address2'];
		} else {
			$shipping_address_2 = '';
		}
		$shipping_address = array(
			'first_name' => $klarna_order['shipping_address']['given_name'],
			'last_name'  => $klarna_order['shipping_address']['family_name'],
			'address_1'  => $received__shipping_address_1,
			'address_2'  => $shipping_address_2,
			'postcode'   => $klarna_order['shipping_address']['postal_code'],
			'city'       => $klarna_order['shipping_address']['city'],
			'country'    => strtoupper( $klarna_order['shipping_address']['country'] ),
			'email'      => $klarna_order['shipping_address']['email'],
			'phone'      => $klarna_order['shipping_address']['phone'],
		);

		// Company.
		if ( isset( $klarna_order['customer']['type'] ) ) {
			if ( 'organization' === $klarna_order['customer']['type'] ) {
				$shipping_address['company'] = $klarna_order['shipping_address']['organization_name'];
			}
		}

		$order->set_address( apply_filters( 'wc_klarna_returned_shipping_address', $shipping_address ), 'shipping' );

		// Store Klarna locale.
		update_post_meta( $order_id, '_klarna_locale', $klarna_order['locale'] );
	}

	/**
	 * Adds order tax rows to local order.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  object $order Local WC order.
	 * @throws Exception PHP Exception.
	 */
	public function add_order_tax_rows( $order ) {
		$order_id = klarna_wc_get_order_id( $order );

		if ( 'yes' === $this->klarna_debug ) {
			// $this->klarna_log->add( 'klarna', microtime() . ": Adding tax rows to order $order_id..." );
		}

		// Store tax rows.
		if ( is_callable( array( WC()->cart, 'get_cart_contents_taxes' ) ) ) {
			$cart_contents_taxes = WC()->cart->get_cart_contents_taxes();
		} else {
			$cart_contents_taxes = WC()->cart->taxes;
		}

		if ( is_callable( array( WC()->cart, 'get_shipping_taxes' ) ) ) {
			$cart_shipping_taxes = WC()->cart->get_shipping_taxes();
		} else {
			$cart_shipping_taxes = WC()->cart->shipping_taxes;
		}

		foreach ( array_keys( $cart_contents_taxes + $cart_shipping_taxes ) as $tax_rate_id ) {
			if ( class_exists( 'WC_Order_Item_Tax' ) ) {
				$item = new WC_Order_Item_Tax();
				$item->set_props(
					array(
						'rate_id'            => $tax_rate_id,
						'tax_total'          => WC()->cart->get_tax_amount( $tax_rate_id ),
						'shipping_tax_total' => WC()->cart->get_shipping_tax_amount( $tax_rate_id ),
					)
				);
				$item->set_rate( $tax_rate_id );
				$item->set_order_id( $order->get_id() );
				$item->save();
				$order->add_item( $item );
				$tax_item_id = $item->get_id();
			} else {
				$tax_item_id = $order->add_tax( $tax_rate_id, WC()->cart->get_tax_amount( $tax_rate_id ), WC()->cart->get_shipping_tax_amount( $tax_rate_id ) );
			}

			if ( $tax_rate_id && ! $tax_item_id && apply_filters( 'woocommerce_cart_remove_taxes_zero_rate_id', 'zero-rated' ) !== $tax_rate_id ) {
				if ( 'yes' === $this->klarna_debug ) {
					// $this->klarna_log->add( 'klarna', microtime() . ': Unable to add taxes.' );
				}

				throw new Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-gateway-klarna' ), 405 ) );
			} else {
				if ( 'yes' === $this->klarna_debug ) {
					// $this->klarna_log->add( 'klarna', microtime() . ": Added tax rate $tax_rate_id to order $order_id..." );
				}
			}
		}

		if ( 'yes' === $this->klarna_debug ) {
			// $this->klarna_log->add( 'klarna', microtime() . ": Finished adding tax rows to order $order_id..." );
		}
	}

	/**
	 * Adds order coupons to local order.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param  object $order Local WC order.
	 * @throws Exception PHP Exception.
	 */
	public function add_order_coupons( $order ) {
		$order_id = klarna_wc_get_order_id( $order );

		if ( 'yes' === $this->klarna_debug ) {
			// $this->klarna_log->add( 'klarna', microtime() . ": Adding coupons to order $order_id..." );
		}

		foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
			if ( class_exists( 'WC_Order_Item_Coupon' ) ) {
				$item = new WC_Order_Item_Coupon();
				$item->set_props(
					array(
						'code'         => $code,
						'discount'     => WC()->cart->get_coupon_discount_amount( $code ),
						'discount_tax' => 0,
						'order_id'     => $order->get_id(),
					)
				);
				$item->save();
				$order->add_item( $item );
				$item_id = $item->get_id();
			} else {
				$item_id = $order->add_coupon( $code, WC()->cart->get_coupon_discount_amount( $code ) );
			}

			if ( ! $item_id ) {
				if ( 'yes' === $this->klarna_debug ) {
					// $this->klarna_log->add( 'klarna', microtime() . ': Unable to add coupons.' );
				}

				throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce-gateway-klarna' ) );
			} else {
				if ( 'yes' === $this->klarna_debug ) {
					// $this->klarna_log->add( 'klarna', microtime() . ": Added coupon $code to order $order_id..." );
				}
			}
		}

		// Store smart coupons plugin meta field.
		if ( WC()->cart->applied_coupons ) {
			$smart_coupons_contribution = array();
			foreach ( WC()->cart->applied_coupons as $code ) {
				$smart_coupon = new WC_Coupon( $code );
				if ( $smart_coupon->is_type( 'smart_coupon' ) ) {
					$smart_coupons_contribution[ $code ] = WC()->cart->coupon_discount_amounts[ $code ];
					update_post_meta( $order_id, 'smart_coupons_contribution', $smart_coupons_contribution );
				}
			}
		}

		if ( 'yes' === $this->klarna_debug ) {
			// $this->klarna_log->add( 'klarna', microtime() . ": Finished adding coupons to order $order_id..." );
		}
	}

	/**
	 * Adds payment method to local order.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param    object $order Local WC order.
	 * @internal param object $klarna_order Klarna order.
	 */
	public function add_order_payment_method( $order ) {
		if ( 'yes' === $this->klarna_debug ) {
			// $this->klarna_log->add( 'klarna', microtime() . ': Adding order payment method...' );
		}

		$available_gateways = WC()->payment_gateways->payment_gateways();
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
		$order_id = klarna_wc_get_order_id( $order );

		if ( 'yes' === $this->klarna_debug ) {
			// $this->klarna_log->add( 'klarna', microtime() . ": Setting order totals for order $order_id..." );
		}

		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}

		WC()->cart->calculate_shipping();
		WC()->cart->calculate_fees();
		WC()->cart->calculate_totals();

		// WC 3.0, we love you.
		if ( method_exists( $order, 'set_shipping_total' ) ) {
			$order->set_shipping_total( WC()->cart->shipping_total );
		} else {
			$order->set_total( WC()->cart->shipping_total, 'shipping' );
		}

		if ( method_exists( $order, 'set_discount_total' ) ) {
			$order->set_discount_total( WC()->cart->get_cart_discount_total() );
		} else {
			$order->set_total( WC()->cart->get_cart_discount_total(), 'cart_discount' );
		}

		if ( method_exists( $order, 'set_discount_tax' ) ) {
			$order->set_discount_tax( WC()->cart->get_cart_discount_tax_total() );
		} else {
			$order->set_total( WC()->cart->get_cart_discount_tax_total(), 'cart_discount_tax' );
		}

		if ( method_exists( $order, 'set_cart_tax' ) ) {
			$order->set_cart_tax( WC()->cart->tax_total );
		} else {
			$order->set_total( WC()->cart->tax_total, 'tax' );
		}

		if ( method_exists( $order, 'set_shipping_tax' ) ) {
			$order->set_shipping_tax( WC()->cart->shipping_tax_total );
		} else {
			$order->set_total( WC()->cart->shipping_tax_total, 'shipping_tax' );
		}

		$order->set_total( WC()->cart->total );

		if ( method_exists( $order, 'save' ) ) {
			$order->save();
		}

		// $order->calculate_totals();
	}

	/**
	 * Create a new customer
	 *
	 * @param string $email    Customer email.
	 * @param string $username Customer username.
	 * @param string $password Customer password.
	 *
	 * @return mixed WP_error on failure, Int (user ID) on success
	 */
	function create_new_customer( $email, $username = '', $password = '' ) {
		// Check the e-mail address.
		if ( empty( $email ) || ! is_email( $email ) ) {
			return new WP_Error( 'registration-error', __( 'Please provide a valid email address.', 'woocommerce-gateway-klarna' ) );
		}

		if ( email_exists( $email ) ) {
			return new WP_Error( 'registration-error', __( 'An account is already registered with your email address. Please login.', 'woocommerce-gateway-klarna' ) );
		}

		// Handle username creation.
		$username = sanitize_user( current( explode( '@', $email ) ) );

		// Ensure username is unique.
		$append     = 1;
		$o_username = $username;
		while ( username_exists( $username ) ) {
			$username = $o_username . $append;
			$append ++;
		}

		// Handle password creation.
		$password           = wp_generate_password();
		$password_generated = true;

		// WP Validation.
		$validation_errors = new WP_Error();
		do_action( 'woocommerce_register_post', $username, $email, $validation_errors );
		$validation_errors = apply_filters( 'woocommerce_registration_errors', $validation_errors, $username, $email );

		if ( $validation_errors->get_error_code() ) {
			// $this->klarna_log->add( 'klarna', __( 'Customer creation error', 'woocommerce-gateway-klarna' ) . ' - ' . $validation_errors->get_error_code() );
			return 0;
		}

		$new_customer_data = apply_filters(
			'woocommerce_new_customer_data', array(
				'user_login' => $username,
				'user_pass'  => $password,
				'user_email' => $email,
				'role'       => 'customer',
			)
		);

		$customer_id = wp_insert_user( $new_customer_data );

		if ( is_wp_error( $customer_id ) ) {
			$validation_errors->add( 'registration-error', '<strong>' . __( 'ERROR', 'woocommerce-gateway-klarna' ) . '</strong>: ' . __( 'Couldn&#8217;t register you&hellip; please contact us if you continue to have problems.', 'woocommerce-gateway-klarna' ) );
			// $this->klarna_log->add( 'klarna', __( 'Customer creation error', 'woocommerce-gateway-klarna' ) . ' - ' . $validation_errors->get_error_code() );
			return 0;
		}

		// Send New account creation email to customer?
		$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );

		if ( 'yes' === $checkout_settings['send_new_account_email'] ) {
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
		$customer_id = 0;

		// Store user id in order so the user can keep track of track it in My account.
		if ( email_exists( $klarna_order['billing_address']['email'] ) ) {
			if ( 'yes' === $this->klarna_debug ) {
				// $this->klarna_log->add( 'klarna', 'Billing email: ' . $klarna_order['billing_address']['email'] );
			}

			$user = get_user_by( 'email', $klarna_order['billing_address']['email'] );

			if ( 'yes' === $this->klarna_debug ) {
				// $this->klarna_log->add( 'klarna', 'Customer User ID: ' . $user->ID );
			}

			$customer_id = $user->ID;
			if ( ! $order->get_customer_id() ) {
				$order->set_customer_id( apply_filters( 'woocommerce_checkout_customer_id', $customer_id ) );
			}
		} else {
			// Create new user.
			$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );

			if ( 'yes' === $checkout_settings['create_customer_account'] ) {
				$customer_id = wc_create_new_customer( $klarna_order['billing_address']['email'] );
				if ( is_int( $customer_id ) ) {
					if ( ! $order->get_customer_id() ) {
						$order->set_customer_id( apply_filters( 'woocommerce_checkout_customer_id', $customer_id ) );
					}
					$order->add_order_note( sprintf( __( 'New customer created (user ID %s).', 'klarna' ), $customer_id, $klarna_order['id'] ) );
				} elseif ( is_wp_error( $customer_id ) ) {
					$order->add_order_note( sprintf( __( 'Error creating new customer account: %s', 'klarna' ), var_export( $customer_id, true ) ) );
				}
			}
		}

		if ( $customer_id > 0 ) {
			if ( 'DE' === $_GET['scountry'] || 'AT' === $_GET['scountry'] ) { // Input var okay.
				$received_billing_address_1  = $klarna_order['billing_address']['street_name'] . ' ' . $klarna_order['billing_address']['street_number'];
				$received_shipping_address_1 = $klarna_order['shipping_address']['street_name'] . ' ' . $klarna_order['shipping_address']['street_number'];
			} else {
				$received_billing_address_1  = $klarna_order['billing_address']['street_address'];
				$received_shipping_address_1 = $klarna_order['shipping_address']['street_address'];
			}

			// Add customer name.
			update_user_meta( $customer_id, 'first_name', $klarna_order['billing_address']['given_name'] );
			update_user_meta( $customer_id, 'last_name', $klarna_order['billing_address']['family_name'] );

			// Add customer billing address - retrieved from callback from Klarna.
			update_user_meta( $customer_id, 'billing_first_name', $klarna_order['billing_address']['given_name'] );
			update_user_meta( $customer_id, 'billing_last_name', $klarna_order['billing_address']['family_name'] );
			update_user_meta( $customer_id, 'billing_address_1', $received_billing_address_1 );
			if ( isset( $klarna_order['billing_address']['care_of'] ) ) {
				update_user_meta( $customer_id, 'billing_address_2', $klarna_order['billing_address']['care_of'] );
			}
			update_user_meta( $customer_id, 'billing_postcode', $klarna_order['billing_address']['postal_code'] );
			update_user_meta( $customer_id, 'billing_city', $klarna_order['billing_address']['city'] );
			update_user_meta( $customer_id, 'billing_country', strtoupper( $klarna_order['billing_address']['country'] ) );
			update_user_meta( $customer_id, 'billing_email', $klarna_order['billing_address']['email'] );
			update_user_meta( $customer_id, 'billing_phone', $klarna_order['billing_address']['phone'] );

			// Add customer shipping address - retrieved from callback from Klarna.
			$allow_separate_shipping = ( isset( $klarna_order['options']['allow_separate_shipping_address'] ) ) ? $klarna_order['options']['allow_separate_shipping_address'] : '';
			if ( 'true' == $allow_separate_shipping && ( 'DE' === $_GET['scountry'] || 'AT' === $_GET['scountry'] ) ) { // Input var okay.
				update_user_meta( $customer_id, 'shipping_first_name', $klarna_order['shipping_address']['given_name'] );
				update_user_meta( $customer_id, 'shipping_last_name', $klarna_order['shipping_address']['family_name'] );
				update_user_meta( $customer_id, 'shipping_address_1', $received_shipping_address_1 );
				if ( isset( $klarna_order['shipping_address']['care_of'] ) ) {
					update_user_meta( $customer_id, 'shipping_address_2', $klarna_order['shipping_address']['care_of'] );
				}
				update_user_meta( $customer_id, 'shipping_postcode', $klarna_order['shipping_address']['postal_code'] );
				update_user_meta( $customer_id, 'shipping_city', $klarna_order['shipping_address']['city'] );
				update_user_meta( $customer_id, 'shipping_country', $klarna_order['shipping_address']['country'] );
			} else {
				update_user_meta( $customer_id, 'shipping_first_name', $klarna_order['billing_address']['given_name'] );
				update_user_meta( $customer_id, 'shipping_last_name', $klarna_order['billing_address']['family_name'] );
				update_user_meta( $customer_id, 'shipping_address_1', $received_billing_address_1 );
				if ( isset( $klarna_order['billing_address']['care_of'] ) ) {
					update_user_meta( $customer_id, 'shipping_address_2', $klarna_order['billing_address']['care_of'] );
				}
				update_user_meta( $customer_id, 'shipping_postcode', $klarna_order['billing_address']['postal_code'] );
				update_user_meta( $customer_id, 'shipping_city', $klarna_order['billing_address']['city'] );
				update_user_meta( $customer_id, 'shipping_country', strtoupper( $klarna_order['billing_address']['country'] ) );
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
		// Rest API.
		if ( isset( $_GET['klarna-api'] ) && 'rest' === sanitize_key( $_GET['klarna-api'] ) ) { // Input var okay.
			if ( ! get_post_meta( klarna_wc_get_order_id( $order ), '_kco_payment_created', true ) ) {
				$order->add_order_note( sprintf( __( 'Klarna Checkout payment created. Klarna reference number: %s.', 'woocommerce-gateway-klarna' ), $klarna_order['klarna_reference'] ) );
				$klarna_order->acknowledge();
				$klarna_order->fetch();
				$klarna_order->updateMerchantReferences( array( 'merchant_reference1' => ltrim( $order->get_order_number(), '#' ) ) );

				$transaction_id = $klarna_order['klarna_reference'];
			}
		} else { // V2 API.
			$order->add_order_note( sprintf( __( 'Klarna Checkout payment created. Reservation number: %1$s.  Klarna order number: %2$s', 'woocommerce-gateway-klarna' ), $klarna_order['reservation'], $klarna_order['id'] ) );

			// Add order expiration date.
			$expiration_time = date( get_option( 'date_format' ) . ' - ' . get_option( 'time_format' ), strtotime( $klarna_order['expires_at'] ) );
			$order->add_order_note( sprintf( __( 'Klarna authorization expires at %s.', 'woocommerce-gateway-klarna' ), $expiration_time ) );
			$update['status']             = 'created';
			$update['merchant_reference'] = array( 'orderid1' => ltrim( $order->get_order_number(), '#' ) );
			$klarna_order->update( $update );

			$transaction_id = $klarna_order['reservation'];
		}

		if ( false === apply_filters( 'klarna_finalize_order_in_thank_you_page', false ) ) {
			$order->calculate_totals( false );
			$order->update_status( 'pending' ); // Set status to Pending Payment before completing the order.
			$order->payment_complete( $transaction_id );
			$order->set_date_created( current_time( 'timestamp', true ) );
			delete_post_meta( klarna_wc_get_order_id( $order ), '_kco_incomplete_customer_email' );
			add_post_meta( klarna_wc_get_order_id( $order ), '_kco_payment_created', time() );
		}

		return $klarna_order;
	}

}
