<?php
/**
 * Klarna invoice class
 *
 * @link http://www.woothemes.com/products/klarna/
 * @since 1.0.0
 *
 * @package WC_Gateway_Klarna
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class for Klarna Part Payment.
 */
class WC_Gateway_Klarna_Invoice extends WC_Gateway_Klarna {

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		global $woocommerce;

		parent::__construct();

		$this->id                 = 'klarna_invoice';
		$this->method_title       = __( 'Klarna Invoice', 'woocommerce-gateway-klarna' );
		$this->method_description = sprintf( __( 'With Klarna your customers can pay by invoice. Klarna works by adding extra personal information fields and then sending the details to Klarna for verification. Documentation <a href="%s" target="_blank">can be found here</a>.', 'woocommerce-gateway-klarna' ), 'https://docs.woothemes.com/document/klarna/' );
		$this->has_fields         = true;
		$this->order_button_text  = apply_filters( 'klarna_order_button_text', __( 'Place order', 'woocommerce' ) );
		$this->pclass_type        = array( 2 );

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables
		include( KLARNA_DIR . 'includes/variables-invoice.php' );

		// Load shortcodes. 
		// This is used so that the merchant easily can modify the displayed monthly 
		// cost text (on single product and shop page) via the settings page.
		include_once( KLARNA_DIR . 'classes/class-klarna-shortcodes.php' );

		// Klarna PClasses handling. 
		include_once( KLARNA_DIR . 'classes/class-klarna-pclasses.php' );

		// Helper class
		include_once( KLARNA_DIR . 'classes/class-klarna-helper.php' );
		$this->klarna_helper = new WC_Gateway_Klarna_Helper( $this );

		// Test mode or Live mode		
		if ( $this->testmode == 'yes' ) {
			// Disable SSL if in testmode
			$this->klarna_ssl  = 'false';
			$this->klarna_mode = Klarna::BETA;
		} else {
			// Set SSL if used in webshop
			if ( is_ssl() ) {
				$this->klarna_ssl = 'true';
			} else {
				$this->klarna_ssl = 'false';
			}
			$this->klarna_mode = Klarna::LIVE;
		}

		// Apply filters to Country and language
		$this->klarna_invoice_info = apply_filters( 'klarna_invoice_info', '' );
		$this->icon                = apply_filters( 'klarna_invoice_icon', $this->klarna_helper->get_account_icon() );
		$this->icon_basic          = apply_filters( 'klarna_basic_icon', '' );

		// Apply filters to Klarna warning banners (NL only)
		$klarna_wb = $this->get_klarna_wb();

		$this->klarna_wb_img_checkout       = apply_filters( 'klarna_wb_img_checkout', $klarna_wb['img_checkout'] );
		$this->klarna_wb_img_single_product = apply_filters( 'klarna_wb_img_single_product', $klarna_wb['img_single_product'] );
		$this->klarna_wb_img_product_list   = apply_filters( 'klarna_wb_img_product_list', $klarna_wb['img_product_list'] );

