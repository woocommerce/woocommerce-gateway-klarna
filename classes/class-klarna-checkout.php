<?php
/**
 * Klarna checkout class
 *
 * @link http://www.woothemes.com/products/klarna/
 * @since 1.0.0
 *
 * @package WC_Gateway_Klarna
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Gateway_Klarna_Checkout extends WC_Gateway_Klarna {

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();
		$this->id           = 'klarna_checkout';
		$this->method_title = __( 'Klarna Checkout', 'woocommerce-gateway-klarna' );
		$this->has_fields   = false;
		// $this->logger       = new WC_Logger();
		// Load the form fields.
		$this->init_form_fields();
		// Load the settings.
		$this->init_settings();
		// Define user set variables
		include KLARNA_DIR . 'includes/variables-checkout.php';
		// Helper class
		include_once KLARNA_DIR . 'classes/class-klarna-helper.php';
		$this->klarna_helper = new WC_Gateway_Klarna_Helper( $this );
		// Test mode or Live mode
		if ( $this->testmode == 'yes' ) {
			// Disable SSL if in testmode
			$this->klarna_ssl  = 'false';
			$this->klarna_mode = Klarna\XMLRPC\Klarna::BETA;
		} else {
			// Set SSL if used in webshop
			if ( is_ssl() ) {
				$this->klarna_ssl = 'true';
			} else {
				$this->klarna_ssl = 'false';
			}
			$this->klarna_mode = Klarna\XMLRPC\Klarna::LIVE;
		}
		// Actions
		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id, array(
				$this,
				'process_admin_options',
			)
		);
		// Push listener
		add_action( 'woocommerce_api_wc_gateway_klarna_checkout', array( $this, 'check_checkout_listener' ) );
		// Validate listener
		add_action(
			'woocommerce_api_wc_gateway_klarna_order_validate', array(
				'WC_Gateway_Klarna_Order_Validate',
				'validate_checkout_listener',
			)
		);
		// We execute the woocommerce_thankyou hook when the KCO Thank You page is rendered,
		// because other plugins use this, but we don't want to display the actual WC Order
		// details table in KCO Thank You page. This action is removed here, but only when
		// in Klarna Thank You page.
		if ( is_page() ) {
			global $post;
			$klarna_checkout_page_id = url_to_postid( $this->klarna_checkout_thanks_url );
			if ( $post->ID == $klarna_checkout_page_id ) {
				remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );
			}
		}
		// Subscription support
		$this->supports = array(
			'products',
			'refunds',
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'subscription_payment_method_change',
			'subscription_payment_method_change_admin',
			'multiple_subscriptions',
		);
		// Add link to KCO page in standard checkout
		if ( class_exists( 'WC_Subscriptions_Order' ) ) {
			// Process subscription payment
			// add_action( 'woocommerce_scheduled_subscription_renewal_klarna_checkout', array( $this, 'scheduled_subscription_payment' ), 10, 2 );
			add_action(
				'woocommerce_scheduled_subscription_payment_klarna_checkout', array(
					$this,
					'scheduled_subscription_payment',
				), 10, 2
			);
			// Do not copy invoice number to recurring orders
			// add_filter( 'woocommerce_subscriptions_renewal_order_meta_query', array( $this, 'kco_recurring_do_not_copy_meta_data' ), 10, 4 );
		}
		// Purge kco_incomplete orders hourly
		add_action( 'wp', array( $this, 'register_purge_cron_job' ) );
		add_action( 'klarna_purge_cron_job_hook', array( $this, 'purge_kco_incomplete' ) );
		// Add activate settings field for recurring orders
		add_filter( 'klarna_checkout_form_fields', array( $this, 'add_activate_recurring_option' ) );

		// Register new order status
		add_filter(
			'woocommerce_valid_order_statuses_for_payment_complete', array(
				$this,
				'kco_incomplete_payment_complete',
			)
		);
		add_filter( 'woocommerce_valid_order_statuses_for_payment', array( $this, 'kco_incomplete_payment_complete' ) );

		// Hide "Refunded" and "KCO Incomplete" statuses for KCO orders
		// add_filter( 'wc_order_statuses', array( $this, 'remove_refunded_and_kco_incomplete' ), 1000 );
		// Hide "Manual Refund" button for KCO orders
		add_action( 'admin_head', array( $this, 'remove_refund_manually' ) );
		// Cancel unpaid orders for KCO orders too
		add_filter( 'woocommerce_cancel_unpaid_order', array( $this, 'cancel_unpaid_kco' ), 10, 2 );
		// Validate callback notice
		add_action( 'wp', array( $this, 'add_validate_notice' ) );
		// Validate Klarna account on settings save
		/*
		add_action( 'update_option_woocommerce_klarna_checkout_settings', array(
			$this,
			'check_klarna_account'
		), 10, 2 );
		*/
		// Passes AJAX actions to WCML
		add_filter( 'wcml_multi_currency_is_ajax', array( $this, 'pass_ajax_actions_to_wcml' ) );

		if ( ! empty( $_GET['order-received'] ) || ! empty( $_GET['klarna_order'] ) ) {
			remove_action( 'get_header', 'wc_clear_cart_after_payment' );
		}
		add_action( 'woocommerce_checkout_subscription_created', array( $this, 'finalize_subscription' ), 10, 3 );

		// Use an existing order when paying for a manual subscription renewal via "Pay Order" page
		add_action( 'template_redirect', array( $this, 'use_ongoing_order_for_kco' ) );

		// Maybe remove KCO sessions on thank you page. Check is performed even i KCO is the selected payment method or not.
		add_action( 'woocommerce_thankyou', array( $this, 'maybe_clear_kco_sessions' ), 20 );
	}

	/**
	 * Function maybe_clear_kco_sessions()
	 * Check if Klarna sessions needs to be cleared when purchase is done.
	 */
	public function maybe_clear_kco_sessions( $order_id ) {
		// Clear session and empty cart.
		if ( method_exists( WC()->session, '__unset' ) ) {
			if ( WC()->session->get( 'klarna_checkout' ) ) {
				WC()->session->__unset( 'klarna_checkout' );
			}
			if ( WC()->session->get( 'klarna_checkout_country' ) ) {
				WC()->session->__unset( 'klarna_checkout_country' );
			}
			if ( WC()->session->get( 'ongoing_klarna_order' ) ) {
				WC()->session->__unset( 'ongoing_klarna_order' );
			}
			if ( WC()->session->get( 'klarna_order_note' ) ) {
				WC()->session->__unset( 'klarna_order_note' );
			}
			if ( WC()->session->get( 'klarna_separate_shipping' ) ) {
				WC()->session->__unset( 'klarna_separate_shipping' );
			}
		}

	}

	function use_ongoing_order_for_kco() {
		global $wp;

		if ( isset( $_GET['pay_for_order'] ) && isset( $_GET['key'] ) && isset( $wp->query_vars['order-pay'] ) ) {
			$order_id = ( isset( $wp->query_vars['order-pay'] ) ) ? $wp->query_vars['order-pay'] : absint( $_GET['order_id'] );
			$order    = wc_get_order( $wp->query_vars['order-pay'] );

			if ( $order ) {
				WC()->session->set( 'ongoing_klarna_order', $order_id );
			}
		}
	}

	/**
	 * Since KCO processes checkout differently, we need to add shipping to it, if WCS wasn't able to do it when
	 * subscription was created.
	 *
	 * @param $subscription
	 * @param $order
	 * @param $cart
	 *
	 * @hook woocommerce_checkout_subscription_created
	 *
	 * @throws Exception
	 */
	function finalize_subscription( $subscription, $order, $cart ) {
		$subscription_shipping_methods = $subscription->get_shipping_methods();
		if ( klarna_wc_get_order_payment_method( $subscription ) === $this->id ) {
			if ( empty( $subscription_shipping_methods ) ) {
				WC_Subscriptions_Cart::set_calculation_type( 'recurring_total' );
				foreach ( $cart->get_shipping_packages() as $base_package ) {
					$package = WC()->shipping->calculate_shipping_for_package( $base_package );
					foreach ( WC()->shipping->get_packages() as $package_key => $package_to_ignore ) {
						$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
						if ( isset( $package['rates'][ $chosen_shipping_methods[ $package_key ] ] ) ) {
							$item_id = $subscription->add_shipping( $package['rates'][ $chosen_shipping_methods[ $package_key ] ] );
							if ( ! $item_id ) {
								throw new Exception( __( 'Error: Unable to create subscription. Please try again.', 'woocommerce-subscriptions' ) );
							}
							// Allows plugins to add order item meta to shipping.
							do_action( 'woocommerce_add_shipping_order_item', $subscription->id, $item_id, $package_key );
							do_action( 'woocommerce_subscriptions_add_recurring_shipping_order_item', $subscription->id, $item_id, $package_key );
						}
					}
				}

				WC_Subscriptions_Cart::set_calculation_type( 'none' );

				$subscription->calculate_shipping();
				$subscription->calculate_totals( true );
				$subscription->payment_complete();
			}

			// In some cases the parent order is set to Processing/Completed (on a callback from Klarna) before the subscription is created.
			// In these cases we ned to activate the subscription ourselves.
			if ( $order->has_status( array( 'processing', 'completed' ) ) ) {
				$subscription->update_status( 'active' );
			}
		}
	}


	/**
	 * Checks if Klarna accounts are valid.
	 */
	public function check_klarna_account( $new_value, $old_value ) {
		if ( 2 > did_action( 'update_option_woocommerce_klarna_checkout_settings' ) ) {
			// Check KCO account for all countries (SE, NO, FI, DE, AT, UK, US)
			// Check if there's any difference between old and new
			$updated_settings = array_diff( $old_value, $new_value );
			if ( ! empty( $updated_settings ) ) {
				if ( isset( $updated_settings['testmode'] ) ) { // If testmode setting has changed, check all countries with Eid and secret
				} else { // Otherwise check only countries where Eid or secret has changed
				}
			}
		}

		return $new_value;
	}

	/**
	 * Cancel unpaid KCO orders if the option is enabled
	 *
	 * @param  $cancel    boolean    Cancel or not
	 * @param  $order    Object    WooCommerce order object
	 *
	 * @return boolean
	 * @since  2.0
	 **/
	function cancel_unpaid_kco( $cancel, $order ) {
		if ( 'klarna_checkout' === get_post_meta( klarna_wc_get_order_id( $order ), '_created_via', true ) ) {
			$cancel = true;
		}

		return $cancel;
	}

	/**
	 * Remove "Refunded" and "KCO Incomplete" statuses from the dropdown for KCO orders
	 *
	 * @since  2.0
	 **/
	function remove_refunded_and_kco_incomplete( $order_statuses ) {
		if ( is_admin() && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( is_object( $screen ) && 'shop_order' == $screen->id ) {
				if ( isset( $_GET['post'] ) && absint( $_GET['post'] ) == $_GET['post'] ) {
					$order_id = $_GET['post'];
					$order    = wc_get_order( $order_id );
					if ( false != $order && 'refunded' != $order->get_status() && 'klarna_checkout' == get_post_meta( $order_id, '_created_via', true ) ) {
						/**
						 * Filter that allows merchants to show Refunded status in order status dropdown.
						 *
						 * @param boolean $hide Default value true.
						 */
						if ( apply_filters( 'klarna_checkout_hide_refunded_status', true ) ) {
							unset( $order_statuses['wc-refunded'] );
						}
					}
					// NEVER make it possible to change status to KCO Incomplete
					if ( false != $order ) {
						unset( $order_statuses['wc-kco-incomplete'] );
					}
				}
			}
		}

		return $order_statuses;
	}

	/**
	 * Hide "Refund x Manually" for KCO orders
	 *
	 * @since  2.0
	 **/
	function remove_refund_manually() {
		$screen = get_current_screen();
		if ( 'shop_order' == $screen->id ) {
			if ( absint( $_GET['post'] ) == $_GET['post'] ) {
				$order_id = $_GET['post'];
				$order    = wc_get_order( $order_id );
				if ( false != $order && 'klarna_checkout' == get_post_meta( $order_id, '_created_via', true ) ) {
					echo '<style>.do-manual-refund{display:none !important;}</style>';
				}
			}
		}
	}

	/**
	 * Register purge KCO Incomplete orders cronjob
	 *
	 * @since  2.0
	 **/
	function register_purge_cron_job() {
		if ( ! wp_next_scheduled( 'klarna_purge_cron_job_hook' ) ) {
			wp_schedule_event( current_time( 'timestamp' ), 'daily', 'klarna_purge_cron_job_hook' );
		}
	}

	/**
	 * Purge KCO Incomplete orders
	 *
	 * Deletes KCO Incomplete orders that are older than one day and have KCO Incomplete email
	 * set to guest_checkout@klarna.com indicating customer email was never captured before
	 * checkout was abandoned and all KCO incomplete orders older than 2 weeks.
	 *
	 * @since  2.0
	 **/
	function purge_kco_incomplete() {
		// Get KCO Incomplete orders that are older than a day and don't have a real customer email captured.
		$kco_incomplete_args  = array(
			'post_type'      => 'shop_order',
			'post_status'    => 'wc-kco-incomplete',
			'posts_per_page' => - 1,
			'date_query'     => array(
				array(
					'before' => '2 days ago',
				),
			),
		);
		$kco_incomplete_query = new WP_Query( $kco_incomplete_args );
		if ( $kco_incomplete_query->have_posts() ) {
			while ( $kco_incomplete_query->have_posts() ) {
				$kco_incomplete_query->the_post();
				global $post;
				if ( 'guest_checkout@klarna.com' == get_post_meta( $post->ID, '_kco_incomplete_customer_email', true ) ) {
					wp_delete_post( $post->ID, true );
				}
			}
		}
		wp_reset_postdata();

		/*
		// Get all KCO Incomplete orders older than 2 weeks.
		$kco_incomplete_args_1  = array(
			'post_type'      => 'shop_order',
			'post_status'    => 'wc-kco-incomplete',
			'posts_per_page' => - 1,
			'date_query'     => array(
				array(
					'before' => '2 weeks ago'
				)
			)
		);
		$kco_incomplete_query_1 = new WP_Query( $kco_incomplete_args_1 );
		if ( $kco_incomplete_query_1->have_posts() ) {
			while ( $kco_incomplete_query_1->have_posts() ) {
				$kco_incomplete_query_1->the_post();
				global $post;
				wp_delete_post( $post->ID, true );
			}
		}
		wp_reset_postdata();
		*/
	}

	/**
	 * Allows $order->payment_complete to work for KCO incomplete orders
	 *
	 * @since  2.0
	 **/
	function kco_incomplete_payment_complete( $order_statuses ) {
		$order_statuses[] = 'kco-incomplete';

		return $order_statuses;
	}

	/**
	 * Add options for recurring order activation.
	 *
	 * @since  2.0
	 **/
	function add_activate_recurring_option( $settings ) {
		if ( class_exists( 'WC_Subscriptions_Manager' ) ) {
			$settings['activate_recurring_title'] = array(
				'title' => __( 'Recurring orders', 'woocommerce-gateway-klarna' ),
				'type'  => 'title',
			);
			$settings['activate_recurring']       = array(
				'title'   => __( 'Automatically activate recurring orders', 'woocommerce-gateway-klarna' ),
				'type'    => 'checkbox',
				'label'   => __( 'If this option is checked recurring orders will be activated automatically', 'woocommerce-gateway-klarna' ),
				'default' => 'yes',
			);
		}

		return $settings;
	}

	/**
	 * Scheduled subscription payment.
	 *
	 * @since  2.0
	 **/
	function scheduled_subscription_payment( $amount_to_charge, $order ) {
		$order_id = klarna_wc_get_order_id( $order );

		// Check if order was created using this method
		if ( $this->id == get_post_meta( $order_id, '_payment_method', true ) ) {
			// Prevent hook from firing twice
			if ( ! get_post_meta( $order_id, '_schedule_klarna_subscription_payment', true ) ) {
				$result = $this->process_subscription_payment( $amount_to_charge, $order );
				if ( false == $result ) {
					WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order );
				} else {
					WC_Subscriptions_Manager::process_subscription_payments_on_order( $order );
					$order->payment_complete(); // Need to mark new order complete, so Subscription is marked as Active again
				}
				add_post_meta( $order_id, '_schedule_klarna_subscription_payment', 'no', true );
			} else {
				delete_post_meta( $order_id, '_schedule_klarna_subscription_payment', 'no' );
			}
		}
	}

	/**
	 * Process subscription payment.
	 *
	 * @since  2.0
	 **/
	function process_subscription_payment( $amount_to_charge, $order ) {
		if ( 0 == $amount_to_charge ) {
			// Payment complete
			$order->payment_complete();
			return true;
		}

		$order_id = klarna_wc_get_order_id( $order );

		$subscriptions = wcs_get_subscriptions_for_renewal_order( $order_id );
		reset( $subscriptions );
		$subscription_id = key( $subscriptions );

		// Reccuring token
		$klarna_recurring_token = get_post_meta( $order_id, '_klarna_recurring_token', true );
		// If the recurring token isn't stored in the subscription, grab it from parent order.
		if ( empty( $klarna_recurring_token ) ) {
			$klarna_recurring_token = get_post_meta( WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id ), '_klarna_recurring_token', true );
			update_post_meta( $order_id, '_klarna_recurring_token', $klarna_recurring_token );
			update_post_meta( $subscription_id, '_klarna_recurring_token', $klarna_recurring_token );
		}
		if ( empty( $klarna_recurring_token ) ) {
			$order->add_order_note( __( 'Klarna recurring token could not be retrieved.', 'woocommerce-gateway-klarna' ) );
		}

		// Locale
		$klarna_locale = get_post_meta( $order_id, '_klarna_locale', true );
		// If the locale isn't stored in the subscription, grab it from parent order.
		if ( empty( $klarna_locale ) ) {
			$klarna_locale = get_post_meta( WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id ), '_klarna_locale', true );
			update_post_meta( $subscription_id, '_klarna_locale', $klarna_locale );
		}
		if ( empty( $klarna_locale ) ) {
			$klarna_locale = 'en-gb';
			$order->add_order_note( __( 'Klarna locale could not be retrieved, using English as fallback language.', 'woocommerce-gateway-klarna' ) );
			update_post_meta( $subscription_id, '_klarna_locale', $klarna_locale );
		}

		$klarna_currency = get_post_meta( $order_id, '_order_currency', true );

		// Country
		if ( get_post_meta( $order_id, '_klarna_credentials_country', true ) ) {
			$klarna_country = get_post_meta( $order_id, '_klarna_credentials_country', true );
		} else {
			$klarna_country = get_post_meta( $order_id, '_billing_country', true );
		}

		// Billing country - including fallback check
		$billing_country = get_post_meta( $order_id, '_billing_country', true ) ? get_post_meta( $order_id, '_billing_country', true ) : $klarna_country;
		if ( empty( $billing_country ) ) {
			$billing_country = get_post_meta( WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id ), '_billing_country', true );
		}

		// Shipping country - including fallback check
		$shipping_country = get_post_meta( $order_id, '_shipping_country', true ) ? get_post_meta( $order_id, '_shipping_country', true ) : $klarna_country;
		if ( empty( $shipping_country ) ) {
			$shipping_country = get_post_meta( WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id ), '_billing_country', true );
		}

		// Billing postcode
		$billing_postcode = get_post_meta( $order_id, '_billing_postcode', true ) ? get_post_meta( $order_id, '_billing_postcode', true ) : get_post_meta( WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id ), '_billing_postcode', true );

		// Shipping postcode
		$shipping_postcode = get_post_meta( $order_id, '_shipping_postcode', true ) ? get_post_meta( $order_id, '_shipping_postcode', true ) : get_post_meta( WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id ), '_shipping_postcode', true );

		// Billing email
		$billing_email = get_post_meta( $order_id, '_billing_email', true ) ? get_post_meta( $order_id, '_billing_email', true ) : get_post_meta( WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id ), '_billing_email', true );

		// Shipping email
		$shipping_email = get_post_meta( $order_id, '_shipping_email', true ) ? get_post_meta( $order_id, '_shipping_email', true ) : $billing_email;

		// Billing city
		$billing_city = get_post_meta( $order_id, '_billing_city', true ) ? get_post_meta( $order_id, '_billing_city', true ) : get_post_meta( WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id ), '_billing_city', true );

		// Shipping city
		$shipping_city = get_post_meta( $order_id, '_shipping_city', true ) ? get_post_meta( $order_id, '_shipping_city', true ) : get_post_meta( WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id ), '_shipping_city', true );

		// Billing first name
		$billing_first_name = get_post_meta( $order_id, '_billing_first_name', true ) ? get_post_meta( $order_id, '_billing_first_name', true ) : get_post_meta( WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id ), '_billing_first_name', true );

		// Shipping first name
		$shipping_first_name = get_post_meta( $order_id, '_shipping_first_name', true ) ? get_post_meta( $order_id, '_shipping_first_name', true ) : get_post_meta( WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id ), '_shipping_first_name', true );

		// Billing last name
		$billing_last_name = get_post_meta( $order_id, '_billing_last_name', true ) ? get_post_meta( $order_id, '_billing_last_name', true ) : get_post_meta( WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id ), '_billing_last_name', true );

		// Shipping last name
		$shipping_last_name = get_post_meta( $order_id, '_shipping_last_name', true ) ? get_post_meta( $order_id, '_shipping_last_name', true ) : get_post_meta( WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id ), '_shipping_last_name', true );

		// Billing address 1
		$billing_address_1 = get_post_meta( $order_id, '_billing_address_1', true ) ? get_post_meta( $order_id, '_billing_address_1', true ) : get_post_meta( WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id ), '_billing_address_1', true );

		// Shipping address 1
		$shipping_address_1 = get_post_meta( $order_id, '_shipping_address_1', true ) ? get_post_meta( $order_id, '_shipping_address_1', true ) : get_post_meta( WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id ), '_shipping_address_1', true );

		// Billing phone
		$billing_phone = get_post_meta( $order_id, '_billing_phone', true ) ? get_post_meta( $order_id, '_billing_phone', true ) : get_post_meta( WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id ), '_billing_phone', true );

		// Shipping phone
		$shipping_phone = get_post_meta( $order_id, '_shipping_phone', true ) ? get_post_meta( $order_id, '_shipping_phone', true ) : $billing_phone;

		// Can't use same methods to retrieve Eid and secret that are used in frontend.
		// Need to use order billing country as base instead.
		$klarna_checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
		$klarna_country_lowercase = strtolower( $klarna_country );
		$klarna_eid               = $klarna_checkout_settings[ 'eid_' . $klarna_country_lowercase ];
		$klarna_secret            = $klarna_checkout_settings[ 'secret_' . $klarna_country_lowercase ];

		$klarna_billing = array(
			'postal_code'    => $billing_postcode,
			'email'          => $billing_email,
			'country'        => strtolower( $billing_country ),
			'city'           => $billing_city,
			'family_name'    => $billing_last_name,
			'given_name'     => $billing_first_name,
			'street_address' => $billing_address_1,
			'phone'          => $billing_phone,
		);
		if ( wc_shipping_enabled() ) {
			$klarna_shipping = array(
				'postal_code'    => $shipping_postcode,
				'email'          => $shipping_email,
				'country'        => strtolower( $shipping_country ),
				'city'           => $shipping_city,
				'family_name'    => $shipping_last_name,
				'given_name'     => $shipping_first_name,
				'street_address' => $shipping_address_1,
				'phone'          => $shipping_phone,
			);
		} else {
			$klarna_shipping = array(
				'postal_code'    => $billing_postcode,
				'email'          => $billing_email,
				'country'        => strtolower( $billing_country ),
				'city'           => $billing_city,
				'family_name'    => $billing_last_name,
				'given_name'     => $billing_first_name,
				'street_address' => $billing_address_1,
				'phone'          => $billing_phone,
			);
		}
		// Products in subscription
		$cart = array();
		if ( sizeof( $order->get_items() ) > 0 ) {
			foreach ( $order->get_items() as $item_key => $item ) {
				$_product = $order->get_product_from_item( $item );
				if ( $_product->exists() && $item['qty'] ) {
					// Get SKU or product id
					if ( $_product->get_sku() ) {
						$reference = $_product->get_sku();
					} elseif ( klarna_wc_get_product_variation_id( $_product ) ) {
						$reference = klarna_wc_get_product_variation_id( $_product );
					} else {
						$reference = klarna_wc_get_product_id( $_product );
					}
					$recurring_price = $order->get_item_total( $item, true, false ) * 100;
					if ( $item['line_total'] > 0 ) {
						$recurring_tax_rate = round( ( $item['line_tax'] / $item['line_total'] ) * 10000 );
					} else {
						$recurring_tax_rate = 0;
					}
					$cart[] = array(
						'reference'     => strval( $reference ),
						'name'          => utf8_decode( $item['name'] ),
						'quantity'      => intval( $item['qty'] ),
						'unit_price'    => intval( $recurring_price ),
						'discount_rate' => 0,
						'tax_rate'      => intval( $recurring_tax_rate ),
					);
				}
			}
		}
		// Shipping
		if ( $order->get_total_shipping() > 0 ) {
			$shipping_price = round( ( $order->get_total_shipping() + $order->get_shipping_tax() ) * 100 );
			if ( $order->get_total_shipping() > 0 ) {
				$shipping_tax_rate = round( $order->get_shipping_tax() / $order->get_total_shipping() * 10000 );
			} else {
				$shipping_tax_rate = 0;
			}
			$cart[] = array(
				'type'       => 'shipping_fee',
				'reference'  => 'SHIPPING',
				'name'       => __( 'Shipping Fee', 'woocommerce-gateway-klarna' ),
				'quantity'   => 1,
				'unit_price' => intval( $shipping_price ),
				'tax_rate'   => intval( $shipping_tax_rate ),
			);
		}
		$create = array();
		if ( 'yes' == $this->activate_recurring ) {
			$create['activate'] = true;
		} else {
			$create['activate'] = false;
		}
		$create['purchase_currency']  = $klarna_currency;
		$create['purchase_country']   = $klarna_country;
		$create['locale']             = $klarna_locale;
		$create['merchant']['id']     = $klarna_eid;
		$create['billing_address']    = $klarna_billing;
		$create['shipping_address']   = $klarna_shipping;
		$create['merchant_reference'] = array(
			'orderid1' => ltrim( $order->get_order_number(), '#' ),
		);
		$create['cart']               = array();
		foreach ( $cart as $item ) {
			$create['cart']['items'][] = $item;
		}
		$connector    = Klarna_Checkout_Connector::create( $klarna_secret, $this->klarna_server );
		$klarna_order = new Klarna_Checkout_RecurringOrder( $connector, $klarna_recurring_token );
		try {
			$klarna_order->create( $create );
			if ( isset( $klarna_order['invoice'] ) ) {
				add_post_meta( $order_id, '_klarna_order_invoice_recurring', $klarna_order['invoice'], true );
				$order->add_order_note( __( 'Klarna subscription payment invoice number: ', 'woocommerce-gateway-klarna' ) . $klarna_order['invoice'] );
			} elseif ( isset( $klarna_order['reservation'] ) ) {
				add_post_meta( $order_id, '_klarna_order_reservation_recurring', $klarna_order['reservation'], true );
				add_post_meta( $order_id, '_klarna_order_reservation', $klarna_order['reservation'], true );
				$order->add_order_note( __( 'Klarna subscription payment reservation number: ', 'woocommerce-gateway-klarna' ) . $klarna_order['reservation'] );
			}

			return true;
		} catch ( Klarna_Checkout_ApiErrorException $e ) {
			if ( $this->debug == 'yes' ) {
				// $this->logger->add( 'klarna', 'Klarna subscription payment API error: ' . $e->__toString() );
			}
			$pay_load = $e->getPayload();
			if ( 402 == $e->getCode() ) {
				$order->add_order_note( sprintf( __( 'Klarna subscription payment failed. Error code: %1$s. Reason: %2$s. Payment method: %3$s.', 'woocommerce-gateway-klarna' ), $e->getCode(), $pay_load['reason'], $pay_load['payment_method']['type'] ) );
				update_post_meta( $order_id, 'klarna_subscription_renewal_status', $e->getCode() );
			} else {
				$internal_message = '';
				if ( isset( $pay_load['internal_message'] ) ) {
					$internal_message = $pay_load['internal_message'];
				}
				$order->add_order_note( sprintf( __( 'Klarna subscription payment failed. Error code: %1$s. Reason: %2$s. Internal message: %3$s.', 'woocommerce-gateway-klarna' ), $e->getCode(), $pay_load['http_status_message'], $internal_message ) );
			}

			update_post_meta( $order_id, 'klarna_subscription_error_info_1', var_export( $klarna_order, true ) );
			update_post_meta( $order_id, 'klarna_subscription_error_info_2', var_export( $create, true ) );
			update_post_meta( $order_id, 'klarna_subscription_error_info_3', var_export( $pay_load, true ) );
			return false;
		}
	}

	/**
	 * Do not copy Klarna invoice number from completed subscription order to its renewal orders.
	 *
	 * @since  2.0
	 **/
	function kco_recurring_do_not_copy_meta_data( $order_meta_query, $original_order_id, $renewal_order_id, $new_order_role ) {
		$order_meta_query .= " AND `meta_key` NOT IN ('_klarna_invoice_number')";

		return $order_meta_query;
	}


	/**
	 * Pushes Klarna order update in AJAX calls.
	 *
	 * @since  2.0
	 **/
	function ajax_update_klarna_order() {
		// Check if Euro is selected, get correct country
		if ( 'EUR' == get_woocommerce_currency() && WC()->session->get( 'klarna_euro_country' ) ) {
			$klarna_c = strtolower( WC()->session->get( 'klarna_euro_country' ) );

			if ( in_array( strtoupper( $klarna_c ), array( 'DE', 'FI', 'NL' ) ) ) {
				// Add correct EID & secret specific to country if the curency is EUR and the country is DE or FI.
				$eid          = $this->settings[ "eid_$klarna_c" ];
				$sharedSecret = html_entity_decode( $this->settings[ "secret_$klarna_c" ] );
			} else {
				// Otherwise use the general eid and secret (filterable) if we're using EUR as currency for a global KCO checkout
				$eid          = $this->klarna_eid;
				$sharedSecret = html_entity_decode( $this->klarna_secret );
			}
		} else {
			$eid          = $this->klarna_eid;
			$sharedSecret = html_entity_decode( $this->klarna_secret );
		}

		if ( $this->is_rest() ) {
			if ( $this->testmode == 'yes' ) {
				if ( in_array(
					strtoupper( $this->klarna_country ), apply_filters(
						'klarna_is_rest_countries_eu', array(
							'DK',
							'GB',
							'NL',
						)
					)
				) ) {
					$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_TEST_BASE_URL;
				} elseif ( in_array( strtoupper( $this->klarna_country ), apply_filters( 'klarna_is_rest_countries_na', array( 'US' ) ) ) ) {
					$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_TEST_BASE_URL;
				}
			} else {
				if ( in_array(
					strtoupper( $this->klarna_country ), apply_filters(
						'klarna_is_rest_countries_eu', array(
							'DK',
							'GB',
							'NL',
						)
					)
				) ) {
					$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_BASE_URL;
				} elseif ( in_array( strtoupper( $this->klarna_country ), apply_filters( 'klarna_is_rest_countries_na', array( 'US' ) ) ) ) {
					$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_BASE_URL;
				}
			}
			$klarna_order_id = WC()->session->get( 'klarna_checkout' );
			$connector       = Klarna\Rest\Transport\Connector::create( $eid, $sharedSecret, $klarna_server_url );
			$klarna_order    = new \Klarna\Rest\Checkout\Order( $connector, $klarna_order_id );
		} else {
			$connector    = Klarna_Checkout_Connector::create( $sharedSecret, $this->klarna_server );
			$klarna_order = new Klarna_Checkout_Order( $connector, WC()->session->get( 'klarna_checkout' ) );
			$klarna_order->fetch();
		}

		// Process cart contents and prepare them for Klarna
		include_once KLARNA_DIR . 'classes/class-wc-to-klarna.php';

		$wc_to_klarna = new WC_Gateway_Klarna_WC2K( $this->is_rest(), $this->klarna_country );
		$cart         = $wc_to_klarna->process_cart_contents();

		if ( 0 == count( $cart ) ) {
			$klarna_order = null;
		} else {
			// Reset cart
			if ( $this->is_rest() ) {
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

			try {
				$klarna_order->update( apply_filters( 'kco_update_order', $update ) );
			} catch ( Exception $e ) {
				if ( $this->debug == 'yes' ) {
					// $this->logger->add( 'klarna', 'Klarna API error: ' . $e->__toString() );
				}
			}
		}
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
	 * Initialise Gateway Settings Form Fields
	 *
	 * @since 1.0.0
	 */
	function init_form_fields() {
		$this->form_fields = include KLARNA_DIR . 'includes/settings-checkout.php';
	}

	/**
	 * Admin Panel Options
	 *
	 * @since 1.0.0
	 */
	public function admin_options() { ?>
		<?php if ( ! get_option( 'permalink_structure' ) ) { ?>
			<div id="message" class="error">
				<p>
					<?php
					printf( __( 'Klarna Checkout requires pretty permalinks to be enabled. <a href="%s">Click here</a> to update your permalinks structure.', 'woocommerce-gateway-klarna' ), admin_url( 'options-permalink.php' ) )
					?>
				</p>
			</div>
		<?php } ?>

		<h3><?php _e( 'Klarna Checkout', 'woocommerce-gateway-klarna' ); ?></h3>

		<p>
			<?php printf( __( 'With Klarna Checkout your customers can pay by invoice or credit card. Klarna Checkout works by replacing the standard WooCommerce checkout form. Documentation <a href="%s" target="_blank">can be found here</a>.', 'woocommerce-gateway-klarna' ), 'https://docs.woothemes.com/document/klarna/' ); ?>
		</p>

		<?php
		// If the WooCommerce terms page isn't set, do nothing.
		$klarna_terms_page = get_option( 'woocommerce_terms_page_id' );
		if ( empty( $klarna_terms_page ) && empty( $this->terms_url ) ) {
			echo '<div class="error"><p>' . __( 'You need to specify a Terms Page in the WooCommerce settings or in the Klarna Checkout settings in order to enable the Klarna Checkout payment method.', 'woocommerce-gateway-klarna' ) . '</p></div>';
		}
		?>

		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table><!--/.form-table-->
	<?php
	}

	/**
	 * Disabled KCO on regular checkout page
	 *
	 * @since 1.0.0
	 */
	function is_available() {
		if ( defined( 'WOOCOMMERCE_KLARNA_AVAILABLE' ) || ! is_checkout() ) {
			return true;
		}

		return false;
	}

	/**
	 * Set up Klarna configuration.
	 *
	 * @since  2.0
	 **/
	function configure_klarna( $klarna, $country ) {
		// Country and language
		switch ( $country ) {
			case 'NO':
			case 'NB':
				$klarna_language = 'nb-no';
				$klarna_currency = 'NOK';
				$klarna_eid      = $this->eid_no;
				$klarna_secret   = $this->secret_no;
				break;
			case 'FI':
				// Check if WPML is used and determine if Finnish or Swedish is used as language
				if ( class_exists( 'woocommerce_wpml' ) && defined( 'ICL_LANGUAGE_CODE' ) && strtoupper( ICL_LANGUAGE_CODE ) == 'SV' ) {
					$klarna_language = 'sv-fi'; // Swedish
				} else {
					$klarna_language = 'fi-fi'; // Finnish
				}
				$klarna_currency = 'EUR';
				$klarna_eid      = $this->eid_fi;
				$klarna_secret   = $this->secret_fi;
				break;
			case 'SE':
			case 'SV':
				$klarna_language = 'sv-se';
				$klarna_currency = 'SEK';
				$klarna_eid      = $this->eid_se;
				$klarna_secret   = $this->secret_se;
				break;
			case 'DE':
				$klarna_language = 'de-de';
				$klarna_currency = 'EUR';
				$klarna_eid      = $this->eid_de;
				$klarna_secret   = $this->secret_de;
				break;
			case 'AT':
				$klarna_language = 'de-at';
				$klarna_currency = 'EUR';
				$klarna_eid      = $this->eid_at;
				$klarna_secret   = $this->secret_at;
				break;
			case 'GB':
				$klarna_language = 'en-gb';
				$klarna_currency = 'gbp';
				$klarna_eid      = $this->eid_uk;
				$klarna_secret   = $this->secret_uk;
				break;
			default:
				$klarna_language = '';
				$klarna_currency = '';
				$klarna_eid      = '';
				$klarna_secret   = '';
		}
		$klarna->config( $eid = $klarna_eid, $secret = $klarna_secret, $country = $country, $language = $klarna_language, $currency = $klarna_currency, $mode = $this->klarna_mode, $pcStorage = 'json', $pcURI = '/srv/pclasses.json', $ssl = $this->klarna_ssl, $candice = false );
	}

	/**
	 * Render checkout page
	 *
	 * @since 1.0.0
	 */
	public static function get_klarna_checkout_page() {
		global $woocommerce;
		$current_user = wp_get_current_user();

		// Display Checkout page
		ob_start();
		include KLARNA_DIR . 'includes/checkout/checkout.php';

		return ob_get_clean();
	} // End Function

	public static function get_klarna_thank_you_page() {
		global $woocommerce;
		$current_user = wp_get_current_user();

		ob_start();
		include KLARNA_DIR . 'includes/checkout/thank-you.php';

		return ob_get_clean();
	}

	/**
	 * Creates a WooCommerce order, or updates if already created
	 *
	 * @since 1.0.0
	 */
	function update_or_create_local_order( $customer_email = '' ) {
		if ( is_user_logged_in() ) {
			global $current_user;
			$customer_email = $current_user->user_email;
		}
		if ( '' == $customer_email ) {
			$customer_email = 'guest_checkout@klarna.com';
		}
		if ( ! is_email( $customer_email ) ) {
			return;
		}
		// Check quantities
		global $woocommerce;
		$result = $woocommerce->cart->check_cart_item_stock();
		if ( is_wp_error( $result ) ) {
			return $result->get_error_message();
		}
		// Update the local order
		include_once KLARNA_DIR . 'classes/class-klarna-to-wc.php';
		$klarna_to_wc = new WC_Gateway_Klarna_K2WC();
		$klarna_to_wc->set_rest( $this->is_rest() );
		$klarna_to_wc->set_eid( $this->klarna_eid );
		$klarna_to_wc->set_secret( $this->klarna_secret );
		// $klarna_to_wc->set_klarna_log( $this->logger );
		$klarna_to_wc->set_klarna_debug( $this->debug );
		$klarna_to_wc->set_klarna_test_mode( $this->testmode );
		$klarna_to_wc->set_klarna_server( $this->klarna_server );
		$klarna_to_wc->set_klarna_credentials_country( $this->klarna_credentials_country );
		if ( $customer_email ) {
			$orderid = $klarna_to_wc->prepare_wc_order( $customer_email );
		} else {
			$orderid = $klarna_to_wc->prepare_wc_order();
		}

		return $orderid;
	}

	/**
	 * Order confirmation via IPN
	 *
	 * @since 1.0.0
	 */
	function check_checkout_listener() {
		// WC_Gateway_Klarna::log( 'Klarna Listener Order ID: ' . $_GET['sid']  . ' $_GET: ' . var_export( $_GET, true ) );
		if ( isset( $_GET['validate'] ) ) {
			exit;
		}
		$klarna_eid    = false;
		$klarna_secret = false;

		// Retrieve Eid & Secret from order if it exist
		if ( isset( $_GET['sid'] ) ) {
			$order_id                   = sanitize_key( $_GET['sid'] );
			$klarna_credentials_country = get_post_meta( $order_id, '_klarna_credentials_country', true );

			// Hack for UK/GB. We store the settings as UK but Klarna is using GB
			if ( 'gb' == strtolower( $klarna_credentials_country ) ) {
				$klarna_credentials_country = 'uk';
			}

			if ( $klarna_credentials_country ) {
				$klarna_credentials_country = strtolower( $klarna_credentials_country );
				$klarna_eid                 = $this->settings[ "eid_$klarna_credentials_country" ];
				$klarna_secret              = html_entity_decode( $this->settings[ "secret_$klarna_credentials_country" ] );
			}
		}

		// If we don't get an Eid from the order then we can grab the data from the returned country
		// @TODO - this can be removed over time since the credential country now is stored as post meta in the order.
		if ( empty( $klarna_eid ) ) {
			switch ( $_GET['scountry'] ) {
				case 'SE':
					$klarna_secret = $this->secret_se;
					$klarna_eid    = $this->eid_se;
					break;
				case 'FI':
					$klarna_secret = $this->secret_fi;
					$klarna_eid    = $this->eid_se;
					break;
				case 'NO':
					$klarna_secret = $this->secret_no;
					$klarna_eid    = $this->eid_no;
					break;
				case 'DE':
					$klarna_secret = $this->secret_de;
					$klarna_eid    = $this->eid_de;
					break;
				case 'AT':
					$klarna_secret = $this->secret_at;
					$klarna_eid    = $this->eid_at;
					break;
				case 'dk':
					$klarna_secret = $this->secret_dk;
					$klarna_eid    = $this->eid_dk;
					break;
				case 'nl':
					$klarna_secret = $this->secret_nl;
					$klarna_eid    = $this->eid_nl;
					break;
				case 'gb':
					$klarna_secret = $this->secret_uk;
					$klarna_eid    = $this->eid_uk;
					break;
				case 'us':
					$klarna_secret = $this->secret_us;
					$klarna_eid    = $this->eid_us;
					break;
				default:
					$klarna_secret = '';
			}
		}
		// Process cart contents and prepare them for Klarna
		if ( isset( $_GET['klarna_order'] ) ) {
			include_once KLARNA_DIR . 'classes/class-klarna-to-wc.php';
			$klarna_to_wc = new WC_Gateway_Klarna_K2WC();
			$klarna_to_wc->set_rest( $this->is_rest() );
			$klarna_to_wc->set_eid( $klarna_eid );
			$klarna_to_wc->set_secret( $klarna_secret );
			$klarna_to_wc->set_klarna_order_uri( $_GET['klarna_order'] );
			// $klarna_to_wc->set_klarna_log( $this->logger );
			$klarna_to_wc->set_klarna_test_mode( $this->testmode );
			$klarna_to_wc->set_klarna_debug( $this->debug );
			$klarna_to_wc->set_klarna_server( $this->klarna_server );
			$klarna_to_wc->set_klarna_credentials_country( $this->klarna_credentials_country );
			$klarna_to_wc->listener();
		}
	} // End function check_checkout_listener

	/**
	 * Add out of stock notice if validate callback fails.
	 */
	function add_validate_notice() {
		if ( ! is_cart() ) {
			return;
		}
		if ( isset( $_GET['stock_validate_failed'] ) ) {
			wc_add_notice( __( 'This product is currently out of stock and unavailable.', 'woocommerce-gateway-klarna' ), 'error' );
		}
	}

	/**
	 * Helper function get_enabled
	 *
	 * @since 1.0.0
	 */
	function get_enabled() {
		return $this->enabled;
	}

	/**
	 * Helper function get_modify_standard_checkout_url
	 *
	 * @since 1.0.0
	 */
	function get_modify_standard_checkout_url() {
		return $this->modify_standard_checkout_url;
	}

	/**
	 * Helper function get_klarna_checkout_page
	 *
	 * @since 1.0.0
	 */
	function get_klarna_checkout_url() {
		return $this->klarna_checkout_url;
	}

	/**
	 * Helper function get_klarna_country
	 *
	 * @since 1.0.0
	 */
	function get_klarna_country() {
		return $this->klarna_country;
	}

	/**
	 * Helper function - get correct currency for selected country
	 *
	 * @since 1.0.0
	 */
	function get_currency_for_country( $country ) {
		switch ( $country ) {
			case 'DK':
				$currency = 'DKK';
				break;
			case 'DE':
				$currency = 'EUR';
				break;
			case 'NL':
				$currency = 'EUR';
				break;
			case 'NO':
				$currency = 'NOK';
				break;
			case 'FI':
				$currency = 'EUR';
				break;
			case 'SE':
				$currency = 'SEK';
				break;
			case 'AT':
				$currency = 'EUR';
				break;
			default:
				$currency = '';
		}

		return $currency;
	}

	/**
	 * Helper function - get Account Signup Text
	 *
	 * @since 1.0.0
	 */
	public function get_account_signup_text() {
		return $this->account_signup_text;
	}

	/**
	 * Helper function - get Account Login Text
	 *
	 * @since 1.0.0
	 */
	public function get_account_login_text() {
		return $this->account_login_text;
	}

	/**
	 * Helper function - get Subscription Product ID
	 *
	 * @since 2.0.0
	 */
	public function get_subscription_product_id() {
		global $woocommerce;
		$subscription_product_id = false;
		if ( ! empty( $woocommerce->cart->cart_contents ) ) {
			foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
				if ( WC_Subscriptions_Product::is_subscription( $cart_item['product_id'] ) ) {
					$subscription_product_id = $cart_item['product_id'];
					break;
				}
			}
		}

		return $subscription_product_id;
	}

	/**
	 * Can the order be refunded via Klarna?
	 *
	 * @param  WC_Order $order
	 *
	 * @return bool
	 * @since  2.0.0
	 */
	public function can_refund_order( $order ) {
		if ( get_post_meta( klarna_wc_get_order_id( $order ), '_klarna_invoice_number', true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Refund order in Klarna system
	 *
	 * @param  integer $orderid
	 * @param  integer $amount
	 * @param  string  $reason
	 *
	 * @return bool
	 * @since  2.0.0
	 */
	public function process_refund( $orderid, $amount = null, $reason = '' ) {
		// Check if order was created using this method
		if ( $this->id == get_post_meta( $orderid, '_payment_method', true ) ) {
			$order = wc_get_order( $orderid );
			if ( ! $this->can_refund_order( $order ) ) {
				if ( $this->debug == 'yes' ) {
					// $this->logger->add( 'klarna', 'Refund Failed: No Klarna invoice ID.' );
				}
				$order->add_order_note( __( 'This order cannot be refunded. Please make sure it is activated.', 'woocommerce-gateway-klarna' ) );

				return new WP_Error( 'error', __( 'This order cannot be refunded. Please make sure it is activated.', 'woocommerce-gateway-klarna' ) );
			}
			if ( 'v2' == get_post_meta( $orderid, '_klarna_api', true ) ) {
				$country = get_post_meta( $orderid, '_billing_country', true );
				$klarna  = new Klarna\XMLRPC\Klarna();
				$this->configure_klarna( $klarna, $country );
				$invNo        = get_post_meta( $orderid, '_klarna_invoice_number', true );
				$klarna_order = new WC_Gateway_Klarna_Order( $order, $klarna );
				$refund_order = $klarna_order->refund_order( $amount, $reason, $invNo );
			} elseif ( 'rest' == get_post_meta( $orderid, '_klarna_api', true ) ) {

				if ( get_post_meta( $orderid, '_klarna_credentials_country', true ) ) {
					$country = get_post_meta( $orderid, '_klarna_credentials_country', true );
				} else {
					$country = get_post_meta( $orderid, '_billing_country', true );
				}

				/**
				 * Need to send local order to constructor and Klarna order to method
				 */
				if ( $this->testmode == 'yes' ) {
					if ( 'gb' === strtolower( $country ) || 'dk' === strtolower( $country ) || 'nl' === strtolower( $country ) ) {
						$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_TEST_BASE_URL;
					} elseif ( 'us' === strtolower( $country ) ) {
						$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_TEST_BASE_URL;
					}
				} else {
					if ( 'gb' === strtolower( $country ) || 'dk' === strtolower( $country ) || 'nl' === strtolower( $country ) ) {
						$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_BASE_URL;
					} elseif ( 'us' === strtolower( $country ) ) {
						$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_BASE_URL;
					}
				}
				if ( 'gb' === strtolower( $country ) || 'dk' === strtolower( $country ) || 'nl' === strtolower( $country ) ) {
					if ( 'gb' === strtolower( $country ) ) {
						$eid    = $this->eid_uk;
						$secret = html_entity_decode( $this->secret_uk );
					} elseif ( 'dk' === strtolower( $country ) ) {
						$eid    = $this->eid_dk;
						$secret = html_entity_decode( $this->secret_dk );
					} elseif ( 'nl' === strtolower( $country ) ) {
						$eid    = $this->eid_nl;
						$secret = html_entity_decode( $this->secret_nl );
					}

					$connector = Klarna\Rest\Transport\Connector::create( $eid, $secret, $klarna_server_url );
				} elseif ( 'us' === strtolower( $country ) ) {
					$connector = Klarna\Rest\Transport\Connector::create( $this->eid_us, $this->secret_us, $klarna_server_url );
				}
				$klarna_order_id = get_post_meta( $orderid, '_klarna_order_id', true );
				$k_order         = new Klarna\Rest\OrderManagement\Order( $connector, $klarna_order_id );
				$k_order->fetch();
				$klarna_order = new WC_Gateway_Klarna_Order( $order );
				$refund_order = $klarna_order->refund_order_rest( $amount, $reason, $k_order );
			}
			if ( $refund_order ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determines which version of Klarna API should be used
	 *
	 * @return boolean
	 * @since  2.0.0
	 */
	function is_rest() {
		if ( in_array(
			strtoupper( $this->klarna_country ), apply_filters(
				'klarna_is_rest_countries', array(
					'US',
					'DK',
					'GB',
					'NL',
				)
			)
		) ) {
			// Set it in session as well, to be used in Shortcodes class
			WC()->session->set( 'klarna_is_rest', true );

			return true;
		}
		// Set it in session as well, to be used in Shortcodes class
		WC()->session->set( 'klarna_is_rest', false );

		return false;
	}

	/**
	 * Determines if KCO checkout page should be displayed.
	 *
	 * @return boolean
	 * @since  2.0.0
	 */
	function show_kco() {
		// Don't render the Klarna Checkout form if the payment gateway isn't enabled.
		if ( $this->enabled != 'yes' ) {
			// Set it in session as well, to be used in Shortcodes class
			WC()->session->set( 'klarna_show_kco', false );

			return false;
		}
		// If checkout registration is disabled and not logged in, the user cannot checkout
		global $woocommerce;
		$checkout = $woocommerce->checkout();
		if ( ! $checkout->enable_guest_checkout && ! is_user_logged_in() ) {
			echo '<div>';
			echo apply_filters( 'klarna_checkout_must_be_logged_in_message', sprintf( __( 'You must be logged in to checkout. %1$s or %2$s.', 'woocommerce-gateway-klarna' ), '<a href="' . wp_login_url() . '" title="' . __( 'Login', 'woocommerce-gateway-klarna' ) . '">' . __( 'Login', 'woocommerce-gateway-klarna' ) . '</a>', '<a href="' . wp_registration_url() . '" title="' . __( 'create an account', 'woocommerce-gateway-klarna' ) . '">' . __( 'create an account', 'woocommerce-gateway-klarna' ) . '</a>' ) );
			echo '</div>';
			WC()->session->set( 'klarna_show_kco', false );

			return false;
		}
		// If no Klarna country is set - return.
		if ( empty( $this->klarna_country ) || empty( $this->klarna_eid ) || empty( $this->klarna_secret ) ) {
			echo apply_filters( 'klarna_checkout_wrong_country_message', sprintf( __( 'Sorry, you can not buy via Klarna Checkout from your country or currency. Please <a href="%s">use another payment method</a>. ', 'woocommerce-gateway-klarna' ), get_permalink( get_option( 'woocommerce_checkout_page_id' ) ) ) );
			WC()->session->set( 'klarna_show_kco', false );

			return false;
		}
		// If the WooCommerce terms page or the Klarna Checkout settings field
		// Terms Page isn't set, do nothing.
		if ( empty( $this->terms_url ) ) {
			WC()->session->set( 'klarna_show_kco', false );

			return false;
		}
		WC()->session->set( 'klarna_show_kco', true );

		return true;
	}

	/**
	 * Get a link to the transaction on the 3rd party gateway size (if applicable).
	 *
	 * @param  WC_Order $order the order object.
	 *
	 * @return string transaction URL, or empty string.
	 */
	public function get_transaction_url( $order ) {
		// Check if order is completed
		if ( get_post_meta( klarna_wc_get_order_id( $order ), '_klarna_order_activated', true ) ) {
			if ( $this->testmode == 'yes' ) {
				$this->view_transaction_url = 'https://testdrive.klarna.com/invoices/%s.pdf';
			} else {
				$this->view_transaction_url = 'https://online.klarna.com/invoices/%s.pdf';
			}
		}

		return parent::get_transaction_url( $order );
	}

	/**
	 * Pass KCO iframe update AJAX actions to WCML, so it can filter the currency.
	 *
	 * @param $actions
	 *
	 * @return array
	 */
	function pass_ajax_actions_to_wcml( $actions ) {
		$actions[] = 'kco_iframe_change_cb';
		$actions[] = 'kco_iframe_shipping_address_change_cb';
		$actions[] = 'kco_iframe_shipping_option_change_cb';
		$actions[] = 'klarna_checkout_remove_coupon_callback';
		$actions[] = 'klarna_checkout_coupons_callback';
		$actions[] = 'klarna_checkout_cart_callback_remove';
		$actions[] = 'klarna_checkout_cart_callback_update';
		$actions[] = 'klarna_checkout_shipping_callback';
		$actions[] = 'klarna_checkout_order_note_callback';
		$actions[] = 'klarna_checkout_country_callback';

		return $actions;
	}

} // End class WC_Gateway_Klarna_Checkout
// Extra Class for Klarna Checkout
class WC_Gateway_Klarna_Checkout_Extra {

	public function __construct() {
		add_action( 'init', array( $this, 'start_session' ), 1 );
		add_action( 'before_woocommerce_init', array( $this, 'prevent_caching' ) );
		add_filter( 'woocommerce_get_checkout_url', array( $this, 'change_checkout_url' ), 20 );
		add_action( 'woocommerce_register_form_start', array( $this, 'add_account_signup_text' ) );
		add_action( 'woocommerce_login_form_start', array( $this, 'add_account_login_text' ) );
		add_action( 'woocommerce_checkout_after_order_review', array( $this, 'klarna_add_link_to_kco_page' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'klarna_checkout_enqueuer' ) );
		// Filter Checkout page ID, so WooCommerce Google Analytics integration can
		// output Ecommerce tracking code on Klarna Thank You page
		add_filter( 'woocommerce_get_checkout_page_id', array( $this, 'change_checkout_page_id' ) );
		// Change is_checkout to true on KCO page
		add_filter( 'woocommerce_is_checkout', array( $this, 'change_is_checkout_value' ) );
		// Address update listener
		add_action( 'template_redirect', array( $this, 'address_update_listener' ) );
	}

	/**
	 * Prevent caching in KCO and KCO thank you pages
	 *
	 * @since 1.9.8.2
	 */
	function prevent_caching() {
		$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
		$checkout_pages    = array();
		$thank_you_pages   = array();
		// Clean request URI to remove all parameters
		$clean_req_uri = explode( '?', $_SERVER['REQUEST_URI'] );
		$clean_req_uri = $clean_req_uri[0];
		$clean_req_uri = trailingslashit( $clean_req_uri );
		$length        = strlen( $clean_req_uri );
		// Get arrays of checkout and thank you pages for all countries
		if ( is_array( $checkout_settings ) ) {
			foreach ( $checkout_settings as $cs_key => $cs_value ) {
				if ( strpos( $cs_key, 'klarna_checkout_url_' ) !== false ) {
					$checkout_pages[ $cs_key ] = substr( $cs_value, 0 - $length );
				}
				if ( strpos( $cs_key, 'klarna_checkout_thanks_url_' ) !== false ) {
					$thank_you_pages[ $cs_key ] = substr( $cs_value, 0 - $length );
				}
			}
		}
		// Check if string is longer than 1 character, to avoid homepage caching
		if ( strlen( $clean_req_uri ) > 1 ) {
			if ( in_array( $clean_req_uri, $checkout_pages ) || in_array( $clean_req_uri, $thank_you_pages ) ) {
				// Prevent caching
				if ( ! defined( 'DONOTCACHEPAGE' ) ) {
					define( 'DONOTCACHEPAGE', 'true' );
				}
				if ( ! defined( 'DONOTCACHEOBJECT' ) ) {
					define( 'DONOTCACHEOBJECT', 'true' );
				}
				if ( ! defined( 'DONOTCACHEDB' ) ) {
					define( 'DONOTCACHEDB', 'true' );
				}
				nocache_headers();
			}
		}
	}

	/**
	 * Add link to KCO page from standard checkout page.
	 * Initiated here because KCO class is instantiated multiple times
	 * making the hook fire multiple times as well.
	 *
	 * @since  2.0
	 */
	function klarna_add_link_to_kco_page() {
		global $klarna_checkout_url;
		$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
		if ( 'yes' == $checkout_settings['enabled'] && '' != $checkout_settings['klarna_checkout_button_label'] && 'yes' == $checkout_settings['add_klarna_checkout_button'] ) {
			echo '<div class="woocommerce"><a style="margin-top:1em" href="' . $klarna_checkout_url . '" class="button std-checkout-button">' . $checkout_settings['klarna_checkout_button_label'] . '</a></div>';
		}
	}

	// Set session
	function start_session() {
		new WC_Gateway_Klarna_Checkout(); // Still need to initiate it here, otherwise shortcode won't work
		// if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
		/*
		$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
		$is_enabled        = ( isset( $checkout_settings['enabled'] ) ) ? $checkout_settings['enabled'] : '';

		$checkout_pages  = array();
		$thank_you_pages = array();

		// Clean request URI to remove all parameters
		$clean_req_uri = explode( '?', $_SERVER['REQUEST_URI'] );
		$clean_req_uri = $clean_req_uri[0];
		$clean_req_uri = trailingslashit( $clean_req_uri );
		$length        = strlen( $clean_req_uri );

		// Get arrays of checkout and thank you pages for all countries
		if ( is_array( $checkout_settings ) ) {
			foreach ( $checkout_settings as $cs_key => $cs_value ) {
				if ( strpos( $cs_key, 'klarna_checkout_url_' ) !== false ) {
					$checkout_pages[ $cs_key ] = substr( trailingslashit( $cs_value ), 0 - $length );
				}
				if ( strpos( $cs_key, 'klarna_checkout_thanks_url_' ) !== false ) {
					$thank_you_pages[ $cs_key ] = substr( trailingslashit( $cs_value ), 0 - $length );
				}
			}
		}

		// Start session if on a KCO or KCO Thank You page and KCO enabled
		if ( ( in_array( $clean_req_uri, $checkout_pages ) || in_array( $clean_req_uri, $thank_you_pages ) ) && 'yes' == $is_enabled ) {
			session_start();
		}
		// }
		*/
	}

	function klarna_checkout_css() {
		global $post;
		global $klarna_checkout_url;
		$checkout_page_id  = url_to_postid( $klarna_checkout_url );
		$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
		if ( $post->ID == $checkout_page_id ) {
			if ( '' != $checkout_settings['color_button'] || '' != $checkout_settings['color_button_text'] ) {
			?>
				<style>
					a.std-checkout-button,
					.klarna_checkout_coupon input[type="submit"] {
						background: <?php echo $checkout_settings['color_button']; ?> !important;
						border: none !important;
						color: <?php echo $checkout_settings['color_button_text']; ?> !important;
					}
				</style>
			<?php
			}
		}
	}

	/**
	 *  Change Checkout URL
	 *
	 *  Triggered from the 'woocommerce_get_checkout_url' action.
	 *  Alter the checkout url to the custom Klarna Checkout Checkout page.
	 **/
	public function change_checkout_url( $url ) {

		// Don't change the url if this is a subscription switch.
		if ( isset( $_GET['switch-subscription'] ) || ( method_exists( 'WC_Subscriptions_Switcher', 'cart_contains_switches' ) && WC_Subscriptions_Switcher::cart_contains_switches() ) ) {
			return $url;
		}

		// Don't change the url if Klarna Checkout is not available.
		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
		if ( false === array_key_exists( 'klarna_checkout', $available_gateways ) ) {
			return $url;
		}

		if ( ! is_admin() ) {
			$klarna_checkout_url = WC_Gateway_Klarna_Checkout_Variables::get_klarna_checkout_url();
			$klarna_country      = WC_Gateway_Klarna_Checkout_Variables::get_klarna_country();

			if ( ! $klarna_checkout_url || '' === $klarna_checkout_url ) {
				return $url;
			}

			$checkout_settings            = get_option( 'woocommerce_klarna_checkout_settings' );
			$enabled                      = $checkout_settings['enabled'];
			$modify_standard_checkout_url = $checkout_settings['modify_standard_checkout_url'];
			$available_countries          = $this->get_authorized_countries();

			// Change the Checkout URL if this is enabled in the settings
			if ( 'yes' === $modify_standard_checkout_url &&
				'yes' === $enabled &&
				! empty( $klarna_checkout_url ) &&
				in_array( strtoupper( $klarna_country ), $available_countries, true ) &&
				array_key_exists( strtoupper( $klarna_country ), WC()->countries->get_allowed_countries() )
			) {
				if ( class_exists( 'WC_Subscriptions_Cart' ) && WC_Subscriptions_Cart::cart_contains_subscription() ) {
					if ( in_array( strtoupper( $klarna_country ), array( 'SE', 'FI', 'NO' ), true ) ) {
						$url = $klarna_checkout_url;
					} else {
						return $url;
					}
				} else {
					$url = $klarna_checkout_url;
				}
			}
		}

		return $url;
	}

	/**
	 *  Function Add Account signup text
	 *
	 * @since version 1.8.9
	 *    Add text above the Account Registration Form.
	 *  Useful for legal text for German stores. See documentation for more information. Leave blank to disable.
	 **/
	public function add_account_signup_text() {
		$checkout_settings   = get_option( 'woocommerce_klarna_checkout_settings' );
		$account_signup_text = ( isset( $checkout_settings['account_signup_text'] ) ) ? $checkout_settings['account_signup_text'] : '';
		// Change the Checkout URL if this is enabled in the settings
		if ( ! empty( $account_signup_text ) ) {
			echo $account_signup_text;
		}
	}

	/**
	 *  Function Add Account login text
	 *
	 * @since version 1.8.9
	 *    Add text above the Account Login Form.
	 *  Useful for legal text for German stores. See documentation for more information. Leave blank to disable.
	 **/
	public function add_account_login_text() {
		$checkout_settings  = get_option( 'woocommerce_klarna_checkout_settings' );
		$account_login_text = ( isset( $checkout_settings['account_login_text'] ) ) ? $checkout_settings['account_login_text'] : '';
		// Change the Checkout URL if this is enabled in the settings
		if ( ! empty( $account_login_text ) ) {
			echo $account_login_text;
		}
	}

	/**
	 * Change checkout page ID to Klarna Thank You page, when in Klarna Thank You page only
	 */
	public function change_checkout_page_id( $checkout_page_id ) {
		global $post;
		global $klarna_checkout_thanks_url;
		if ( is_page() ) {
			$current_page_url = get_permalink( $post->ID );
			// Compare Klarna Thank You page URL to current page URL
			if ( esc_url( trailingslashit( $klarna_checkout_thanks_url ) ) == esc_url( trailingslashit( $current_page_url ) ) ) {
				$checkout_page_id = $post->ID;
			}
		}

		return $checkout_page_id;
	}

	/**
	 * Set is_checkout to true on KCO page
	 */
	function change_is_checkout_value( $bool ) {
		global $post;
		global $klarna_checkout_url;
		if ( is_page() ) {
			$current_page_url = get_permalink( $post->ID );
			// Compare Klarna Checkout page URL to current page URL
			if ( esc_url( trailingslashit( $klarna_checkout_url ) ) == esc_url( trailingslashit( $current_page_url ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Enqueue Klarna Checkout javascript.
	 *
	 * @since  2.0
	 **/
	function klarna_checkout_enqueuer() {
		$suffix               = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$assets_path          = str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/';
		$frontend_script_path = $assets_path . 'js/frontend/';
		if ( true == $this->is_rest() ) {
			$version = 'v3';
		} else {
			$version = 'v2';
		}
		wp_register_script( 'klarna_checkout', KLARNA_URL . 'assets/js/klarna-checkout.js', array(), WC_KLARNA_VER, true );
		wp_localize_script(
			'klarna_checkout', 'kcoAjax', array(
				'ajaxurl'               => admin_url( 'admin-ajax.php' ),
				'klarna_checkout_nonce' => wp_create_nonce( 'klarna_checkout_nonce' ),
				'version'               => $version,
				'coupon_success'        => __( 'Coupon code applied successfully.', 'woocommerce-gateway-klarna' ),
				'coupon_fail'           => __( 'Coupon could not be added.', 'woocommerce-gateway-klarna' ),
			)
		);
		wp_register_style( 'klarna_checkout', KLARNA_URL . 'assets/css/klarna-checkout.css', array(), WC_KLARNA_VER );

		if ( is_page() ) {
			$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
			$checkout_pages    = array();
			$thank_you_pages   = array();
			// Clean request URI to remove all parameters
			$clean_req_uri = explode( '?', $_SERVER['REQUEST_URI'] );
			$clean_req_uri = $clean_req_uri[0];
			$clean_req_uri = trailingslashit( $clean_req_uri );
			$length        = strlen( $clean_req_uri );

			// Get arrays of checkout and thank you pages for all countries
			if ( is_array( $checkout_settings ) ) {
				foreach ( $checkout_settings as $cs_key => $cs_value ) {
					if ( strpos( $cs_key, 'klarna_checkout_url_' ) !== false && '' != $cs_value ) {
						$clean_checkout_uri        = explode( '?', $cs_value );
						$clean_checkout_uri        = trailingslashit( $clean_checkout_uri[0] );
						$checkout_pages[ $cs_key ] = substr( $clean_checkout_uri, 0 - $length );
					}
					if ( strpos( $cs_key, 'klarna_checkout_thanks_url_' ) !== false && '' != $cs_value ) {
						$clean_thank_you_uri        = explode( '?', $cs_value );
						$clean_thank_you_uri        = trailingslashit( $clean_thank_you_uri[0] );
						$thank_you_pages[ $cs_key ] = substr( $clean_thank_you_uri, 0 - $length );
					}
				}
			}

			// Start session if on a KCO or KCO Thank You page and KCO enabled
			if ( $length > 1 ) {
				if ( in_array( $clean_req_uri, $checkout_pages ) || in_array( $clean_req_uri, $thank_you_pages ) || apply_filters( 'klarna_checkout_enqueuer', '' ) ) {
					wp_enqueue_script( 'jquery' );
					wp_enqueue_script(
						'wc-checkout', $frontend_script_path . 'checkout' . $suffix . '.js', array(
							'jquery',
							'woocommerce',
							'wc-country-select',
							'wc-address-i18n',
						)
					);
					wp_enqueue_script( 'klarna_checkout' );
					wp_enqueue_style( 'klarna_checkout' );
				}
			}
		}
	}

	/**
	 * Get authorized KCO Countries.
	 *
	 * @since  2.0
	 **/
	public function get_authorized_countries() {
		$checkout_settings          = get_option( 'woocommerce_klarna_checkout_settings' );
		$this->eid_se               = ( isset( $checkout_settings['eid_se'] ) ) ? $checkout_settings['eid_se'] : '';
		$this->eid_no               = ( isset( $checkout_settings['eid_no'] ) ) ? $checkout_settings['eid_no'] : '';
		$this->eid_fi               = ( isset( $checkout_settings['eid_fi'] ) ) ? $checkout_settings['eid_fi'] : '';
		$this->eid_de               = ( isset( $checkout_settings['eid_de'] ) ) ? $checkout_settings['eid_de'] : '';
		$this->eid_at               = ( isset( $checkout_settings['eid_at'] ) ) ? $checkout_settings['eid_at'] : '';
		$this->eid_uk               = ( isset( $checkout_settings['eid_uk'] ) ) ? $checkout_settings['eid_uk'] : '';
		$this->eid_us               = ( isset( $checkout_settings['eid_us'] ) ) ? $checkout_settings['eid_us'] : '';
		$this->eid_dk               = ( isset( $checkout_settings['eid_dk'] ) ) ? $checkout_settings['eid_dk'] : '';
		$this->eid_nl               = ( isset( $checkout_settings['eid_nl'] ) ) ? $checkout_settings['eid_nl'] : '';
		$this->authorized_countries = array();
		if ( ! empty( $this->eid_se ) ) {
			$this->authorized_countries[] = 'SE';
		}
		if ( ! empty( $this->eid_no ) ) {
			$this->authorized_countries[] = 'NO';
		}
		if ( ! empty( $this->eid_fi ) ) {
			$this->authorized_countries[] = 'FI';
		}
		if ( ! empty( $this->eid_de ) ) {
			$this->authorized_countries[] = 'DE';
		}
		if ( ! empty( $this->eid_at ) ) {
			$this->authorized_countries[] = 'AT';
		}
		if ( ! empty( $this->eid_uk ) ) {
			$this->authorized_countries[] = 'GB';
		}
		if ( ! empty( $this->eid_us ) ) {
			$this->authorized_countries[] = 'US';
		}
		if ( ! empty( $this->eid_dk ) ) {
			$this->authorized_countries[] = 'DK';
		}
		if ( ! empty( $this->eid_nl ) ) {
			$this->authorized_countries[] = 'NL';
		}

		return apply_filters( 'klarna_authorized_countries', $this->authorized_countries );
	}

	/**
	 * Determines which version of Klarna API should be used
	 *
	 * @todo remove or move this function to a separate class. This function exist in the WC_Gateway_Klarna_Checkout class as well.
	 * We needed to move it here because the is_rest function in the WC_Gateway_Klarna_Checkout class was probably called after the klarna_checkout_enqueuer function in this class.
	 * This caused is_rest to be false on first pageload of the checkout even if the Klarna country was UK or US.
	 */
	function is_rest() {
		$this->klarna_country = WC()->session->get( 'klarna_country' );
		if ( in_array(
			strtoupper( $this->klarna_country ), apply_filters(
				'klarna_is_rest_countries', array(
					'US',
					'DK',
					'GB',
					'NL',
				)
			)
		) ) {
			// Set it in session as well, to be used in Shortcodes class
			WC()->session->set( 'klarna_is_rest', true );

			return true;
		}
		// Set it in session as well, to be used in Shortcodes class
		WC()->session->set( 'klarna_is_rest', false );

		return false;
	}

	/**
	 * Change the template for address_update callback
	 *
	 * Can't use WC_API here because we need output on the page.
	 * Checks if KCO shortcode is used and query parameter exists.
	 *
	 * Output JSON and die()
	 *
	 * @since  2.0
	 **/
	function address_update_listener() {
		global $post;
		// Check if page has Klarna Checkout shortcode in it and address_update query parameter
		if ( isset( $post ) && has_shortcode( $post->post_content, 'woocommerce_klarna_checkout' ) && isset( $_GET['address_update'] ) && 'yes' == $_GET['address_update'] ) {
			// Read the post body
			$post_body = file_get_contents( 'php://input' );
			// Convert post body into native object
			$data     = json_decode( $post_body, true );
			$order_id = $_GET['sid'];
			// Capture address from Klarna
			$order            = wc_get_order( $order_id );
			$billing_address  = array(
				'country'    => strtoupper( $data['billing_address']['country'] ),
				'first_name' => $data['billing_address']['given_name'],
				'last_name'  => $data['billing_address']['family_name'],
				'address_1'  => $data['billing_address']['street_address'],
				'postcode'   => $data['billing_address']['postal_code'],
				'city'       => $data['billing_address']['city'],
				'state'      => $data['billing_address']['region'],
				'email'      => $data['billing_address']['email'],
				'phone'      => $data['billing_address']['phone'],
			);
			$shipping_address = array(
				'country'    => strtoupper( $data['shipping_address']['country'] ),
				'first_name' => $data['shipping_address']['given_name'],
				'last_name'  => $data['shipping_address']['family_name'],
				'address_1'  => $data['shipping_address']['street_address'],
				'postcode'   => $data['shipping_address']['postal_code'],
				'city'       => $data['shipping_address']['city'],
				'state'      => $data['shipping_address']['region'],
				'email'      => $data['shipping_address']['email'],
				'phone'      => $data['shipping_address']['phone'],
			);

			if ( isset( $data['billing_address']['street_address2'] ) ) {
				$billing_address['address_2'] = $data['billing_address']['street_address2'];
			}

			if ( isset( $data['shipping_address']['street_address2'] ) ) {
				$billing_address['address_2'] = $data['shipping_address']['street_address2'];
			}

			$order->set_address( $billing_address, 'billing' );
			$order->set_address( apply_filters( 'kco_set_shipping_address', $shipping_address, $order, $data ), 'shipping' );
			$order->calculate_taxes();
			$sales_tax = round( ( $order->get_cart_tax() + $order->get_shipping_tax() ) * 100 );
			if ( 'us' == strtolower( $data['billing_address']['country'] ) ) {
				/**
				 * Update order total by removing old tax value and then adding the
				 * new one and set new order_tax_amount to $sales_tax value.
				 */
				$data['order_amount']     = $data['order_amount'] - $data['order_tax_amount'];
				$data['order_amount']     = $data['order_amount'] + $sales_tax;
				$data['order_tax_amount'] = $sales_tax;
				/**
				 * Loop through $data['order_lines'], then create new array only with
				 * elements where 'type' is not equal to 'sales_tax'. Then add new
				 * sales_tax element to this new array, json_encode the array and send
				 * it back to Klarna.
				 */
				foreach ( $data['order_lines'] as $order_line_key => $order_line ) {
					if ( 'sales_tax' == $order_line['type'] ) {
						unset( $data['order_lines'][ $order_line_key ] );
					}
				}
				// Add sales tax line item
				$data['order_lines'][] = array(
					'type'                  => 'sales_tax',
					'reference'             => __( 'Sales Tax', 'woocommerce-gateway-klarna' ),
					'name'                  => __( 'Sales Tax', 'woocommerce-gateway-klarna' ),
					'quantity'              => 1,
					'unit_price'            => $sales_tax,
					'tax_rate'              => 0,
					'total_amount'          => $sales_tax,
					'total_discount_amount' => 0,
					'total_tax_amount'      => 0,
				);
			}
			// Remove array indexing for order lines
			$data['order_lines'] = array_values( $data['order_lines'] );
			$response            = json_encode( $data );
			echo $response;
			die();
		}
	}

} // End class WC_Gateway_Klarna_Checkout_Extra
$wc_klarna_checkout_extra = new WC_Gateway_Klarna_Checkout_Extra();
