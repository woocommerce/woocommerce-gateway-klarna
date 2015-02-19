<?php

class WC_Gateway_Klarna_Campaign extends WC_Gateway_Klarna {
	
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		global $woocommerce;
		
		parent::__construct();
		
		$this->id = 'klarna_special_campaign';
		$this->method_title = __('Klarna Special Campaign', 'klarna');
		$this->has_fields = true;
		$this->order_button_text = apply_filters( 'klarna_order_button_text', __( 'Place order', 'woocommerce' ) );
		
		// Klarna warning banner - used for NL only
		$klarna_wb_img_checkout = '';
		$klarna_wb_img_checkout = 'http://www.afm.nl/~/media/Images/wetten-regels/kredietwaarschuwing/balk_afm1-jpg.ashx';
		$this->klarna_wb_img_checkout = apply_filters( 'klarna_wb_img_checkout', $klarna_wb_img_checkout );
		
		// Load the form fields.
		$this->init_form_fields();
		
		// Load the settings.
		$this->init_settings();

		// Define user set variables
		$this->enabled = 
			( isset( $this->settings['enabled'] ) ) ? $this->settings['enabled'] : '';
		$this->title = 
			( isset( $this->settings['title'] ) ) ? $this->settings['title'] : '';
		$this->description = 
			( isset( $this->settings['description'] ) ) ? $this->settings['description'] : '';
		$this->icon_url = 
			( isset( $this->settings['icon_url'] ) ) ? $this->settings['icon_url'] : '';
		
		$this->eid_se = 
			( isset( $this->settings['eid_se'] ) ) ? $this->settings['eid_se'] : '';
       	$this->secret_se = 
       		( isset( $this->settings['secret_se'] ) ) ? $this->settings['secret_se'] : '';
       	$this->terms_se = 
       		( isset( $this->settings['terms_se'] ) ) ? $this->settings['terms_se'] : '';

       	$this->eid_no = 
       		( isset( $this->settings['eid_no'] ) ) ? $this->settings['eid_no'] : '';
       	$this->secret_no = 
       		( isset( $this->settings['secret_no'] ) ) ? $this->settings['secret_no'] : '';
		$this->terms_no = 
			( isset( $this->settings['terms_no'] ) ) ? $this->settings['terms_no'] : '';
		
		$this->eid_fi = 
			( isset( $this->settings['eid_fi'] ) ) ? $this->settings['eid_fi'] : '';
       	$this->secret_fi = 
       		( isset( $this->settings['secret_fi'] ) ) ? $this->settings['secret_fi'] : '';
       	$this->terms_fi = 
       		( isset( $this->settings['terms_fi'] ) ) ? $this->settings['terms_fi'] : '';
       	
       	$this->eid_dk = 
       		( isset( $this->settings['eid_dk'] ) ) ? $this->settings['eid_dk'] : '';
       	$this->secret_dk = 
       		( isset( $this->settings['secret_dk'] ) ) ? $this->settings['secret_dk'] : '';
       	$this->terms_se = 
       		( isset( $this->settings['terms_se'] ) ) ? $this->settings['terms_se'] : '';
       	
       	$this->eid_de = 
       		( isset( $this->settings['eid_de'] ) ) ? $this->settings['eid_de'] : '';
       	$this->secret_de = 
       		( isset( $this->settings['secret_de'] ) ) ? $this->settings['secret_de'] : '';
       	$this->terms_se = 
       		( isset( $this->settings['terms_se'] ) ) ? $this->settings['terms_se'] : '';
       	$this->eid_nl = 
       		( isset( $this->settings['eid_nl'] ) ) ? $this->settings['eid_nl'] : '';
       	$this->secret_nl = 
       		( isset( $this->settings['secret_nl'] ) ) ? $this->settings['secret_nl'] : '';
       	$this->eid_at = 
       		( isset( $this->settings['eid_at'] ) ) ? $this->settings['eid_at'] : '';
       	$this->secret_at = 
       		( isset( $this->settings['secret_at'] ) ) ? $this->settings['secret_at'] : '';
       	