		// Refunds support
		$this->supports = array(
			'products',
			'refunds'
		);

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );
		add_action( 'woocommerce_receipt_klarna_invoice', array( $this, 'receipt_page' ) );
		add_action( 'wp_print_footer_scripts', array( $this, 'footer_scripts' ) );

		// Check Klarna pending order
		add_action( 'check_klarna_pending', array( $this, 'check_klarna_pending_callback' ) );

		// Add Klarna shipping info to order confirmation page and email
		add_filter( 'woocommerce_thankyou_order_received_text', array(
			$this,
			'output_klarna_details_confirmation'
		), 20, 2 );
		// add_action( 'woocommerce_email_after_order_table', array( $this, 'output_klarna_details_confirmation_email' ), 10, 3 );

		add_action(
			'update_option_woocommerce_' . $this->id . '_settings',
			array( $this, 'flush_pclasses_on_settings_save'	), 10, 2
		);

	}

	function flush_pclasses_on_settings_save( $oldvalue, $newvalue ) {
		if ( $oldvalue['testmode'] != $newvalue['testmode'] ) {
			$countries = array(
				'SE',
				'NO',
				'FI',
				'DK',
				'DE',
				'NL',
				'AT'
			);

			foreach ( $countries as $country ) {
				delete_transient( 'klarna_pclasses_' . $country );
			}
		}
	}


	/**
	 * Add Klarna's shipping details to order confirmation page.
	 *
	 * @param  $text  string Default order confirmation text
	 * @param  $order object WC_Order object
	 * @return $text  string Updated order confirmation text
	 *
	 * @since  2.0.0
	 */
	public function output_klarna_details_confirmation( $text = false, $order ) {
		if ( $this->id == $order->payment_method ) {
			return $text . $this->get_klarna_shipping_info( $order->id );
		} else {
			return $text;
		}
	}


	/**
	 * Add Klarna's shipping details to confirmation email.
	 *
	 * @since  2.0.0
	 */
	public function output_klarna_details_confirmation_email( $order, $sent_to_admin, $plain_text ) {
		if ( $this->id == $order->payment_method ) {
			echo $this->get_klarna_shipping_info( $order->id );
		}
	}


	/**
	 * Get Klarna's shipping info.
	 *
	 * @since  2.0.0
	 */
	public function get_klarna_shipping_info( $orderid ) {
		$klarna_country = get_post_meta( $orderid, '_billing_country', true );

		switch ( $klarna_country ) {
			case 'SE' :
				$klarna_locale = 'sv_se';
				break;
			case 'NO' :
				$klarna_locale = 'nb_no';
				break;
			case 'DE' :
				$klarna_locale = 'de_de';
				break;
			case 'FI' :
				$klarna_locale = 'fi_fi';
				break;
			default :
				$klarna_locale = '';
		}

		// Only do this for SE, NO, DE and FI
		$allowed_locales = array(
			'sv_se',
			'nb_no',
			'de_de',
			'fi_fi'
		);
		if ( in_array( $klarna_locale, $allowed_locales ) ) {
			$klarna_info = wp_remote_get( 'http://cdn.klarna.com/1.0/shared/content/policy/packing/' . $this->klarna_helper->get_eid() . '/' . $klarna_locale . '/minimal' );

			if ( is_array( $klarna_info ) ) {
				if ( 200 == $klarna_info['response']['code'] ) {
					$klarna_message       = json_decode( $klarna_info['body'] );
					$klarna_shipping_info = wpautop( $klarna_message->template->text );

					return $klarna_shipping_info;
				}
			}
		}

		return '';
	}

	//
	//

	/**
	 * Update order in Klarna system
	 *
	 * @since 1.0.0
	 * @todo  Decide what to do with this
	 */
	function update_klarna_order( $orderid, $items ) {

		$order = wc_get_order( $orderid );

		$billing_address = array(
			'first_name' => $order->billing_first_name,
			'last_name'  => $order->billing_last_name,
			'company'    => $order->billing_company,
			'address_1'  => $order->billing_address_1,
			'address_2'  => $order->billing_address_2,
			'city'       => $order->billing_city,
			'state'      => $order->billing_state,
			'postcode'   => $order->billing_postcode,
			'country'    => $order->billing_country
		);

		$shipping_address = array(
			'first_name' => $order->shipping_first_name,
			'last_name'  => $order->shipping_last_name,
			'company'    => $order->shipping_company,
			'address_1'  => $order->shipping_address_1,
			'address_2'  => $order->shipping_address_2,
			'city'       => $order->shipping_city,
			'state'      => $order->shipping_state,
			'postcode'   => $order->shipping_postcode,
			'country'    => $order->shipping_country
		);

		// Klarna reservation number and billing country must be set
		if ( get_post_meta( $orderid, '_klarna_order_reservation', true ) && get_post_meta( $orderid, '_billing_country', true ) ) {

			// Check if this order hasn't been activated already
			if ( ! get_post_meta( $orderid, '_klarna_order_activated', true ) ) {

				$rno     = get_post_meta( $orderid, '_klarna_order_reservation', true );
				$country = get_post_meta( $orderid, '_billing_country', true );

				$order = wc_get_order( $orderid );

				$klarna = new Klarna();

				/**
				 * Setup Klarna configuration
				 */
				$this->configure_klarna( $klarna, $country );

				if ( sizeof( $order->get_items() ) > 0 ) {
					foreach ( $order->get_items() as $item ) {
						$_product = $order->get_product_from_item( $item );
						if ( $_product->exists() && $item['qty'] ) {

							// We manually calculate the tax percentage here
							if ( $order->get_line_tax( $item ) !== 0 ) {
								// Calculate tax percentage
								$item_tax_percentage = @number_format( ( $order->get_line_tax( $item ) / $order->get_line_total( $item, false ) ) * 100, 2, '.', '' );
							} else {
								$item_tax_percentage = 0.00;
							}

							// apply_filters to item price so we can filter this if needed
							$klarna_item_price_including_tax = $order->get_item_total( $item, true );
							$item_price                      = apply_filters( 'klarna_item_price_including_tax', $klarna_item_price_including_tax );

							// Get SKU or product id
							$reference = '';
							if ( $_product->get_sku() ) {
								$reference = $_product->get_sku();
							} elseif ( $_product->variation_id ) {
								$reference = $_product->variation_id;
							} else {
								$reference = $_product->id;
							}

							$klarna->addArticle( $qty = $item['qty'],                  // Quantity
								$artNo = strval( $reference ),          // Article number
								$title = utf8_decode( $item['name'] ),   // Article name/title
								$price = $item_price,                   // Price including tax
								$vat = round( $item_tax_percentage ), // Tax
								$discount = 0,                             // Discount is applied later
								$flags = KlarnaFlags::INC_VAT           // Price is including VAT.
							);

						}
					}
				}

				try {

					$result = $klarna->update( $rno );

					if ( $result ) {

						$order->add_order_note( __( 'Klarna order updated.', 'woocommerce-gateway-klarna' ) );

					}

				} catch ( Exception $e ) {

					$order->add_order_note( sprintf( __( 'Klarna order update failed. Error code %s. Error message %s', 'woocommerce-gateway-klarna' ), $e->getCode(), utf8_encode( $e->getMessage() ) ) );

				}

			}

		}

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
		if ( get_post_meta( $order->id, '_klarna_invoice_number', true ) ) {
			return true;
		}

		return false;
	}


	/**
	 * Refund order in Klarna system
	 *
	 * @param  integer $orderid
	 * @param  integer $amount
	 * @param  string $reason
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
					$this->log->add( 'klarna', 'Refund Failed: No Klarna invoice ID.' );
				}
				$order->add_order_note( __( 'This order cannot be refunded. Please make sure it is activated.', 'woocommerce-gateway-klarna' ) );

				return false;
			}

			$country = get_post_meta( $orderid, '_billing_country', true );

			$klarna = new Klarna();
			$this->configure_klarna( $klarna, $country );
			$invNo = get_post_meta( $order->id, '_klarna_invoice_number', true );

			$klarna_order = new WC_Gateway_Klarna_Order( $order, $klarna );
			$refund_order = $klarna_order->refund_order( $amount, $reason = '', $invNo );

			if ( $refund_order ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Initialise Gateway Settings Form Fields.
	 *
	 * @since 1.0.0
	 */
	function init_form_fields() {
		$this->form_fields = include( KLARNA_DIR . 'includes/settings-invoice.php' );
	}


	/**
	 * Admin Panel Options.
	 *
	 * @since 1.0.0
	 */
	public function admin_options() { ?>
		<h3><?php echo ( ! empty( $this->method_title ) ) ? $this->method_title : __( 'Settings', 'woocommerce-gateway-klarna' ); ?></h3>
		<?php echo ( ! empty( $this->method_description ) ) ? wpautop( $this->method_description ) : ''; ?>
		<table class="form-table">
			<?php $this->generate_settings_html(); // Generate the HTML For the settings form. ?>
		</table>
	<?php }


	/**
	 * Gets Klarna warning banner images, used for NL only.
	 *
	 * @since  1.0.0
	 *
	 * @return $klarna_wb array
	 */
	function get_klarna_wb() {
		$klarna_wb = array();

		// Klarna warning banner - used for NL only
		$klarna_wb['img_checkout']       = apply_filters( 'klarna_nl_banner', 'http://www.afm.nl/~/media/Images/wetten-regels/kredietwaarschuwing/balk_afm6-jpg.ashx', 'checkout' );
		$klarna_wb['img_single_product'] = apply_filters( 'klarna_nl_banner', 'http://www.afm.nl/~/media/Images/wetten-regels/kredietwaarschuwing/balk_afm6-jpg.ashx', 'single_product' );
		$klarna_wb['img_product_list']   = apply_filters( 'klarna_nl_banner', 'http://www.afm.nl/~/media/Images/wetten-regels/kredietwaarschuwing/balk_afm6-jpg.ashx', 'product_list' );

		return $klarna_wb;
	}


	/**
	 * Check if this gateway is enabled and available in user's country.
	 *
	 * @since 1.0.0
	 */
	function is_available() {
		if ( ! $this->check_enabled() ) {
			return false;
		}

		if ( ! is_admin() ) {
			if ( ! $this->check_required_fields() ) {
				return false;
			}
			// if ( ! $this->check_pclasses() ) return false;
			if ( ! $this->check_cart_total() ) {
				return false;
			}
			if ( ! $this->check_lower_threshold() ) {
				return false;
			}
			if ( ! $this->check_upper_threshold() ) {
				return false;
			}
			if ( ! $this->check_customer_country() ) {
				return false;
			}
			if ( ! $this->check_customer_currency() ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Checks if payment method is enabled.
	 *
	 * @since  2.0
	 **/
	function check_enabled() {
		if ( 'yes' != $this->enabled ) {
			return false;
		}

		return true;
	}


	/**
	 * Checks if required fields are set.
	 *
	 * @since  2.0
	 **/
	function check_required_fields() {
		// Required fields check
		if ( ! $this->klarna_helper->get_eid() || ! $this->klarna_helper->get_secret() ) {
			return false;
		}

		return true;
	}


	/**
	 * Checks if there are PClasses.
	 *
	 * @since  2.0
	 **/
	function check_pclasses() {
		$country = $this->klarna_helper->get_klarna_country();
		$klarna  = new Klarna();
		$this->configure_klarna( $klarna, $country );

		$klarna_pclasses = new WC_Gateway_Klarna_PClasses( $klarna, false, $country );
		$pclasses        = $klarna_pclasses->fetch_pclasses();

		if ( empty( $pclasses ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Checks if there is cart total.
	 *
	 * @since  2.0
	 **/
	function check_cart_total() {
		global $woocommerce;

		if ( ! isset( $woocommerce->cart->total ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Checks if lower threshold is OK.
	 *
	 * @since  2.0
	 **/
	function check_lower_threshold() {
		global $woocommerce;

		// Cart totals check - Lower threshold
		if ( $this->lower_threshold !== '' && $woocommerce->cart->total > 0 ) {
			if ( $woocommerce->cart->total < $this->lower_threshold ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Checks if upper threshold is OK.
	 *
	 * @since  2.0
	 **/
	function check_upper_threshold() {
		global $woocommerce;

		// Cart totals check - Upper threshold
		if ( $this->upper_threshold !== '' && $woocommerce->cart->total > 0 ) {
			if ( $woocommerce->cart->total > $this->upper_threshold ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Checks if selling to customer's country is allowed.
	 *
	 * @since  2.0
	 **/
	function check_customer_country() {
		global $woocommerce;

		// Only activate the payment gateway if the customers country is the same as 
		// the filtered shop country ($this->klarna_country)
		if ( $woocommerce->customer->get_country() == true && ! in_array( $woocommerce->customer->get_country(), $this->authorized_countries ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Checks if customer's currency is allowed.
	 *
	 * @since  2.0
	 **/
	function check_customer_currency() {
		global $woocommerce;

		// Currency check
		$currency_for_country = $this->klarna_helper->get_currency_for_country( $woocommerce->customer->get_country() );

		if ( ! empty( $currency_for_country ) && $currency_for_country !== $this->selected_currency ) {
			return false;
		}

		return true;
	}


	/**
	 * Set up Klarna configuration.
	 *
	 * @since  2.0
	 **/
	function configure_klarna( $klarna, $country ) {
		$klarna->config( $this->klarna_helper->get_eid( $country ),  // EID
			$this->klarna_helper->get_secret( $country ),            // Secret
			$country,                                                // Country
			$this->klarna_helper->get_klarna_language( $country ),   // Language
			$this->selected_currency,                                // Currency
			$this->klarna_mode,                                      // Live or test
			$pcStorage = 'jsondb',                                   // PClass storage
			$pcURI = 'klarna_pclasses_' . $country                   // PClass storage URI path
		);
	}


	/**
	 * Payment form on checkout page
	 *
	 * @since 1.0.0
	 */
	function payment_fields() {

		global $woocommerce;

		if ( 'yes' == $this->testmode ) { ?>
			<p><?php _e( 'TEST MODE ENABLED', 'woocommerce-gateway-klarna' ); ?></p>
		<?php }

		$klarna = new Klarna();

		/**
		 * Setup Klarna configuration
		 */
		$country = $this->klarna_helper->get_klarna_country();
		$this->configure_klarna( $klarna, $country );

		Klarna::$xmlrpcDebug = false;
		Klarna::$debug       = false;

		// apply_filters to cart total so we can filter this if needed
		$klarna_cart_total = $woocommerce->cart->total;
		$sum               = apply_filters( 'klarna_cart_total', $klarna_cart_total ); // Cart total.
		$flag              = KlarnaFlags::CHECKOUT_PAGE; // or KlarnaFlags::PRODUCT_PAGE, if you want to do it for one item.

		// Description
		if ( $this->description ) {
			$klarna_description = $this->description;
			// apply_filters to the description so we can filter this if needed
			echo '<p>' . apply_filters( 'klarna_invoice_description', $klarna_description ) . '</p>';
		}

		// For countries other than NO do the old thing
		$pclass_type                  = $this->pclass_type;
		$klarna_select_pclass_element = $this->id . '_pclass';
		$klarna_dob_element           = $this->id . '_pno';
		include( KLARNA_DIR . 'views/public/payment-fields-invoice.php' );

	}


	/**
	 * Collect DoB, based on country.
	 *
	 * @since  2.0
	 **/
	function collect_dob() {

		// Collect the dob different depending on country
		if ( isset( $_POST['billing_country'] ) && ( $_POST['billing_country'] == 'NL' || $_POST['billing_country'] == 'DE' || $_POST['billing_country'] == 'AT' ) ) {
			$klarna_pno_day   = isset( $_POST['klarna_invoice_date_of_birth_day'] ) ? woocommerce_clean( $_POST['klarna_invoice_date_of_birth_day'] ) : '';
			$klarna_pno_month = isset( $_POST['klarna_invoice_date_of_birth_month'] ) ? woocommerce_clean( $_POST['klarna_invoice_date_of_birth_month'] ) : '';
			$klarna_pno_year  = isset( $_POST['klarna_invoice_date_of_birth_year'] ) ? woocommerce_clean( $_POST['klarna_invoice_date_of_birth_year'] ) : '';

			$klarna_pno = $klarna_pno_day . $klarna_pno_month . $klarna_pno_year;
		} else {
			$klarna_pno = isset( $_POST['klarna_invoice_pno'] ) ? woocommerce_clean( $_POST['klarna_invoice_pno'] ) : '';
		}

		return $klarna_pno;

	}


	/**
	 * Process the payment and return the result
	 *
	 * @since 1.0.0
	 **/
	function process_payment( $order_id ) {
		global $woocommerce;
		$klarna_gender = null;

		$order = wc_get_order( $order_id );

		// Get values from klarna form on checkout page

		// Collect the DoB
		$klarna_pno = $this->collect_dob();

		$klarna_pclass           = isset( $_POST['klarna_invoice_pclass'] ) ? woocommerce_clean( $_POST['klarna_invoice_pclass'] ) : '';
		$klarna_gender           = isset( $_POST['klarna_invoice_gender'] ) ? woocommerce_clean( $_POST['klarna_invoice_gender'] ) : '';
		$klarna_de_consent_terms = isset( $_POST['klarna_de_consent_terms'] ) ? woocommerce_clean( $_POST['klarna_de_consent_terms'] ) : '';

		// Split address into House number and House extension for NL & DE customers
		$klarna_billing  = array();
		$klarna_shipping = array();
		if ( isset( $_POST['billing_country'] ) && ( $_POST['billing_country'] == 'NL' || $_POST['billing_country'] == 'DE' ) ) {
			require_once( KLARNA_DIR . 'split-address.php' );

			// Set up billing address array
			$klarna_billing_address            = $order->billing_address_1;
			$splitted_address                  = splitAddress( $klarna_billing_address );
			$klarna_billing['address']         = $splitted_address[0];
			$klarna_billing['house_number']    = $splitted_address[1];
			$klarna_billing['house_extension'] = $splitted_address[2];

			// Set up shipping address array
			$klarna_shipping_address            = $order->shipping_address_1;
			$splitted_address                   = splitAddress( $klarna_shipping_address );
			$klarna_shipping['address']         = $splitted_address[0];
			$klarna_shipping['house_number']    = $splitted_address[1];
			$klarna_shipping['house_extension'] = $splitted_address[2];
		} else {
			$klarna_billing['address']         = $order->billing_address_1;
			$klarna_billing['house_number']    = '';
			$klarna_billing['house_extension'] = '';

			$klarna_shipping['address']         = $order->shipping_address_1;
			$klarna_shipping['house_number']    = '';
			$klarna_shipping['house_extension'] = '';
		}

		$klarna = new Klarna();

		/**
		 * Setup Klarna configuration
		 */
		$country = $this->klarna_helper->get_klarna_country();
		$this->configure_klarna( $klarna, $country );

		$klarna_order = new WC_Gateway_Klarna_Order( $order, $klarna );
		$klarna_order->prepare_order( $klarna_billing, $klarna_shipping, $this->ship_to_billing_address );

		// Set store specific information so you can e.g. search and associate invoices with order numbers.
		$klarna->setEstoreInfo( $orderid1 = ltrim( $order->get_order_number(), '#' ), $orderid2 = $order_id, $user = '' // Username, email or identifier for the user?
		);


		try {
			// Transmit all the specified data, from the steps above, to Klarna.
			$result = $klarna->reserveAmount( $klarna_pno,            // Date of birth.
				$klarna_gender,            // Gender.
				- 1,                    // Automatically calculate and reserve the cart total amount
				KlarnaFlags::NO_FLAG,    // No specific behaviour like RETURN_OCR or TEST_MODE.
				$klarna_pclass            // Get the pclass object that the customer has choosen.
			);

			// Prepare redirect url
			$redirect_url = $order->get_checkout_order_received_url();

			// Store the selected pclass in the order
			update_post_meta( $order_id, '_klarna_order_pclass', $klarna_pclass );

			// Retreive response
			$invno = $result[0];

			switch ( $result[1] ) {
				case KlarnaFlags::ACCEPTED :
					$order->add_order_note( __( 'Klarna payment completed. Klarna Invoice number: ', 'woocommerce-gateway-klarna' ) . $invno );
					if ( $this->debug == 'yes' ) {
						$this->log->add( 'klarna', __( 'Klarna payment completed. Klarna Invoice number: ', 'woocommerce-gateway-klarna' ) . $invno );
					}
					update_post_meta( $order_id, '_klarna_order_reservation', $invno );
					update_post_meta( $order_id, '_transaction_id', $invno );
					$order->payment_complete(); // Payment complete
					$woocommerce->cart->empty_cart(); // Remove cart
					// Return thank you redirect
					return array(
						'result'   => 'success',
						'redirect' => $redirect_url
					);
					break;

				case KlarnaFlags::PENDING :
					update_post_meta( $order_id, '_klarna_order_reservation', $invno );
					wp_schedule_single_event( time() + 7200, 'check_klarna_pending', array( $order_id ) );
					$order->add_order_note( __( 'Order is PENDING APPROVAL by Klarna. Please visit Klarna Online for the latest status on this order. Klarna reservation number: ', 'woocommerce-gateway-klarna' ) . $invno );
					if ( $this->debug == 'yes' ) {
						$this->log->add( 'klarna', __( 'Order is PENDING APPROVAL by Klarna. Please visit Klarna Online for the latest status on this order. Klarna reservation number: ', 'woocommerce-gateway-klarna' ) . $invno );
					}
					$order->update_status( 'on-hold' ); // Change order status to On Hold
					$woocommerce->cart->empty_cart(); // Remove cart
					// Return thank you redirect
					return array(
						'result'   => 'success',
						'redirect' => $redirect_url
					);
					break;

				case KlarnaFlags::DENIED : // Order is denied, store it in a database.
					$order->add_order_note( __( 'Klarna payment denied.', 'woocommerce-gateway-klarna' ) );
					if ( $this->debug == 'yes' ) {
						$this->log->add( 'klarna', __( 'Klarna payment denied.', 'woocommerce-gateway-klarna' ) );
					}
					wc_add_notice( __( 'Klarna payment denied.', 'woocommerce-gateway-klarna' ), 'error' );

					return;
					break;

				default: // Unknown response, store it in a database.
					$order->add_order_note( __( 'Unknown response from Klarna.', 'woocommerce-gateway-klarna' ) );
					if ( $this->debug == 'yes' ) {
						$this->log->add( 'klarna', __( 'Unknown response from Klarna.', 'woocommerce-gateway-klarna' ) );
					}
					wc_add_notice( __( 'Unknown response from Klarna.', 'woocommerce-gateway-klarna' ), 'error' );

					return;
					break;
			}

		} catch ( Exception $e ) {
			// The purchase was denied or something went wrong, print the message:
			wc_add_notice( sprintf( __( '%s (Error code: %s)', 'woocommerce-gateway-klarna' ), utf8_encode( $e->getMessage() ), $e->getCode() ), 'error' );
			if ( $this->debug == 'yes' ) {
				$this->log->add( 'klarna', sprintf( __( '%s (Error code: %s)', 'woocommerce-gateway-klarna' ), utf8_encode( $e->getMessage() ), $e->getCode() ) );
			}

			return false;
		}

	}


	/**
	 * Runs scheduled action to check Klarna pending order.
	 *
	 * @since 1.0.0
	 **/
	function check_klarna_pending_callback( $order_id ) {
		/**
		 * Setup Klarna configuration
		 */
		$klarna  = new Klarna();
		$country = get_post_meta( $order_id, '_billing_country', true );
		$rno     = get_post_meta( $order_id, '_klarna_order_reservation', true );
		$this->configure_klarna( $klarna, $country );
		$result = $klarna->checkOrderStatus( $rno );
		$order  = wc_get_order( $order_id );

		if ( $result == KlarnaFlags::ACCEPTED ) {
			// Status changed, you can now activate your invoice/reservation.
			$order->add_order_note( __( 'Klarna payment completed. You can now activate Klarna order.', 'woocommerce-gateway-klarna' ) );
			$order->payment_complete();
		} elseif ( $result == KlarnaFlags::DENIED ) {
			// Status changed, it is now denied, proceed accordingly.
			$order->add_order_note( __( 'Klarna payment denied.', 'woocommerce-gateway-klarna' ) );
			$order->update_status( 'cancelled' );
		} else {
			// Order is still pending, try again in two hours.
			wp_schedule_single_event( time() + 7200, 'check_klarna_pending', array( $order_id ) );
		}
	}


	/**
	 * Adds note in receipt page.
	 *
	 * @since 1.0.0
	 **/
	function receipt_page( $order ) {
		echo '<p>' . __( 'Thank you for your order.', 'woocommerce-gateway-klarna' ) . '</p>';
	}


	/**
	 * Disable the radio button for the Klarna Invoice payment method if Company name
	 * is entered and the customer is from Germany or Austria.
	 *
	 * @since 1.0.0
	 * @todo  move to separate JS file?
	 **/
	function footer_scripts() {
		global $woocommerce;
		if ( is_checkout() && $this->enabled == "yes" && ! is_klarna_checkout() ) {
			?>
			<script type="text/javascript">
				//<![CDATA[
				jQuery(document).ready(function ($) {
					$(document.body).on('change', 'input[name="payment_method"]', function () {
						$('body').trigger('update_checkout');
					});
				});
				//]]>
			</script>

			<script type="text/javascript">
				//<![CDATA[
				jQuery(document).ajaxComplete(function () {
					if (jQuery.trim(jQuery('input[name=billing_company]').val()) && (jQuery("#billing_country").val() == 'DE' || jQuery("#billing_country").val() == 'AT')) {
						jQuery('#payment_method_klarna_invoice').prop('disabled', true);
					} else jQuery('#payment_method_klarna_invoice').prop('disabled', false);
				});

				jQuery(document).ready(function ($) {
					$(window).load(function () {
						$('input[name=billing_company]').keyup(function () {
							if ($.trim(this.value).length && ($("#billing_country").val() == 'DE' || $("#billing_country").val() == 'AT')) {
								$('#payment_method_klarna_invoice').prop('disabled', true);
							} else $('#payment_method_klarna_invoice').prop('disabled', false);
						});
					});
				});


				// Move PNO field and get address if SE
				jQuery(document).ajaxComplete(function (event, xhr, settings) {
					settings_url = settings.url;

					// Check if correct AJAX function
					if (settings_url.indexOf('?wc-ajax=update_order_review') > -1) {
						// Check if Klarna Invoice and SE
						if (jQuery('input[name="payment_method"]:checked').val() == 'klarna_invoice' &&
							jQuery('#billing_country').val() == 'SE') {

							jQuery('.woocommerce-billing-fields #klarna-invoice-get-address').remove();

							/* 
							 jQuery('#order_review').on('change', function() {
							 if ( 'undefined' !== typeof pno_getadress ) {
							 jQuery('input#klarna_invoice_pno').val(pno_getadress);
							 }
							 });
							 */
							jQuery('#order_review #klarna-invoice-get-address').show().prependTo(jQuery('.woocommerce-billing-fields'));
							jQuery( document.body ).trigger( 'moved_get_address_form' );
						} else {

							// if (jQuery('.woocommerce-billing-fields #klarna-invoice-get-address').length) {
							jQuery('.woocommerce-billing-fields #klarna-invoice-get-address').hide().appendTo(jQuery('li.payment_method_klarna_invoice div.payment_method_klarna_invoice'));
							// }
						}
					}
				});
				//]]>
			</script>
		<?php }
	}


	/**
	 * Helper function, checks if payment method is enabled.
	 *
	 * @since 1.0.0
	 **/
	function get_enabled() {
		return $this->enabled;
	}


	/**
	 * Helper function, gets Klarna shop country.
	 *
	 * @since 1.0.0
	 **/
	function get_klarna_shop_country() {
		return $this->shop_country;
	}


	/**
	 * Helper function, gets invoice fee ID.
	 *
	 * @since 1.0.0
	 **/
	function get_invoice_fee_id() {
		return $this->invoice_fee_id;
	}


	/**
	 * Helper function, gets invoice fee name.
	 *
	 * @since 1.0.0
	 **/
	function get_invoice_fee_name() {
		if ( $this->invoice_fee_id > 0 ) {
			$product = wc_get_product( $this->invoice_fee_id );
			if ( $product ) {
				return $product->get_title();
			} else {
				return '';
			}
		} else {
			return '';
		}
	}


	/**
	 * Helper function, gets invoice fee price.
	 *
	 * @since 1.0.0
	 **/
	function get_invoice_fee_price() {
		if ( $this->invoice_fee_id > 0 ) {
			$product = wc_get_product( $this->invoice_fee_id );
			if ( $product ) {
				return $product->get_price();
			} else {
				return '';
			}
		} else {
			return '';
		}
	}

} // End class WC_Gateway_Klarna_invoice


/**
 * Class WC_Gateway_Klarna_Invoice_Extra
 * Extra class for functions that needs to be executed outside the payment gateway class.
 * Since version 1.5.4 (WooCommerce version 2.0)
 */
class WC_Gateway_Klarna_Invoice_Extra {

	public function __construct() {

		// Add Invoice fee via the new Fees API
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'calculate_fees' ) );

	}

	/**
	 * Calculate fees on checkout form.
	 */
	public function calculate_fees( $cart ) {
		global $woocommerce;
		$current_gateway = '';

		if ( is_checkout() || defined( 'WOOCOMMERCE_CHECKOUT' ) ) {

			$available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();

			// Need to make this check so invoice fee is not added for KCO orders when Invoice
			// is the default payment method in standard checkout page
			if ( null !== $woocommerce->session->get( 'chosen_payment_method' ) && 'klarna_checkout' == $woocommerce->session->get( 'chosen_payment_method' ) ) {
				return false;
			}

			if ( ! empty( $available_gateways ) ) {
				// Chosen Method
				if ( $woocommerce->session->get( 'chosen_payment_method' ) && isset( $available_gateways[ $woocommerce->session->get( 'chosen_payment_method' ) ] ) ) {
					$current_gateway = $available_gateways[ $woocommerce->session->get( 'chosen_payment_method' ) ];
				} elseif ( isset( $available_gateways[ get_option( 'woocommerce_default_gateway' ) ] ) ) {
					$current_gateway = $available_gateways[ get_option( 'woocommerce_default_gateway' ) ];
				} else {
					$current_gateway = current( $available_gateways );
				}
			}

			if ( is_object( $current_gateway ) ) {
				if ( 'klarna_invoice' === $current_gateway->id && $woocommerce->cart->subtotal > 0 ) {
					$this->add_fee_to_cart( $cart );
				}
			}
		}
	}

	/**
	 * Add the fee to the cart if Klarna is selected payment method and if a fee is used.
	 */
	public function add_fee_to_cart( $cart ) {
		$invo_settings        = get_option( 'woocommerce_klarna_invoice_settings' );
		$this->invoice_fee_id = $invo_settings['invoice_fee_id'];

		if ( $this->invoice_fee_id > 0 ) {
			$product = wc_get_product( $this->invoice_fee_id );

			if ( $product ) {
				// Is this a taxable product?
				if ( $product->is_taxable() ) {
					$product_tax = true;
				} else {
					$product_tax = false;
				}

				$cart->add_fee( $product->get_title(), $product->get_price_excluding_tax(), $product_tax, $product->get_tax_class() );
			}
		}
	}

}

$wc_klarna_invoice_extra = new WC_Gateway_Klarna_Invoice_Extra;