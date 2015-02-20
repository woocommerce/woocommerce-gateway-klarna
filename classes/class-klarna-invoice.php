<?php
/**
 * Klarna Invoice payments
 *
 * @link http://www.woothemes.com/products/klarna/
 * @since 1.0.0
 *
 * @package WC_Gateway_Klarna
 */

class WC_Gateway_Klarna_Invoice extends WC_Gateway_Klarna {
	
	/**
     * Class for Klarna Invoice payment.
     *
     */
     
	public function __construct() {

		global $woocommerce;
		
		parent::__construct();
		
		$this->id = 'klarna';
		$this->method_title = __( 'Klarna Invoice', 'klarna' );
		$this->has_fields = true;
		$this->order_button_text = apply_filters( 'klarna_order_button_text', __( 'Place order', 'woocommerce' ) );
		
		// Load the form fields.
		$this->init_form_fields();
				
		// Load the settings.
		$this->init_settings();
		
		// Define user set variables
		$this->enabled = ( isset( $this->settings['enabled'] ) ) ? $this->settings['enabled'] : '';
		$this->title = ( isset( $this->settings['title'] ) ) ? $this->settings['title'] : '';
		$this->description = ( isset( $this->settings['description'] ) ) ? $this->settings['description'] : '';

		$this->eid_se = 
			( isset( $this->settings['eid_se'] ) ) ? $this->settings['eid_se'] : '';
		$this->secret_se = 
			( isset( $this->settings['secret_se'] ) ) ? $this->settings['secret_se'] : '';
		$this->eid_no = 
			( isset( $this->settings['eid_no'] ) ) ? $this->settings['eid_no'] : '';
		$this->secret_no = 
			( isset( $this->settings['secret_no'] ) ) ? $this->settings['secret_no'] : '';
		$this->eid_fi = 
			( isset( $this->settings['eid_fi'] ) ) ? $this->settings['eid_fi'] : '';
		$this->secret_fi = 
			( isset( $this->settings['secret_fi'] ) ) ? $this->settings['secret_fi'] : '';
		$this->eid_dk = 
			( isset( $this->settings['eid_dk'] ) ) ? $this->settings['eid_dk'] : '';
		$this->secret_dk = 
			( isset( $this->settings['secret_dk'] ) ) ? $this->settings['secret_dk'] : '';
		$this->eid_de = 
			( isset( $this->settings['eid_de'] ) ) ? $this->settings['eid_de'] : '';
		$this->secret_de = 
			( isset( $this->settings['secret_de'] ) ) ? $this->settings['secret_de'] : '';
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
		$this->invoice_fee_id = 
			( isset( $this->settings['invoice_fee_id'] ) ) ? $this->settings['invoice_fee_id'] : '';
		$this->testmode = 
			( isset( $this->settings['testmode'] ) ) ? $this->settings['testmode'] : '';
		$this->de_consent_terms = 
			( isset( $this->settings['de_consent_terms'] ) ) ? $this->settings['de_consent_terms'] : '';
		$this->ship_to_billing_address = 
			( isset( $this->settings['ship_to_billing_address'] ) ) ? $this->settings['ship_to_billing_address'] : '';


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

		if ( $this->invoice_fee_id == '' ) {
			$this->invoice_fee_id = 0;
		}
		
		// Apply filters
		$this->icon = apply_filters( 'klarna_invoice_icon', $this->get_invoice_icon() );
		$this->description = apply_filters( 'klarna_invoice_description', $this->description );
				
		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_klarna', array( $this, 'receipt_page' ) );
		add_action( 'wp_print_footer_scripts', array( $this, 'print_invoice_fee_updater' ) );
		
	}	
	
	
	/**
	 * Admin Panel Options 
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {

    	?>
    	<h3><?php _e('Klarna Invoice', 'klarna'); ?></h3>
	    	
	    	<p><?php printf( __('With Klarna your customers can pay by invoice. Klarna works by adding extra personal information fields and then sending the details to Klarna for verification. Documentation <a href="%s" target="_blank">can be found here</a>.', 'klarna' ), 'http://docs.woothemes.com/document/klarna/' ); ?></p>
	    	
	    	
    	<table class="form-table">
    	<?php
    		// Generate the HTML For the settings form.
    		$this->generate_settings_html();
    	?>
		</table><!--/.form-table-->
    	<?php
    } // End admin_options()
	
	
	/**
	 * Check if this gateway is enabled and available in the user's country
	 */
		