		$this->lower_threshold = 
			( isset( $this->settings['lower_threshold'] ) ) ? $this->settings['lower_threshold'] : '';
		$this->upper_threshold = 
			( isset( $this->settings['upper_threshold'] ) ) ? $this->settings['upper_threshold'] : '';
		$this->testmode = 
			( isset( $this->settings['testmode'] ) ) ? $this->settings['testmode'] : '';
		$this->de_consent_terms = 
			( isset( $this->settings['de_consent_terms'] ) ) ? $this->settings['de_consent_terms'] : '';
		$this->ship_to_billing_address = 
			( isset( $this->settings['ship_to_billing_address'] ) ) ? $this->settings['ship_to_billing_address'] : '';
		
		// Icon for payment method
		if ( $this->icon_url == '' ) {
			$this->icon = apply_filters( 'klarna_campaign_icon', '' );
		} else {
			$this->icon = apply_filters( 'klarna_campaign_icon', $this->icon_url );
		}

		// authorized countries
		$this->authorized_countries	= array();
		if ( ! empty( $this->eid_se ) ) {
			$this->authorized_countries[] = 'SE';
		}
		if ( ! empty( $this->eid_no ) ) {
			$this->authorized_countries[] = 'NO';
		}
		if ( ! empty( $this->eid_fi ) ) {
			$this->authorized_countries[] = 'FI';
		}
		if ( ! empty( $this->eid_dk ) ) {
			$this->authorized_countries[] = 'DK';
		}
		if ( ! empty( $this->eid_de ) ) {
			$this->authorized_countries[] = 'DE';
		}
		if ( ! empty( $this->eid_nl ) ) {
			$this->authorized_countries[] = 'NL';
		}

		// Define Klarna object
		require_once( KLARNA_LIB . 'Klarna.php' );
		$this->klarna = new Klarna();

