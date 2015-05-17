<?php
/**
 * Formats Klarna order data for WC order
 *
 * @link  http://www.woothemes.com/products/klarna/
 * @since 2.0.0
 *
 * @package WC_Gateway_Klarna
 */

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
	 * Constructor
	 *
	 * @since 2.0.0
	 */
	public function __construct( $is_rest = false, $eid, $secret, $klarna_order_uri, $klarna_log, $klarna_debug ) {
		$this->is_rest = $is_rest;
		$this->eid = $eid;
		$this->secret = $secret;
		$this->klarna_order_uri = $klarna_order_uri;
		$this->klarna_log = $klarna_log;
		$this->klarna_debug = $klarna_debug;
	}

	/**
	 * KCO listener function.
	 * 
	 * Creates local order on Klarna's push notification.
	 *
	 * @since  2.0.0
	 * @access public
	 * 
	 * @param  integer $eid    Klarna Eid.
	 * @param  integer $secret Klarna secret.
	 * @param  object  $log    WooCommerce log object.
	 * @param  string  $debug  Debug yes/no.
	 */
	public function listener() {
		global $woocommerce;
		
		// Retrieve Klarna order
		$klarna_order = $this->retrieve_klarna_order();

		if ( $klarna_order['status'] == 'checkout_complete' ) { 
			// Create order in WooCommerce
			$order = $this->create_order();

			// Add order items
			$this->add_order_items( $order, $klarna_order );

			// Add order fees
			$this->add_order_fees( $order, $klarna_order );

			// Add order shipping
			$this->add_order_shipping( $order, $klarna_order );				
			
			// Add order addresses
			$this->add_order_addresses( $order, $klarna_order );

			// Add order taxes
			$this->add_order_tax_rows( $order, $klarna_order );

			// Store coupons
			$this->add_order_coupons( $order, $klarna_order );

			// Store payment method
			$this->add_order_payment_method( $order, $klarna_order );

			// Calculate order totals
			$this->set_order_totals( $order, $klarna_order );
					
			// Let plugins add meta
			do_action( 'woocommerce_checkout_update_order_meta', $order->id, array() );

			// Calculate order totals
			$this->add_order_customer_info( $order, $klarna_order );
			
			$order->add_order_note( sprintf( 
				__( 'Klarna Checkout payment completed. Reservation number: %s.  Klarna order number: %s', 'klarna' ),
				$klarna_order['reservation'], 
				$klarna_order['id'] 
			) );

			
			// Update the order in Klarnas system
			$this->klarna_log->add( 'klarna', 'Updating Klarna order status to "created"...' );
			$update['status'] = 'created';
			$update['merchant_reference'] = array(  
				'orderid1' => ltrim( $order->get_order_number(), '#' )
			);
			$klarna_order->update( $update );
			
			// Check if order is not already completed or processing
			// To avoid triggering of multiple payment_complete() callbacks
			if ( $order->status == 'completed' || $order->status == 'processing' ) {
				if ( $this->klarna_debug == 'yes' ) {
					$this->klarna_log->add( 'klarna', 'Aborting, Order #' . $order->id . ' is already complete.' );
				}
		    } else { // Payment complete		    
		    	// Update order meta
		    	update_post_meta( $order_id, 'klarna_order_status', 'created' );
				update_post_meta( $order_id, '_klarna_order_reservation', $klarna_order['reservation'] );
				
				$order->payment_complete();
				// Debug
				if ( $this->klarna_debug == 'yes') {
					$this->klarna_log->add( 'klarna', 'Payment complete action triggered' );
				}
				
				// Empty cart
				$woocommerce->cart->empty_cart();
			}
			
			// Other plugins and themes can hook into here
			do_action( 'klarna_after_kco_push_notification', $order_id );
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
			$this->klarna_log->add( 'klarna', 'IPN callback from Klarna' );
			$this->klarna_log->add( 'klarna', 'Klarna order: ' . $this->klarna_order_uri );
			$this->klarna_log->add( 'klarna', 'GET: ' . json_encode($_GET) );
			$this->klarna_log->add( 'klarna', 'Fetching Klarna order...' );
		}

		if ( $this->is_rest ) {
			require_once( KLARNA_LIB . 'vendor/autoload.php' );
			$connector = \Klarna\Rest\Transport\Connector::create(
				$this->eid,
				$this->secret,
				\Klarna\Rest\Transport\ConnectorInterface::TEST_BASE_URL
			);

			$klarna_order = new \Klarna\Rest\OrderManagement\Order(
				$connector,
				$klarna_order
			);				
		} else {
			require_once( KLARNA_LIB . '/src/Klarna/Checkout.php' );  
			Klarna_Checkout_Order::$contentType = "application/vnd.klarna.checkout.aggregated-order-v2+json";
			$connector    = Klarna_Checkout_Connector::create( $this->secret );  			
			$checkoutId   = $this->klarna_order_uri;  
			$klarna_order = new Klarna_Checkout_Order( $connector, $checkoutId );  
		}
		$klarna_order->fetch();

		if ( $this->klarna_debug == 'yes' ) {
			$this->klarna_log->add( 'klarna', 'ID: ' . $klarna_order['id'] );
			$this->klarna_log->add( 'klarna', 'Billing: ' . $klarna_order['billing_address']['given_name'] );
			$this->klarna_log->add( 'klarna', 'Reference: ' . $klarna_order['reservation'] );
			$this->klarna_log->add( 'klarna', 'Fetched order from Klarna: ' . var_export( $klarna_order, true ) );
		}

		return $klarna_order;
	}

	/**
	 * Fetch KCO order.
	 *
	 * @since  2.0.0
	 * @access public
	 */
	public function create_order() {
		$this->klarna_log->add( 'klarna', 'Creating local order...' );

		// Customer accounts
		$customer_id = apply_filters( 'woocommerce_checkout_customer_id', get_current_user_id() );

		// Order data
		$order_data = array(
			'status'        => apply_filters( 'woocommerce_default_order_status', 'pending' ),
			'customer_id'   => $customer_id
		);

		// Create the order
		$order = wc_create_order( $order_data );
		if ( is_wp_error( $order ) ) {
			throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
		}

		$order_id = $order->id;
		$this->klarna_log->add( 'klarna', 'Local order created, order ID: ' . $order_id );	

		return $order;
	}

	/**
	 * Adds order items to local order.
	 *
	 * @since  2.0.0
	 * @access public
	 * 
	 * @param  object $order        Local WC order.
	 * @param  object $klarna_order Klarna order.
	 */
	public function add_order_items( $order, $klarna_order ) {
		$this->klarna_log->add( 'klarna', 'Adding order items...' );

		$klarna_transient = sanitize_key( $_GET['sid'] );
		$klarna_wc = get_transient( $klarna_transient );
		$order_id = $order->id;

		foreach ( $klarna_wc->cart->get_cart() as $cart_item_key => $values ) {
			$item_id = $order->add_product(
				$values['data'],
				$values['quantity'],
				array(
					'variation' => $values['variation'],
					'totals'    => array(
						'subtotal'     => $values['line_subtotal'],
						'subtotal_tax' => $values['line_subtotal_tax'],
						'total'        => $values['line_total'],
						'tax'          => $values['line_tax'],
						'tax_data'     => $values['line_tax_data'] // Since 2.2
					)
				)
			);

			if ( ! $item_id ) {
				$this->klarna_log->add( 'klarna', 'Unable to add order item.' );
				throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
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
	 * @param  object $order        Local WC order.
	 * @param  object $klarna_order Klarna order.
	 */
	public function add_order_fees( $order, $klarna_order ) {
		$this->klarna_log->add( 'klarna', 'Adding order fees...' );

		$klarna_transient = sanitize_key( $_GET['sid'] );
		$klarna_wc = get_transient( $klarna_transient );
		$order_id = $order->id;
		
		foreach ( $klarna_wc->cart->get_fees() as $fee_key => $fee ) {
			$item_id = $order->add_fee( $fee );

			if ( ! $item_id ) {
				$this->klarna_log->add( 'klarna', 'Unable to add order fee.' );
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
	 * @param  object $order        Local WC order.
	 * @param  object $klarna_order Klarna order.
	 */
	public function add_order_shipping( $order, $klarna_order ) {
		$klarna_transient = sanitize_key( $_GET['sid'] );
		$klarna_wc = get_transient( $klarna_transient );
		$order_id = $order->id;

		// Store shipping for all packages
		foreach ( $klarna_wc->shipping->get_packages() as $package_key => $package ) {
			if ( isset( $package['rates'][ $this->shipping_methods[ $package_key ] ] ) ) {
				$item_id = $order->add_shipping( $package['rates'][ $this->shipping_methods[ $package_key ] ] );

				if ( ! $item_id ) {
					$this->klarna_log->add( 'klarna', 'Unable to add shipping item.' );
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
	 * @param  object $order        Local WC order.
	 * @param  object $klarna_order Klarna order.
	 */
	public function add_order_addresses( $order, $klarna_order ) {
		$this->klarna_log->add( 'klarna', 'Adding order addresses...' );

		$order_id = $order->id;
		
		// Different names on the returned street address if it's a German purchase or not
		$received__billing_address_1  = '';
		$received__shipping_address_1 = '';

		if ( $_GET['scountry'] == 'DE' ) {
			$received__billing_address_1 = $klarna_order['billing_address']['street_name'] . ' ' . $klarna_order['billing_address']['street_number'];
			$received__shipping_address_1 = $klarna_order['shipping_address']['street_name'] . ' ' . $klarna_order['shipping_address']['street_number'];							
		} else {		
			$received__billing_address_1 	= $klarna_order['billing_address']['street_address'];
			$received__shipping_address_1 	= $klarna_order['shipping_address']['street_address'];		
		}
			
		// Add customer billing address - retrieved from callback from Klarna
		update_post_meta( $order_id, '_billing_first_name', $klarna_order['billing_address']['given_name'] );
		update_post_meta( $order_id, '_billing_last_name', $klarna_order['billing_address']['family_name'] );
		update_post_meta( $order_id, '_billing_address_1', $received__billing_address_1 );
		update_post_meta( $order_id, '_billing_address_2', $klarna_order['billing_address']['care_of'] );
		update_post_meta( $order_id, '_billing_postcode', $klarna_order['billing_address']['postal_code'] );
		update_post_meta( $order_id, '_billing_city', $klarna_order['billing_address']['city'] );
		update_post_meta( $order_id, '_billing_country', strtoupper( $klarna_order['billing_address']['country'] ) );
		update_post_meta( $order_id, '_billing_email', $klarna_order['billing_address']['email'] );
		update_post_meta( $order_id, '_billing_phone', $klarna_order['billing_address']['phone'] );
		
		// Add customer shipping address - retrieved from callback from Klarna
		$allow_separate_shipping = ( isset( $klarna_order['options']['allow_separate_shipping_address'] ) ) ? $klarna_order['options']['allow_separate_shipping_address'] : '';
		
		if ( $allow_separate_shipping == 'true' || $_GET['scountry'] == 'DE' ) {		
			update_post_meta( $order_id, '_shipping_first_name', $klarna_order['shipping_address']['given_name'] );
			update_post_meta( $order_id, '_shipping_last_name', $klarna_order['shipping_address']['family_name'] );
			update_post_meta( $order_id, '_shipping_address_1', $received__shipping_address_1 );
			update_post_meta( $order_id, '_shipping_address_2', $klarna_order['shipping_address']['care_of'] );
			update_post_meta( $order_id, '_shipping_postcode', $klarna_order['shipping_address']['postal_code'] );
			update_post_meta( $order_id, '_shipping_city', $klarna_order['shipping_address']['city'] );
			update_post_meta( $order_id, '_shipping_country', strtoupper( $klarna_order['shipping_address']['country'] ) );		
		} else {			
			update_post_meta( $order_id, '_shipping_first_name', $klarna_order['billing_address']['given_name'] );
			update_post_meta( $order_id, '_shipping_last_name', $klarna_order['billing_address']['family_name'] );
			update_post_meta( $order_id, '_shipping_address_1', $received__billing_address_1 );
			update_post_meta( $order_id, '_shipping_address_2', $klarna_order['billing_address']['care_of'] );
			update_post_meta( $order_id, '_shipping_postcode', $klarna_order['billing_address']['postal_code'] );
			update_post_meta( $order_id, '_shipping_city', $klarna_order['billing_address']['city'] );
			update_post_meta( $order_id, '_shipping_country', strtoupper( $klarna_order['billing_address']['country'] ) );
		}
	}

	/**
	 * Adds order tax rows to local order.
	 *
	 * @since  2.0.0
	 * @access public
	 * 
	 * @param  object $order        Local WC order.
	 * @param  object $klarna_order Klarna order.
	 */
	public function add_order_tax_rows( $order, $klarna_order ) {
		$this->klarna_log->add( 'klarna', 'Adding order tax...' );

		$klarna_transient = sanitize_key( $_GET['sid'] );
		$klarna_wc = get_transient( $klarna_transient );

		global $woocommerce;

		foreach ( array_keys( $klarna_wc->cart->taxes + $klarna_wc->cart->shipping_taxes ) as $tax_rate_id ) {
			if ( ! $order->add_tax( $tax_rate_id, $klarna_wc->cart->get_tax_amount( $tax_rate_id ), $woocommerce->cart->get_shipping_tax_amount( $tax_rate_id ) ) ) {
				$this->klarna_log->add( 'klarna', 'Unable to add taxes.' );
				throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
			}
		}
	}

	/**
	 * Adds order coupons to local order.
	 *
	 * @since  2.0.0
	 * @access public
	 * 
	 * @param  object $order        Local WC order.
	 * @param  object $klarna_order Klarna order.
	 */
	public function add_order_coupons( $order, $klarna_order ) {
		$this->klarna_log->add( 'klarna', 'Adding order coupons...' );

		$klarna_transient = sanitize_key( $_GET['sid'] );
		$klarna_wc = get_transient( $klarna_transient );

		foreach ( $klarna_wc->cart->get_coupons() as $code => $coupon ) {
			if ( ! $order->add_coupon( $code, $klarna_wc->cart->get_coupon_discount_amount( $code ) ) ) {
				$this->klarna_log->add( 'klarna', 'Unable to add coupons.' );
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
	 * @param  object $order        Local WC order.
	 * @param  object $klarna_order Klarna order.
	 */
	public function add_order_payment_method( $order, $klarna_order ) {
		$this->klarna_log->add( 'klarna', 'Adding order payment method...' );

		$klarna_transient = sanitize_key( $_GET['sid'] );
		$klarna_wc = get_transient( $klarna_transient );

		$available_gateways = $klarna_wc->payment_gateways->payment_gateways();
		$payment_method = $available_gateways[ 'klarna_checkout' ];
	
		$order->set_payment_method( $payment_method );
	}

	/**
	 * Set local order totals.
	 *
	 * @since  2.0.0
	 * @access public
	 * 
	 * @param  object $order        Local WC order.
	 * @param  object $klarna_order Klarna order.
	 */
	public function set_order_totals( $order, $klarna_order ) {
		$klarna_transient = sanitize_key( $_GET['sid'] );
		$klarna_wc = get_transient( $klarna_transient );

		$order->set_total( $klarna_wc->cart->shipping_total, 'shipping' );
		$order->set_total( $klarna_wc->cart->get_cart_discount_total(), 'order_discount' );
		$order->set_total( $klarna_wc->cart->get_cart_discount_total(), 'cart_discount' );
		$order->set_total( $klarna_wc->cart->tax_total, 'tax' );
		$order->set_total( $klarna_wc->cart->shipping_tax_total, 'shipping_tax' );
		$order->set_total( $klarna_wc->cart->total );
		$order->calculate_shipping();
		$order->calculate_taxes();
		$order->calculate_totals();		
	}

	/**
	 * Adds customer info to local order.
	 *
	 * @since  2.0.0
	 * @access public
	 * 
	 * @param  object $order        Local WC order.
	 * @param  object $klarna_order Klarna order.
	 */
	public function add_order_customer_info( $order, $klarna_order ) {
		$order_id = $order->id;

		// Store user id in order so the user can keep track of track it in My account
		if ( email_exists( $klarna_order['billing_address']['email'] ) ) {		
			if ( $this->klarna_debug == 'yes' ) {
				$this->klarna_log->add( 'klarna', 'Billing email: ' . $klarna_order['billing_address']['email'] );
			}
		
			$user = get_user_by('email', $klarna_order['billing_address']['email']);
			
			if ( $this->klarna_debug == 'yes' ) {
				$this->klarna_log->add( 'klarna', 'Customer User ID: ' . $user->ID );
			}
				
			$this->customer_id = $user->ID;
			
			update_post_meta( $order->id, '_customer_user', $this->customer_id );
		} else {
			// Create new user
			if ( $this->create_customer_account == 'yes' ) {
				$password = '';
				$new_customer = $this->create_new_customer( $klarna_order['billing_address']['email'], $klarna_order['billing_address']['email'], $password );
				$order->add_order_note( sprintf( __( 'New customer created (user ID %s).', 'klarna' ), $new_customer, $klarna_order['id'] ) );
				
				if ( is_wp_error( $new_customer ) ) { // Creation failed
					$order->add_order_note( sprintf( __( 'Customer creation failed. Error: %s.', 'klarna' ), $new_customer->get_error_message(), $klarna_order['id'] ) );
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
					
					if ( $allow_separate_shipping == 'true' || $_GET['scountry'] == 'DE' ) {
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

}