	function is_available() {

		global $woocommerce;
		
		if ( 'yes' == $this->enabled ) {
	
			// Required fields check
			if ( ! $this->get_eid() || ! $this->get_secret() ) {
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
			
				// Only activate the payment gateway if the customers country is the same as the filtered shop country ($this->klarna_country)
				if ( $woocommerce->customer->get_country() == true && ! in_array( $woocommerce->customer->get_country(), $this->get_authorized_countries() ) ) {
					return false;
				}
				
				// Currency check
				$currency_for_country = $this->get_currency_for_country( $woocommerce->customer->get_country() );
				if ( ! empty($currency_for_country) && $currency_for_country !== $this->selected_currency ) {
					return false;
				}
			
			} // End Checkout form check
								
			return true;
					
		} // End if enabled
	
		return false;

	}
	
	
	
	
	/**
	 * Payment form on checkout page
	 */
	
	function payment_fields() {

	   	global $woocommerce;
	   	
		if ( 'yes' == $this->testmode ) { ?>
			<p><?php _e('TEST MODE ENABLED', 'klarna'); ?></p>
		<?php }
		
		// Description
		if ($this->description) :	
			echo '<p>' . $this->description . '</p>';
		endif; 

		// Use Klarna PMS for Norway
		if ( 'NO' == $this->get_klarna_country() ) {

			// Use Klarna PMS for Norway
			$payment_method_group = 'part_payment';
			$payment_method_select_id = 'klarna_account_pclass';
			include_once( KLARNA_DIR . 'views/public/payment-fields-pms.php' );
		
		} else {

			// For countries other than NO do the old thing
			include_once( KLARNA_DIR . 'views/public/payment-fields-kpm.php' );

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
				isset( $_POST['klarna_invo_pno'] ) ? woocommerce_clean( $_POST['klarna_invo_pno'] ) : '';
		}

		return $klarna_pno;

	}