		// Test mode or Live mode		
		if ( $this->testmode == 'yes' ) {
			// Disable SSL if in testmode
			$this->klarna_ssl = 'false';
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

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_klarna_campaign', array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_checkout_process', array( $this, 'klarna_campaign_checkout_field_process' ) );
		
	}
	
	
	/**
	 * Admin Panel Options.
	 *
	 * @since 1.0.0
	 */
	public function admin_options() { ?>

		<h3><?php _e('Klarna Special Campaign', 'klarna'); ?></h3>
		<p><?php printf(__('With Klarna your customers can pay by invoice. Klarna works by adding extra personal information fields and then sending the details to Klarna for verification. Documentation <a href="%s" target="_blank">can be found here</a>.', 'klarna'), 'http://docs.woothemes.com/document/klarna/' ); ?></p>

		<?php
		require_once( KLARNA_LIB . 'Klarna.php' );
		require_once( KLARNA_LIB . 'pclasses/storage.intf.php' );

		if ( ! function_exists( 'xmlrpc_encode_entitites' ) && ! class_exists( 'xmlrpcresp' ) ) {
			require_once( KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc' );
			require_once( KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc' );
		}

		if ( ! empty( $this->authorized_countries ) && $this->enabled == 'yes' ) {
			echo '<h4>' . __( 'Active PClasses', 'klarna' ) . '</h4>';
			foreach ( $this->authorized_countries as $key => $country ) {
		    	$data = new WC_Gateway_Klarna_Account;
			    $pclasses = $data->fetch_pclasses( $country );
				if ( $pclasses ) {
					echo '<p>' . $country . '</p>';
					foreach( $pclasses as $pclass ) {
						if ( $pclass->getType() == 2 && $pclass->getExpire() >= time() ) { // Passed from parent file
							echo $pclass->getDescription() . ', ';
						}
					}
					echo '<br/>';
				}
			}
		}
		?>

		<table class="form-table">
			<?php $this->generate_settings_html(); // Generate the HTML For the settings form. ?>
		</table>

	<?php }

	
	/**
	 * Check if this gateway is enabled and available in the user's country
	 * 
	 * @since  1.0.0
	 */	
	function is_available() {

		global $woocommerce;
		
		if ( 'yes' == $this->enabled ) {
			if ( ! $this->get_eid() || ! $this->get_secret() ) { // Required fields check
				return false;
			}
		
			// PClass check
			$data = new WC_Gateway_Klarna_Account();
			$pclasses = $data->fetch_pclasses( $this->get_klarna_country() );
						
			if ( $pclasses ) {
				// PClasses exists. Check if they are valid for Special Campaigns 
				$find_special_campaign = 0;
				
				// Loop through the available PClasses stored in the file srv/pclasses.json
				foreach ( $pclasses as $pclass ) {
					// Check if there are any Special Campaign classes and that these classes is still active
					if ( $pclass->getType() == 2 && $pclass->getExpire() >= time() ) {
       	    			$find_special_campaign++;
					}
				}
			
				// All PClasses have been checked
				// If $find_special_campaign == 0 then we did not find any valid PClass for Special Campaign
				if ( $find_special_campaign == 0 ) {
					return false;
				}
			} else {
				// No PClasses available for Special Campaigns
				return false;
			}
			
			// Checkout form check
			if ( isset( $woocommerce->cart->total ) ) {
				// Cart totals check - Lower threshold
				if ( $this->lower_threshold !== '' ) {
					if ( $woocommerce->cart->total < $this->lower_threshold ) {
						return false;
					}
				}
			
				// Cart totals check - Upper threshold
				if ( $this->upper_threshold !== '' ) {
					if ( $woocommerce->cart->total > $this->upper_threshold ) {
						return false;
					}
				}
				
				// Country check
				if ( $woocommerce->customer->get_country() == true && !in_array($woocommerce->customer->get_country(), $this->authorized_countries) ) {
					return false;
				}
				
				// Currency check
				$currency_for_country = $this->get_currency_for_country( $woocommerce->customer->get_country() );
				if( ! empty( $currency_for_country ) && $currency_for_country !== $this->selected_currency ) {
					return false;
				}
			}
			
			return true;
		}	
	
		return false;

	}
	

	/**
	 * Set up Klarna configuration.
	 * 
	 * @since  2.0
	 **/
	function configure_klarna( $klarna ) {

		$klarna->config(
			$this->get_eid(), 												// EID
			$this->get_secret(), 											// Secret
			$this->get_klarna_country(), 									// Country
			$this->get_klarna_language($this->get_klarna_country()), 		// Language
			$this->selected_currency, 										// Currency
			$this->klarna_mode, 													// Live or test
			$pcStorage = 'jsondb', 											// PClass storage
			$pcURI = 'klarna_pclasses_' . $this->get_klarna_country()		// PClass storage URI path
		);

	}

	
	/**
	 * Payment form on checkout page
	 * 
	 * @since  1.0.0
	 */
	function payment_fields( ) {

	   	global $woocommerce;
	   		   	
	   	// Get PClasses so that the customer can chose between different payment plans.
	  	require_once( KLARNA_LIB . 'Klarna.php' );
		require_once( KLARNA_LIB . 'pclasses/storage.intf.php' );
		
		if ( ! function_exists('xmlrpc_encode_entitites') && ! class_exists( 'xmlrpcresp' ) ) {
			require_once(KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc');
			require_once(KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc');
		}
		
		$klarna = $this->klarna;

		/**
		 * Setup Klarna configuration
		 */
		$this->configure_klarna( $klarna );

		Klarna::$xmlrpcDebug = false;
		Klarna::$debug = false;
		
		// apply_filters to cart total so we can filter this if needed
		$klarna_cart_total = $woocommerce->cart->total;
		$sum = apply_filters( 'klarna_cart_total', $klarna_cart_total ); // Cart total.
		$flag = KlarnaFlags::CHECKOUT_PAGE; // or KlarnaFlags::PRODUCT_PAGE, if you want to do it for one item.
		?>
		
		<?php if ( 'yes' == $this->testmode ) { ?>
			<p><?php _e('TEST MODE ENABLED', 'klarna'); ?></p>
		<?php } ?>

		<?php
		// Description
		if ($this->description) {
			// apply_filters to the description so we can filter this if needed
			$klarna_description = $this->description;
			echo '<p>' . apply_filters( 'klarna_campaign_description', $klarna_description ) . '</p>';
		}

		// Use Klarna PMS for Norway
		if ( 'NO' == $this->get_klarna_country() ) {
			// Use Klarna PMS for Norway
			$payment_method_group = 'special_campaigns';
			$payment_method_select_id = 'klarna_campaign_pclass';
			include_once( KLARNA_DIR . 'views/public/payment-fields-pms.php' );
		// For countries other than NO do the old thing
		} else {
			// For countries other than NO do the old thing
			include_once( KLARNA_DIR . 'views/public/payment-fields-campaign.php' );
		}

	}
	
	
	/**
 	 * Process the gateway specific checkout form fields
	 * @since 1.0.0
	 * @todo  Move compare shipping and billing out if it is shared in all
 	 */
	function klarna_campaign_checkout_field_process() {

    	global $woocommerce;
 		
 		// Only run this if Klarna account is the choosen payment method
 		if ( $_POST['payment_method'] == 'klarna_special_campaign' ) {
 		
 			$klarna_field_prefix = 'klarna_campaign_';

			include_once( KLARNA_DIR . 'includes/checkout-field-process.php' );

		}

	}


	/**
	 * Collect DoB, based on country.
	 * 
	 * @since  2.0
	 **/
	function collect_dob( $order_id ) {
	
		// Collect the dob different depending on country
		if ( $_POST['billing_country'] == 'NL' || $_POST['billing_country'] == 'DE' ) {
			$klarna_pno_day = 
				isset( $_POST['date_of_birth_day'] ) ? woocommerce_clean( $_POST['date_of_birth_day'] ) : '';
			$klarna_pno_month = 
				isset( $_POST['date_of_birth_month'] ) ? woocommerce_clean( $_POST['date_of_birth_month'] ) : '';
			$klarna_pno_year = 
				isset( $_POST['date_of_birth_year'] ) ? woocommerce_clean( $_POST['date_of_birth_year'] ) : '';

			$klarna_pno = $klarna_pno_day . $klarna_pno_month . $klarna_pno_year;
		} else {
			$klarna_pno = 
				isset( $_POST['klarna_campaign_pno'] ) ? woocommerce_clean( $_POST['klarna_campaign_pno'] ) : '';
		}

		return $klarna_pno;

	}

	
	/**
	 * Process the payment and return the result
	 **/
	function process_payment( $order_id ) {

		global $woocommerce;
		
		$order = WC_Klarna_Compatibility::wc_get_order( $order_id );
		
		require_once(KLARNA_LIB . 'Klarna.php');
		require_once(KLARNA_LIB . 'pclasses/storage.intf.php');
		
		if(!function_exists('xmlrpc_encode_entitites') && !class_exists('xmlrpcresp')) {
			require_once(KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc');
			require_once(KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc');
		}
		
		// Get values from klarna form on checkout page
		
		// Collect the DoB
		$klarna_pno = $this->collect_dob( $order_id );

		// Store Klarna specific form values in order as post meta
		update_post_meta( $order_id, 'klarna_pno', $klarna_pno);
		
		$klarna_pclass = 
			isset($_POST['klarna_campaign_pclass']) ? woocommerce_clean($_POST['klarna_campaign_pclass']) : '';
		$klarna_gender = 
			isset($_POST['klarna_campaign_gender']) ? woocommerce_clean($_POST['klarna_campaign_gender']) : '';
		$klarna_de_consent_terms = 
			isset($_POST['klarna_campaign_de_consent_terms']) ? woocommerce_clean($_POST['klarna_campaign_de_consent_terms']) : '';
		
		// Split address into House number and House extension for NL & DE customers
		$klarna_billing = array();
		$klarna_shipping = array();
		if ( $_POST['billing_country'] == 'NL' || $_POST['billing_country'] == 'DE' ) {
			require_once( KLARNA_DIR . 'split-address.php' );
			
			// Set up billing address array
			$klarna_billing_address             = $order->billing_address_1;
			$splitted_address                   = splitAddress( $klarna_billing_address );
			$klarna_billing['address']          = $splitted_address[0];
			$klarna_billing['house_number']	    = $splitted_address[1];
			$klarna_billing['house_extension']  = $splitted_address[2];
			
			// Set up shipping address array
			$klarna_shipping_address            = $order->shipping_address_1;
			$splitted_address                   = splitAddress( $klarna_shipping_address );
			$klarna_shipping['address']         = $splitted_address[0];
			$klarna_shipping['house_number']    = $splitted_address[1];
			$klarna_shipping['house_extension'] = $splitted_address[2];
		} else {			
			$klarna_billing['address']          = $order->billing_address_1;
			$klarna_billing['house_number']     = '';
			$klarna_billing['house_extension']  = '';
			
			$klarna_shipping['address']         = $order->shipping_address_1;
			$klarna_shipping['house_number']    = '';
			$klarna_shipping['house_extension'] = '';
		}
			
		$klarna = $this->klarna;

		/**
		 * Setup Klarna configuration
		 */
		$this->configure_klarna( $klarna );
		
		Klarna::$xmlrpcDebug = false;
		Klarna::$debug = false;

		$klarna_order = new WC_Gateway_Klarna_Order(
			$order,
			$klarna_billing,
			$klarna_shipping,
			$this->ship_to_billing_address,
			$klarna
		);
		
		// Set store specific information so you can e.g. search and associate invoices with order numbers.
		$klarna->setEstoreInfo(
		    $orderid1 = ltrim( $order->get_order_number(), '#'),
		    $orderid2 = $order_id,
		    $user = '' //Username, email or identifier for the user?
		);
		
		try {
    		//Transmit all the specified data, from the steps above, to Klarna.
    		$result = $klarna->reserveAmount(
				$klarna_pno, 			//Date of birth.
				$klarna_gender,			//Gender.
				-1, 					// Automatically calculate and reserve the cart total amount
				KlarnaFlags::NO_FLAG, 	//No specific behaviour like RETURN_OCR or TEST_MODE.
				$klarna_pclass 			// Get the pclass object that the customer has choosen.
    		);
    		
    		// Prepare redirect url
    		$redirect_url = $order->get_checkout_order_received_url();
    		
    		// Store the selected pclass in the order
    		update_post_meta( $order_id, '_klarna_order_pclass', $klarna_pclass );
    		
    		// Retreive response
    		$invno = $result[0];
    		switch($result[1]) {
            case KlarnaFlags::ACCEPTED:
                $order->add_order_note( __('Klarna payment completed. Klarna Invoice number: ', 'klarna') . $invno );
                update_post_meta( $order_id, '_klarna_order_reservation', $invno );
                
                // Payment complete
				$order->payment_complete();		
				
				// Remove cart
				$woocommerce->cart->empty_cart();			
				
				// Return thank you redirect
				return array(
						'result' 	=> 'success',
						'redirect'	=> $redirect_url
				);
						
                break;
            case KlarnaFlags::PENDING:
                $order->add_order_note( __('Order is PENDING APPROVAL by Klarna. Please visit Klarna Online for the latest status on this order. Klarna Invoice number: ', 'klarna') . $invno );
                
                // Payment complete
				$order->payment_complete();
				
				// Remove cart
				$woocommerce->cart->empty_cart();
				
				// Return thank you redirect
				return array(
						'result' 	=> 'success',
						'redirect'	=> $redirect_url
				);
				
                break;
            case KlarnaFlags::DENIED:
                //Order is denied, store it in a database.
				$order->add_order_note( __('Klarna payment denied.', 'klarna') );
				wc_add_notice(__('Klarna payment denied.', 'klarna'), 'error');
				
                return;
                break;
            default:
            	//Unknown response, store it in a database.
				$order->add_order_note( __('Unknown response from Klarna.', 'klarna') );
				wc_add_notice(__('Unknown response from Klarna.', 'klarna'), 'error');
                return;
                break;
        	}
 			
 	   		
			}
		
		catch(Exception $e) {
    		//The purchase was denied or something went wrong, print the message:
			wc_add_notice(sprintf(__('%s (Error code: %s)', 'klarna'), utf8_encode($e->getMessage()), $e->getCode() ), 'error');
			return;
		}

	
	}
	
	/**
	 * receipt_page
	 **/
	function receipt_page( $order ) {
		
		echo '<p>'.__('Thank you for your order.', 'klarna').'</p>';
		
	}
	
	
	
	// Helper function - get Enabled
	function get_enabled() {
		return $this->enabled;
	}
	
	// Helper function - get eid
	function get_eid( $country = false ) {
		
		global $woocommerce;
	
		if( empty($country) ) {
			$country = ( isset( $woocommerce->customer->country ) ) ? $woocommerce->customer->country : $this->shop_country;
		}
		
		$current_eid = '';
		
		switch ( $country )
		{
		case 'DK':
			$current_eid = $this->eid_dk;
			break;
		case 'DE' :
			$current_eid = $this->eid_de;
			break;
		case 'NL' :
			$current_eid = $this->eid_nl;
			break;
		case 'NO' :
			$current_eid = $this->eid_no;
			break;
		case 'FI' :
			$current_eid = $this->eid_fi;
			break;
		case 'SE' :
			$current_eid = $this->eid_se;
			break;
		case 'AT' :
			$current_eid = $this->eid_at;
			break;
		default:
			$current_eid = '';
		}
		
		return $current_eid;
	} // End function
	
	
	// Helper function - get secret
	function get_secret( $country = false ) {
		
		global $woocommerce;
	
		if( empty($country) ) {
			$country = ( isset( $woocommerce->customer->country ) ) ? $woocommerce->customer->country : $this->shop_country;
		}
		
		$current_secret = '';
		
		switch ( $country )
		{
		case 'DK':
			$current_secret = $this->secret_dk;
			break;
		case 'DE' :
			$current_secret = $this->secret_de;
			break;
		case 'NL' :
			$current_secret = $this->secret_nl;
			break;
		case 'NO' :
			$current_secret = $this->secret_no;
			break;
		case 'FI' :
			$current_secret = $this->secret_fi;
			break;
		case 'SE' :
			$current_secret = $this->secret_se;
			break;
		case 'AT' :
			$current_secret = $this->secret_at;
			break;
		default:
			$current_secret = '';
		}
		
		return $current_secret;
	} // End function
	
	
	// Helper function - get correct currency for selected country
	function get_currency_for_country($country) {
				
		switch ( $country )
		{
		case 'DK':
			$currency = 'DKK';
			break;
		case 'DE' :
			$currency = 'EUR';
			break;
		case 'NL' :
			$currency = 'EUR';
			break;
		case 'NO' :
			$currency = 'NOK';
			break;
		case 'FI' :
			$currency = 'EUR';
			break;
		case 'SE' :
			$currency = 'SEK';
			break;
		case 'AT' :
			$currency = 'EUR';
			break;
		default:
			$currency = '';
		}
		
		return $currency;
	} // End function
	
	
	// Helper function - get correct language for selected country
	function get_klarna_language($country) {
				
		switch ( $country )
		{
		case 'DK':
			$language = 'DA';
			break;
		case 'DE' :
			$language = 'DE';
			break;
		case 'NL' :
			$language = 'NL';
			break;
		case 'NO' :
			$language = 'NB';
			break;
		case 'FI' :
			$language = 'FI';
			break;
		case 'SE' :
			$language = 'SV';
			break;
		case 'AT' :
			$language = 'DE';
			break;
		default:
			$language = '';
		}
		
		return $language;
	} // End function
	
	
	// Helper function - get Klarna country
	function get_klarna_country() {
		global $woocommerce;
		
		if ($woocommerce->customer->get_country()) {
			
			$klarna_country = $woocommerce->customer->get_country();
		
		} else {
		
			$klarna_country = $this->shop_language;
			
			switch ( $this->shop_country ) {
				case 'NB' :
					$klarna_country = 'NO';
					break;
				case 'SV' :
					$klarna_country = 'SE';
					break;
			}
		
		}
		
		// Check if $klarna_country exist among the authorized countries
		if(!in_array($klarna_country, $this->authorized_countries)) {
			return $this->shop_country;
		} else {
			return $klarna_country;
		}
	} // End function

	
	/**
	 * Initialise Gateway Settings Form Fields.
	 *
	 * @since 1.0.0
	 */
	function init_form_fields() {
	    
	   	$this->form_fields = array(
			'enabled' => array(
				'title' => __( 'Enable/Disable', 'klarna' ), 
				'type' => 'checkbox', 
				'label' => __( 'Enable Klarna Special Campaigns', 'klarna' ), 
				'default' => 'no'
			), 
			'title' => array(
				'title' => __( 'Title', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'This controls the title which the user sees during checkout.', 'klarna' ), 
				'default' => __( 'Klarna Special Campaigns', 'klarna' )
			),
			'icon_url' => array(
				'title' => __( 'Icon URL', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Enter URL to the icon image. Leave blank to disable.', 'klarna' ), 
				'default' => ''
			),
			'description' => array(
				'title' => __( 'Description', 'klarna' ), 
				'type' => 'textarea', 
				'description' => __( 'This controls the description which the user sees during checkout. ', 'klarna' ), 
				'default' => ''
			), 
			'eid_se' => array(
				'title' => __( 'Eid - Sweden', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Eid for Sweden. Leave blank to disable.', 'klarna' ), 
				'default' => ''
			),
			'secret_se' => array(
				'title' => __( 'Shared Secret - Sweden', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Shared Secret for Sweden.', 'klarna' ), 
				'default' => ''
			),
			'terms_se' => array(
				'title' => __( 'Terms URL - Sweden', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter the URL to the Terms page for Sweden (for specific campaigns). Leave blank to use Klarna Account standard terms.', 'klarna' ), 
				'default' => ''
			),
			'eid_no' => array(
				'title' => __( 'Eid - Norway', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Eid for Norway. Leave blank to disable.', 'klarna' ), 
				'default' => ''
			),
			'secret_no' => array(
				'title' => __( 'Shared Secret - Norway', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Shared Secret for Norway.', 'klarna' ), 
				'default' => ''
			),
			'terms_no' => array(
				'title' => __( 'Terms URL - Norway', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter the URL to the Terms page for Norway (for specific campaigns). Leave blank to use Klarna Account standard terms.', 'klarna' ), 
				'default' => ''
			),
			'eid_fi' => array(
				'title' => __( 'Eid - Finland', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Eid for Finland. Leave blank to disable.', 'klarna' ), 
				'default' => ''
			),
			'secret_fi' => array(
				'title' => __( 'Shared Secret - Finland', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Shared Secret for Finland.', 'klarna' ), 
				'default' => ''
			),
			'terms_fi' => array(
				'title' => __( 'Terms URL - Finland', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter the URL to the Terms page for Finland (for specific campaigns). Leave blank to use Klarna Account standard terms.', 'klarna' ), 
				'default' => ''
			),
			'eid_dk' => array(
				'title' => __( 'Eid - Denmark', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Eid for Denmark. Leave blank to disable.', 'klarna' ), 
				'default' => ''
			),
			'secret_dk' => array(
				'title' => __( 'Shared Secret - Denmark', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Shared Secret for Denmark.', 'klarna' ), 
				'default' => ''
			),
			'eid_de' => array(
				'title' => __( 'Eid - Germany', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Eid for Germany. Leave blank to disable.', 'klarna' ), 
				'default' => ''
			),
			'secret_de' => array(
				'title' => __( 'Shared Secret - Germany', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Shared Secret for Germany.', 'klarna' ), 
				'default' => ''
			),
			'eid_nl' => array(
				'title' => __( 'Eid - Netherlands', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Eid for Netherlands. Leave blank to disable.', 'klarna' ), 
				'default' => ''
			),
			'secret_nl' => array(
				'title' => __( 'Shared Secret - Netherlands', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Shared Secret for Netherlands.', 'klarna' ), 
				'default' => ''
			),
			'lower_threshold' => array(
				'title' => __( 'Lower threshold', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Disable Klarna Special Campaigns if Cart Total is lower than the specified value. Leave blank to disable this feature.', 'klarna' ), 
				'default' => ''
			),
			'upper_threshold' => array(
				'title' => __( 'Upper threshold', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Disable Klarna Special Campaigns if Cart Total is higher than the specified value. Leave blank to disable this feature.', 'klarna' ), 
				'default' => ''
			),
			'ship_to_billing_address' => array(
				'title' => __( 'Send billing address as shipping address', 'klarna' ), 
				'type' => 'checkbox', 
				'label' => __( 'Send the entered billing address in WooCommerce checkout as shipping address to Klarna.', 'klarna' ), 
				'default' => 'no'
			),
			'de_consent_terms' => array(
				'title' => __( 'Klarna consent terms (DE & AT only)', 'klarna' ), 
				'type' => 'checkbox', 
				'label' => __( 'Enable Klarna consent terms checkbox in checkout. This only apply to German & Austrian merchants.', 'klarna' ), 
				'default' => 'no'
			),
			'testmode' => array(
				'title' => __( 'Test Mode', 'klarna' ), 
				'type' => 'checkbox', 
				'label' => __( 'Enable Klarna Test Mode. This will only work if you have a Klarna test account. For test purchases with a live account, <a href="http://integration.klarna.com/en/testing/test-persons" target="_blank">follow these instructions</a>.', 'klarna' ), 
				'default' => 'no'
			)
		);
	    
	}		 
	
} // End class WC_Gateway_Klarna_Campaign