	/**
	 * Set up Klarna configuration.
	 * 
	 * @since  2.0
	 **/
	function configure_klarna( $klarna ) {

		$klarna->config(
			$this->get_eid(), // EID
			$this->get_secret(), // Secret
			$this->get_klarna_country(), // Country
			$this->get_klarna_language( $this->get_klarna_country() ), // Language
			$this->selected_currency, // Currency
			$this->klarna_mode, // Live or test
			$pcStorage = 'jsondb', // PClass storage
			$pcURI = '/srv/pclasses.json' // PClass storage URI path
		);

	}
	
	
	/**
	 * Process the payment and return the result
	 **/
	function process_payment( $order_id ) {
	
		global $woocommerce;
		$klarna_gender = null;
		
		$order = WC_Klarna_Compatibility::wc_get_order( $order_id );
		
		require_once(KLARNA_LIB . 'Klarna.php');
		
		if(!function_exists('xmlrpc_encode_entitites') && !class_exists('xmlrpcresp')) {
			require_once(KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc');
			require_once(KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc');
		}
		
		// Get values from klarna form on checkout page
		
		// Collect the DoB
		$klarna_pno = $this->collect_dob( $order_id );

		// Store Klarna specific form values in order as post meta
		update_post_meta( $order_id, 'klarna_pno', $klarna_pno);
		
		$klarna_pclass = isset($_POST['klarna_invo_pclass']) ? woocommerce_clean($_POST['klarna_invo_pclass']) : KlarnaPClass::INVOICE; // KlarnaPClass::INVOICE = -1
		$klarna_gender = isset($_POST['klarna_invo_gender']) ? woocommerce_clean($_POST['klarna_invo_gender']) : '';
		$klarna_de_consent_terms = isset($_POST['klarna_invo_de_consent_terms']) ? woocommerce_clean($_POST['klarna_invo_de_consent_terms']) : '';
		

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
			$user = '' // Username, email or identifier for the user?
		);

		try {
		
    		//Transmit all the specified data, from the steps above, to Klarna.
    		$result = $klarna->reserveAmount(
				$klarna_pno, 				// Date of birth. 
				$klarna_gender,				// Gender.
				-1, 						// Automatically calculate and reserve the cart total amount
    		    KlarnaFlags::NO_FLAG, 		// No specific behaviour like RETURN_OCR or TEST_MODE.
				$klarna_pclass				// -1, notes that this is an invoice purchase, for part payment purchase you will have a pclass object which you use getId() from.
    		);
    		
    		$redirect_url = $order->get_checkout_order_received_url();

    		// Store the selected pclass in the order
    		update_post_meta( $order_id, '_klarna_order_pclass', $klarna_pclass );
    		
    		// Retreive response
    		$invno = $result[0];
    		switch($result[1]) {
            
            case '1':
                $order->add_order_note( __('Klarna payment completed. Klarna reservation number: ', 'klarna') . $invno );
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
           
            case '2':
                $order->add_order_note( __('Order is PENDING APPROVAL by Klarna. Please visit Klarna Online for the latest status on this order. Klarna reservarion number: ', 'klarna') . $invno );
                
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
		
		catch( Exception $e ) {
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
	
	

	
	/**
	 * print_invoice_fee_updater()
	 * Adds inline javascript in the footer that updates the totals on the checkout form when a payment method is selected.
	 **/
	function print_invoice_fee_updater () {
		global $woocommerce;
		if ( is_checkout() && $this->enabled=="yes" && $this->invoice_fee_id > 0 ) {
			?>
			<script type="text/javascript">
				//<![CDATA[
				jQuery(document).ready(function($){
					$(document.body).on('change', 'input[name="payment_method"]', function() {
						$('body').trigger('update_checkout');
					});
				});
				//]]>
			</script>
			<?php
		}
		
		// Klarna invoice not available for Companies in Germany and Austria
		// Disable the radio button for the Invoice payment method if Company name is entered
		if ( is_checkout() && $this->enabled=="yes" ) {
			?>
			<script type="text/javascript">
				//<![CDATA[
				jQuery(document).ajaxComplete(function(){
		
				    if (jQuery.trim(jQuery('input[name=billing_company]').val()) && (jQuery( "#billing_country" ).val()=='DE' || jQuery( "#billing_country" ).val()=='AT')) {
				    	
				        jQuery('#payment_method_klarna').prop('disabled', true);
				        
				    } else jQuery('#payment_method_klarna').prop('disabled', false);
						    
				});
				
				jQuery(document).ready(function($){
											    
					$(window).load(function(){
						
						$('input[name=billing_company]').keyup(function() {
						    if ($.trim(this.value).length && ($( "#billing_country" ).val()=='DE' || $( "#billing_country" ).val()=='AT')) {
						    	
						        $('#payment_method_klarna').prop('disabled', true);
						        
						    } else $('#payment_method_klarna').prop('disabled', false);
						});
						
					});	
				});
				//]]>
			</script>
			<?php
		}
	
	}
	
	
	
	// Helper function - get Invoice fee id
	function get_invoice_fee_id() {

		return $this->invoice_fee_id;

	}
	
	// Helper function - get Invoice fee name
	function get_invoice_fee_name() {

		if ( $this->invoice_fee_id > 0 ) {
			$product = WC_Klarna_Compatibility::wc_get_product( $this->invoice_fee_id );
			if ( $product ) {
				return $product->get_title();
			} else {
				return '';
			}
		} else {
			return '';
		}
		
	}
	
	
	// Helper function - get Invoice fee price
	function get_invoice_fee_price() {

		if ( $this->invoice_fee_id > 0 ) {
			$product = WC_Klarna_Compatibility::wc_get_product( $this->invoice_fee_id );
			if ( $product ) {
				return $product->get_price();
			} else {
				return '';
			}
		} else {
			return '';
		}
		
	}
	
	
	// Helper function - get Shop Country
	function get_klarna_shop_country() {

		return $this->shop_country;

	}
	
	// Helper function - get Testmode
	function get_testmode() {

		return $this->testmode;

	}
	
	// Helper function - get Enabled
	function get_enabled() {

		return $this->enabled;

	}
	
	// Helper function - get eid
	function get_eid() {
		
		global $woocommerce;
		$country = ( isset( $woocommerce->customer->country ) ) ? $woocommerce->customer->country : '';
	
		if( empty($country) ) {
			$country = $this->shop_country;
		}
		
		$current_eid = '';
		
		switch ( $country ) {
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
	}
	
	
	// Helper function - get secret
	function get_secret() {
		
		global $woocommerce;
		$country = ( isset( $woocommerce->customer->country ) ) ? $woocommerce->customer->country : '';
	
		if( empty($country) ) {
			$country = $this->shop_country;
		}

		$current_secret = '';
		
		switch ( $country )	{
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
	function get_currency_for_country( $country ) {
				
		switch ( $country ) {
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

	}
	
	
	// Helper function - get correct language for selected country
	function get_klarna_language( $country ) {
				
		switch ( $country ) {
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

	}
	
	
	// Helper function - invoice icon
	function get_invoice_icon() {
		
		global $woocommerce;
		$country = ( isset( $woocommerce->customer->country ) ) ? $woocommerce->customer->country : '';
	
		if ( empty( $country ) ) {
			$country = $this->shop_country;
		}
		
		$current_secret = '';
		
		switch ( $country ) {
			case 'DK':
				$klarna_invoice_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/da_dk/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			case 'DE' :
				$klarna_invoice_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/de_de/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			case 'NL' :
				$klarna_invoice_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/nl_nl/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			case 'NO' :
				$klarna_invoice_icon = false;
				break;
			case 'FI' :
				$klarna_invoice_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/fi_fi/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			case 'SE' :
				$klarna_invoice_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/sv_se/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			case 'AT' :
				$klarna_invoice_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/de_at/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			default:
				$klarna_invoice_icon = '';
		}
		
		return $klarna_invoice_icon;

	}
	
	
	// Helper function - get Klarna country
	function get_klarna_country() {

		global $woocommerce;
		
		if ( $woocommerce->customer->get_country() ) {
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
		
		// Check if $klarna_country exists among the authorized countries
		if ( ! in_array( $klarna_country, $this->get_authorized_countries() ) ) {
			return $this->shop_country;
		} else {
			return $klarna_country;
		}

	}
	
	
	
	// Helper function - get consent terms
	function get_consent_terms() {

		return $this->de_consent_terms;

	}
	
	// Helper function - get authorized countries
	public function get_authorized_countries() {

		$this->authorized_countries		= array();
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
		if ( ! empty( $this->eid_at ) ) {
			$this->authorized_countries[] = 'AT';
		}
		
		return $this->authorized_countries;

	}


	/**
	 * Initialise Gateway Settings Form Fields.
	 *
	 * @since 1.0.0
	 */
	function init_form_fields() {
	    
	   	$this->form_fields = apply_filters( 'klarna_invoice_form_fields', array(
			'enabled' => array(
				'title' => __( 'Enable/Disable', 'klarna' ), 
				'type' => 'checkbox', 
				'label' => __( 'Enable Klarna Invoice', 'klarna' ), 
				'default' => 'no'
			), 
			'title' => array(
				'title' => __( 'Title', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'This controls the title which the user sees during checkout.', 'klarna' ), 
				'default' => __( 'Invoice', 'klarna' )
			),
			'description' => array(
				'title' => __( 'Description', 'klarna' ), 
				'type' => 'textarea', 
				'description' => __( 'This controls the description which the user sees during checkout.', 'klarna' ), 
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
			'eid_at' => array(
				'title' => __( 'Eid - Austria', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Eid for Austria. Leave blank to disable.', 'klarna' ), 
				'default' => ''
			),
			'secret_at' => array(
				'title' => __( 'Shared Secret - Austria', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Shared Secret for Austria.', 'klarna' ), 
				'default' => ''
			),
			'lower_threshold' => array(
				'title' => __( 'Lower threshold', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Disable Klarna Invoice if Cart Total is lower than the specified value. Leave blank to disable this feature.', 'klarna' ), 
				'default' => ''
			),
			'upper_threshold' => array(
				'title' => __( 'Upper threshold', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Disable Klarna Invoice if Cart Total is higher than the specified value. Leave blank to disable this feature.', 'klarna' ), 
				'default' => ''
			),
			'invoice_fee_id' => array(
				'title' => __( 'Invoice Fee', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Create a hidden (simple) product that acts as the invoice fee. Enter the ID number in this textfield. Leave blank to disable. ', 'klarna' ), 
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
				'label' => __( 'Enable Klarna consent terms checkbox in checkout. This only apply to German and Austrian merchants.', 'klarna' ), 
				'default' => 'no'
			),
			'testmode' => array(
				'title' => __( 'Test Mode', 'klarna' ), 
				'type' => 'checkbox', 
				'label' => __( 'Enable Klarna Test Mode. This will only work if you have a Klarna test account. For test purchases with a live account, <a href="http://integration.klarna.com/en/testing/test-persons" target="_blank">follow these instructions</a>.', 'klarna' ), 
				'default' => 'no'
			)
		) );
	    
	}

} // End class WC_Gateway_Klarna_Invoice


/**
 * Class WC_Gateway_Klarna_Invoice_Extra
 * Extra class for functions that needs to be executed outside the payment gateway class.
 * Since version 1.5.4 (WooCommerce version 2.0)
 */
class WC_Gateway_Klarna_Invoice_Extra {

	public function __construct() {
		
		// Add Invoice fee via the new Fees API
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'calculate_fees' ));
		
		// Chcek Klarna specific fields on Checkout
		add_action('woocommerce_checkout_process', array( $this, 'klarna_invoice_checkout_field_process'));

	}
	
	/**
	 * Calculate fees on checkout form.
	 */
	public function calculate_fees( $cart ) {

		global $woocommerce;
		$current_gateway = '';
		
    	if ( is_checkout() || defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
    		
    		$available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();
			
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
				if ( 'klarna' === $current_gateway->id ) {
	        		$this->add_fee_to_cart( $cart );
				}
			}
		}

	}
	
	/**
	 * Add the fee to the cart if Klarna is selected payment method and if a fee is used.
	 */
	public function add_fee_to_cart( $cart ) {

		$invoice_fee = new WC_Gateway_Klarna_Invoice;
		$this->invoice_fee_id = $invoice_fee->get_invoice_fee_id();

		if ( $this->invoice_fee_id > 0 ) {
			$product = WC_Klarna_Compatibility::wc_get_product( $this->invoice_fee_id );
				 	
		 	if ( $product ) {
		 		// Is this a taxable product?
		 		if ( $product->is_taxable() ) {
		 			$product_tax = true;
		 		} else {
			 		$product_tax = false;
			 	}
			 	
			 	$cart->add_fee( $product->get_title(), $product->get_price_excluding_tax(), $product_tax,$product->get_tax_class() );
			}
		}

	}
	
	/**
 	 * Process the gateway specific checkout form fields
 	 **/
	function klarna_invoice_checkout_field_process() {
 
    	global $woocommerce;
    	
    	$data = new WC_Gateway_Klarna_Invoice;
		$this->shop_country = $data->get_klarna_shop_country();
		$this->de_consent_terms = $data->get_consent_terms();

 		
 		// Only run this if Klarna invoice is the choosen payment method
 		if ( $_POST['payment_method'] == 'klarna' ) {

 			$klarna_field_prefix = 'klarna_invo_';

			include_once( KLARNA_DIR . 'includes/checkout-field-process.php' );

		}

	}

}
$wc_klarna_invoice_extra = new WC_Gateway_Klarna_Invoice_Extra;