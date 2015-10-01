<?php
class WC_Gateway_Klarna_Checkout extends WC_Gateway_Klarna {
			
	public function __construct() { 
		global $woocommerce;
		
		parent::__construct();
        
		//$this->shop_country	= get_option('woocommerce_default_country');
       	
       	$this->id			= 'klarna_checkout';
       	$this->method_title = __('Klarna Checkout', 'klarna');
       	$this->has_fields 	= false;
       	
       	// Load the form fields.
       	$this->init_form_fields();
				
       	// Load the settings.
       	$this->init_settings();
       	
       	
       	// Define user set variables
       	$this->enabled							= ( isset( $this->settings['enabled'] ) ) ? $this->settings['enabled'] : '';
       	$this->title 							= ( isset( $this->settings['title'] ) ) ? $this->settings['title'] : '';
       	$this->log 								= new WC_Logger();
       	
       	
       	$this->eid_se							= ( isset( $this->settings['eid_se'] ) ) ? $this->settings['eid_se'] : '';
       	$this->secret_se						= ( isset( $this->settings['secret_se'] ) ) ? $this->settings['secret_se'] : '';
       	$this->klarna_checkout_url_se			= ( isset( $this->settings['klarna_checkout_url_se'] ) ) ? $this->settings['klarna_checkout_url_se'] : '';
       	$this->klarna_checkout_thanks_url_se	= ( isset( $this->settings['klarna_checkout_thanks_url_se'] ) ) ? $this->settings['klarna_checkout_thanks_url_se'] : '';
       	
       	$this->eid_no							= ( isset( $this->settings['eid_no'] ) ) ? $this->settings['eid_no'] : '';
       	$this->secret_no						= ( isset( $this->settings['secret_no'] ) ) ? $this->settings['secret_no'] : '';
       	$this->klarna_checkout_url_no			= ( isset( $this->settings['klarna_checkout_url_no'] ) ) ? $this->settings['klarna_checkout_url_no'] : '';
       	$this->klarna_checkout_thanks_url_no	= ( isset( $this->settings['klarna_checkout_thanks_url_no'] ) ) ? $this->settings['klarna_checkout_thanks_url_no'] : '';
       	
       	$this->eid_fi							= ( isset( $this->settings['eid_fi'] ) ) ? $this->settings['eid_fi'] : '';
       	$this->secret_fi						= ( isset( $this->settings['secret_fi'] ) ) ? $this->settings['secret_fi'] : '';
       	$this->klarna_checkout_url_fi			= ( isset( $this->settings['klarna_checkout_url_fi'] ) ) ? $this->settings['klarna_checkout_url_fi'] : '';
       	$this->klarna_checkout_thanks_url_fi	= ( isset( $this->settings['klarna_checkout_thanks_url_fi'] ) ) ? $this->settings['klarna_checkout_thanks_url_fi'] : '';
       	
       	$this->eid_de							= ( isset( $this->settings['eid_de'] ) ) ? $this->settings['eid_de'] : '';
       	$this->secret_de						= ( isset( $this->settings['secret_de'] ) ) ? $this->settings['secret_de'] : '';
       	$this->klarna_checkout_url_de			= ( isset( $this->settings['klarna_checkout_url_de'] ) ) ? $this->settings['klarna_checkout_url_de'] : '';
       	$this->klarna_checkout_thanks_url_de	= ( isset( $this->settings['klarna_checkout_thanks_url_de'] ) ) ? $this->settings['klarna_checkout_thanks_url_de'] : '';
       	$this->phone_mandatory_de				= ( isset( $this->settings['phone_mandatory_de'] ) ) ? $this->settings['phone_mandatory_de'] : '';
       	$this->dhl_packstation_de				= ( isset( $this->settings['dhl_packstation_de'] ) ) ? $this->settings['dhl_packstation_de'] : '';
       	
       	
       	$this->default_eur_contry				= ( isset( $this->settings['default_eur_contry'] ) ) ? $this->settings['default_eur_contry'] : '';
       	
       	$this->terms_url						= ( isset( $this->settings['terms_url'] ) ) ? $this->settings['terms_url'] : '';
       	$this->testmode							= ( isset( $this->settings['testmode'] ) ) ? $this->settings['testmode'] : '';
       	$this->debug							= ( isset( $this->settings['debug'] ) ) ? $this->settings['debug'] : '';
       	
       	$this->modify_standard_checkout_url		= ( isset( $this->settings['modify_standard_checkout_url'] ) ) ? $this->settings['modify_standard_checkout_url'] : '';
       	$this->add_std_checkout_button			= ( isset( $this->settings['add_std_checkout_button'] ) ) ? $this->settings['add_std_checkout_button'] : '';
       	$this->std_checkout_button_label		= ( isset( $this->settings['std_checkout_button_label'] ) ) ? $this->settings['std_checkout_button_label'] : '';
       	
       	$this->create_customer_account			= ( isset( $this->settings['create_customer_account'] ) ) ? $this->settings['create_customer_account'] : '';
       	$this->send_new_account_email			= ( isset( $this->settings['send_new_account_email'] ) ) ? $this->settings['send_new_account_email'] : '';
       	
       	$this->account_signup_text				= ( isset( $this->settings['account_signup_text'] ) ) ? $this->settings['account_signup_text'] : '';
       	$this->account_login_text				= ( isset( $this->settings['account_login_text'] ) ) ? $this->settings['account_login_text'] : '';
       	
       	
       	
		
       	
		if ( empty($this->terms_url) ) 
			$this->terms_url = esc_url( get_permalink(woocommerce_get_page_id('terms')) );
        	
       	// Check if this is test mode or not
		if ( $this->testmode == 'yes' ):
			$this->klarna_server = 'https://checkout.testdrive.klarna.com/checkout/orders';	
		else :
			$this->klarna_server = 'https://checkout.klarna.com/checkout/orders';
		endif;

		// Set current country based on used currency
		switch ( get_woocommerce_currency() ) {
			
			case 'NOK' :
				$klarna_country = 'NO';
				break;
			case 'EUR' :
				if( get_locale() == 'de_DE' ) {
					$klarna_country = 'DE';
				} elseif( get_locale() == 'fi' ) {
					$klarna_country = 'FI';
				} else {
					$klarna_country = $this->default_eur_contry;
				}
				break;
			case 'SEK' :
				$klarna_country = 'SE';
				break;
			default:
				$klarna_country = '';
		}

		$this->shop_country	= $klarna_country;
		
		// Country and language
		switch ( $this->shop_country ) {
			case 'NO' :
			case 'NB' :
			//case 'NOK' :
				$klarna_country 			= 'NO';
				$klarna_language 			= 'nb-no';
				$klarna_currency 			= 'NOK';
				$klarna_eid 				= $this->eid_no;
				$klarna_secret 				= $this->secret_no;
				$klarna_checkout_url 		= $this->klarna_checkout_url_no;
				if ($this->klarna_checkout_thanks_url_no == '' ) {
					$klarna_checkout_thanks_url 	= $this->klarna_checkout_url_no;
				} else {
					$klarna_checkout_thanks_url 	= $this->klarna_checkout_thanks_url_no;
				}
				break;
			case 'FI' :
			//case 'EUR' :
				$klarna_country 			= 'FI';
				
				// Check if WPML is used and determine if Finnish or Swedish is used as language
				if ( class_exists( 'woocommerce_wpml' ) && defined('ICL_LANGUAGE_CODE') && strtoupper(ICL_LANGUAGE_CODE) == 'SV') {
					// Swedish
					$klarna_language 			= 'sv-fi';
				} else {
					// Finnish
					$klarna_language 			= 'fi-fi';
				}
				
				$klarna_currency 			= 'EUR';
				$klarna_eid 				= $this->eid_fi;
				$klarna_secret 				= $this->secret_fi;
				$klarna_checkout_url 		= $this->klarna_checkout_url_fi;
				if ($this->klarna_checkout_thanks_url_fi == '' ) {
					$klarna_checkout_thanks_url 	= $this->klarna_checkout_url_fi;
				} else {
					$klarna_checkout_thanks_url 	= $this->klarna_checkout_thanks_url_fi;
				}
				break;
			case 'SE' :
			case 'SV' :
			//case 'SEK' :
				$klarna_country 			= 'SE';
				
				
				$klarna_language 			= 'sv-se';
				$klarna_currency 			= 'SEK';
				$klarna_eid 				= $this->eid_se;
				$klarna_secret 				= $this->secret_se;
				$klarna_checkout_url 		= $this->klarna_checkout_url_se;
				if ($this->klarna_checkout_thanks_url_se == '' ) {
					$klarna_checkout_thanks_url 	= $this->klarna_checkout_url_se;
				} else {
					$klarna_checkout_thanks_url 	= $this->klarna_checkout_thanks_url_se;
				}
				break;
			case 'DE' :
				$klarna_country 			= 'DE';
				$klarna_language 			= 'de-de';
				$klarna_currency 			= 'EUR';
				$klarna_eid 				= $this->eid_de;
				$klarna_secret 				= $this->secret_de;
				$klarna_checkout_url 		= $this->klarna_checkout_url_de;
				if ($this->klarna_checkout_thanks_url_de == '' ) {
					$klarna_checkout_thanks_url 	= $this->klarna_checkout_url_de;
				} else {
					$klarna_checkout_thanks_url 	= $this->klarna_checkout_thanks_url_de;
				}
				break;
			default:
				$klarna_country = '';
				$klarna_language = '';
				$klarna_currency = '';
				$klarna_eid = '';
				$klarna_secret = '';
				$klarna_checkout_url = '';
				$klarna_invoice_terms = '';
				$klarna_invoice_icon = '';
				$klarna_checkout_thanks_url = '';
		}
		
		// Apply filters to Country and language
		$this->klarna_country 				= apply_filters( 'klarna_country', $klarna_country );
		$this->klarna_language 				= apply_filters( 'klarna_language', $klarna_language );
		$this->klarna_currency 				= apply_filters( 'klarna_currency', $klarna_currency );
		$this->klarna_eid					= apply_filters( 'klarna_eid', $klarna_eid );
		$this->klarna_secret				= apply_filters( 'klarna_secret', $klarna_secret );
		$this->klarna_checkout_url			= apply_filters( 'klarna_checkout_url', $klarna_checkout_url );
		$this->klarna_checkout_thanks_url	= apply_filters( 'klarna_checkout_thanks_url', $klarna_checkout_thanks_url );
		
		global $klarna_checkout_thanks_url;
		$klarna_checkout_thanks_url = $this->klarna_checkout_thanks_url;
		
	   	add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
       	
       	add_action( 'woocommerce_api_wc_gateway_klarna_checkout', array( $this, 'check_checkout_listener' ) );
       	
		// We execute the woocommerce_thankyou hook when the KCO Thank You page is rendered,
		// because other plugins use this, but we don't want to display the actual WC Order
		// details table in KCO Thank You page. This action is removed here, but only when
		// in Klarna Thank You page.
		if ( is_page() ) {
			global $post;
			$klarna_checkout_page_id = url_to_postid ( $this->klarna_checkout_thanks_url );
			if ( $post->ID == $klarna_checkout_page_id ) {
				remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );
			}
		}

		// add_action( 'add_meta_boxes', array( $this, 'add_klarna_meta_box' ) );
       	
       	// Ajax
       	add_action( 'wp_ajax_customer_update_kco_order_note', array($this, 'customer_update_kco_order_note') );
		add_action( 'wp_ajax_nopriv_customer_update_kco_order_note', array($this, 'customer_update_kco_order_note') );
		add_action( 'wp_footer', array( $this, 'js_order_note' ) );
		add_action( 'wp_footer', array( $this, 'ajaxurl'));

		// Cancel unpaid orders for KCO orders too
		add_filter( 'woocommerce_cancel_unpaid_order', array( $this, 'cancel_unpaid_kco' ), 10, 2 );
       	
    }


	/**
	 * Cancel unpaid KCO orders if the option is enabled
	 * 
	 * @param  $cancel 	boolean 	Cancel or not
	 * @param  $order 	Object  	WooCommerce order object
	 * @since  1.9.8.3
	 **/
	function cancel_unpaid_kco( $cancel, $order ) {
		if ( 'klarna_checkout' == get_post_meta( $order->id, '_created_via', true ) ) {
			$cancel = true;
		}

		return $cancel;
	}

	 
    /**
	 * Initialise Gateway Settings Form Fields
	 */
	 
	 function init_form_fields() {
    
		 $this->form_fields = apply_filters('klarna_checkout_form_fields', array(
		 	'enabled' => array(
							'title' => __( 'Enable/Disable', 'klarna' ), 
							'type' => 'checkbox', 
							'label' => __( 'Enable Klarna Checkout', 'klarna' ), 
							'default' => 'no'
						), 
			'title' => array(
							'title' => __( 'Title', 'klarna' ), 
							'type' => 'text', 
							'description' => __( 'This controls the title which the user sees during checkout.', 'klarna' ), 
							'default' => __( 'Klarna Checkout', 'klarna' )
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
			'klarna_checkout_url_se' => array(
							'title' => __( 'Custom Checkout Page - Sweden', 'klarna' ), 
							'type' => 'text', 
							'description' => __( 'Please enter the URL to the page that acts as Checkout Page for Klarna Checkout Sweden. This page must contain the shortcode [woocommerce_klarna_checkout].', 'klarna' ), 
							'default' => ''
						),
			'klarna_checkout_thanks_url_se' => array(
							'title' => __( 'Custom Thanks Page - Sweden', 'klarna' ), 
							'type' => 'text', 
							'description' => __( 'Enter the URL to the page that acts as Thanks Page for Klarna Checkout Sweden. This page must contain the shortcode [woocommerce_klarna_checkout]. Leave blank to use the Custom Checkout Page as Thanks Page.', 'klarna' ), 
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
			'klarna_checkout_url_no' => array(
							'title' => __( 'Custom Checkout Page - Norway', 'klarna' ), 
							'type' => 'text', 
							'description' => __( 'Please enter the URL to the page that acts as Checkout Page for Klarna Checkout Norway. This page must contain the shortcode [woocommerce_klarna_checkout].', 'klarna' ), 
							'default' => ''
						),
			'klarna_checkout_thanks_url_no' => array(
							'title' => __( 'Custom Thanks Page - Norway', 'klarna' ), 
							'type' => 'text', 
							'description' => __( 'Enter the URL to the page that acts as Thanks Page for Klarna Checkout Norway. This page must contain the shortcode [woocommerce_klarna_checkout]. Leave blank to use the Custom Checkout Page as Thanks Page.', 'klarna' ), 
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
			'klarna_checkout_url_fi' => array(
							'title' => __( 'Custom Checkout Page - Finland', 'klarna' ), 
							'type' => 'text', 
							'description' => __( 'Please enter the URL to the page that acts as Checkout Page for Klarna Checkout Finland. This page must contain the shortcode [woocommerce_klarna_checkout].', 'klarna' ), 
							'default' => ''
						),
			'klarna_checkout_thanks_url_fi' => array(
							'title' => __( 'Custom Thanks Page - Finland', 'klarna' ), 
							'type' => 'text', 
							'description' => __( 'Enter the URL to the page that acts as Thanks Page for Klarna Checkout Finland. This page must contain the shortcode [woocommerce_klarna_checkout]. Leave blank to use the Custom Checkout Page as Thanks Page.', 'klarna' ), 
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
			'klarna_checkout_url_de' => array(
							'title' => __( 'Custom Checkout Page - Germany', 'klarna' ), 
							'type' => 'text', 
							'description' => __( 'Please enter the URL to the page that acts as Checkout Page for Klarna Checkout Germany. This page must contain the shortcode [woocommerce_klarna_checkout].', 'klarna' ), 
							'default' => ''
						),
			'klarna_checkout_thanks_url_de' => array(
							'title' => __( 'Custom Thanks Page - Germany', 'klarna' ), 
							'type' => 'text', 
							'description' => __( 'Enter the URL to the page that acts as Thanks Page for Klarna Checkout Germany. This page must contain the shortcode [woocommerce_klarna_checkout]. Leave blank to use the Custom Checkout Page as Thanks Page.', 'klarna' ), 
							'default' => ''
						),
			'phone_mandatory_de' => array(
							'title' => __( 'Phone Number Mandatory - Germany', 'klarna' ), 
							'type' => 'checkbox', 
							'label' => __( 'Phone number is not mandatory for Klarna Checkout in Germany by default. Check this box to make it mandatory.', 'klarna' ), 
							'default' => 'no'
						),
			'dhl_packstation_de' => array(
							'title' => __( 'DHL Packstation Functionality - Germany', 'klarna' ), 
							'type' => 'checkbox', 
							'label' => __( 'Enable DHL packstation functionality for German customers.', 'klarna' ),
							'default' => 'no'
						),
			'default_eur_contry' => array(
								'title' => __( 'Default Checkout Country', 'klarna' ),
								'type' => 'select',
								'options' => array('DE'=>__( 'Germany', 'klarna' ), 'FI'=>__( 'Finland', 'klarna' )),
								'description' => __( 'Used by the payment gateway to determine which country should be the default Checkout country if Euro is the selected currency, you as a merchant has an agreement with multiple countries that use Euro and the selected language cant be of help for this decision.', 'klarna' ),
								'default' => 'DE'
							),
			'modify_standard_checkout_url' => array(
							'title' => __( 'Modify Standard Checkout', 'klarna' ), 
							'type' => 'checkbox', 
							'label' => __( 'Make the Custom Checkout Page for Klarna Checkout the default checkout page (i.e. changing the url of the checkout buttons in Cart and the Widget mini cart).', 'klarna' ), 
							'default' => 'yes'
						),
			'add_std_checkout_button' => array(
							'title' => __( 'Button to Standard Checkout', 'klarna' ), 
							'type' => 'checkbox', 
							'label' => __( 'Add a button when the Klarna Checkout form is displayed that links to the standard checkout page.', 'klarna' ), 
							'default' => 'no'
						),
			'std_checkout_button_label' => array(
							'title' => __( 'Label for Standard Checkout Button', 'klarna' ), 
							'type' => 'text', 
							'description' => __( 'Please enter the text for the button that links to the standard checkout page from the Klarna Checkout form.', 'klarna' ), 
							'default' => ''
						),
			'terms_url' => array(
							'title' => __( 'Terms Page', 'klarna' ), 
							'type' => 'text', 
							'description' => __( 'Please enter the URL to the page that acts as Terms Page for Klarna Checkout. Leave blank to use the defined WooCommerce Terms Page.', 'klarna' ), 
							'default' => ''
						),
			'create_customer_account' => array(
							'title' => __( 'Create customer account', 'klarna' ), 
							'type' => 'checkbox', 
							'label' => __( 'Automatically create an account for new customers.', 'klarna' ), 
							'default' => 'no'
						),
			'send_new_account_email' => array(
							'title' => __( 'Send New account email when creating new accounts.', 'klarna' ), 
							'type' => 'checkbox', 
							'label' => __( 'Send New account email', 'klarna' ), 
							'default' => 'no'
						),
			'account_signup_text' => array(
							'title' => __( 'Account Signup Text', 'klarna' ), 
							'type' => 'textarea', 
							'description' => __( 'Add text above the Account Registration Form. Useful for legal text for German stores. See documentation for more information. Leave blank to disable.', 'klarna' ), 
							'default' => ''
						),
			'account_login_text' => array(
							'title' => __( 'Account Login Text', 'klarna' ), 
							'type' => 'textarea', 
							'description' => __( 'Add text above the Account Login Form. Useful for legal text for German stores. See documentation for more information. Leave blank to disable.', 'klarna' ), 
							'default' => ''
						),
			'testmode' => array(
							'title' => __( 'Test Mode', 'klarna' ), 
							'type' => 'checkbox', 
							'label' => __( 'Enable Klarna Test Mode. This will only work if you have a Klarna test account.', 'klarna' ), 
							'default' => 'no'
						),
			'debug' => array(
							'title' => __( 'Debug', 'klarna' ), 
							'type' => 'checkbox', 
							'label' => __( 'Enable logging (<code>woocommerce/logs/klarna.txt</code>)', 'klarna' ), 
							'default' => 'no'
						)
		) );
    
	} // End init_form_fields()
	
	
	
	/**
	 * Admin Panel Options 
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 */
	 
	 public function admin_options() {
	 	?>
	 	<h3><?php _e('Klarna Checkout', 'klarna'); ?></h3>
	   	
	   	<p><?php printf(__('With Klarna Checkout your customers can pay by invoice or credit card. Klarna Checkout works by replacing the standard WooCommerce checkout form. Documentation <a href="%s" target="_blank">can be found here</a>.', 'klarna'), 'http://wcdocs.woothemes.com/user-guide/extensions/klarna/' ); ?></p>
	   	
	   	<?php
	   	// If the WooCommerce terms page isn't set, do nothing.
		$klarna_terms_page = get_option('woocommerce_terms_page_id');
		if ( empty($klarna_terms_page) && empty($this->terms_url) ) {
			echo '<strong>' . __('You need to specify a Terms Page in the WooCommerce settings or in the Klarna Checkout settings in order to enable the Klarna Checkout payment method.', 'klarna') . '</strong>';
		}
		
		// Check if Curl is installed. If not - display message to the merchant about this.
		if( function_exists('curl_version') ) {
			// Do nothing
		} else {
			echo '<div id="message" class="error"><p>' . __('The PHP library cURL does not seem to be installed on your server. Klarna Checkout will not work without it.', 'klarna') . '</p></div>';
		}
		?>
	    	
	    	
	   	<table class="form-table">
	    	<?php
		    // Generate the HTML For the settings form.
		    $this->generate_settings_html();
		    ?>
		</table><!--/.form-table-->
		<?php
	} // End admin_options()
			
	
	
	/**
	 * Make the gateway disabled on the regular checkout page
	 */
	
	function is_available() {
		 global $woocommerce;

		 return false;
	}


	
 	
 	
 	/**
	 * Render checkout page
	 */
		function get_klarna_checkout_page() {

			// Debug
			if ($this->debug=='yes') $this->log->add( 'klarna', 'KCO page about to render...' );
			
			global $woocommerce;
			global $current_user;
			get_currentuserinfo();
			
			//ob_start();
			require_once(KLARNA_DIR . '/src/Klarna/Checkout.php');
			//ob_end_clean();
			
			if (isset($_GET['klarna_order'])) {
			
				// Display Order response/thank you page via iframe from Klarna
				
				// Debug
				if ($this->debug=='yes') $this->log->add( 'klarna', 'Rendering Thank you page...' );
				
				//@session_start();
				
				// Shared secret
				$sharedSecret = $this->klarna_secret;
				
				Klarna_Checkout_Order::$contentType = "application/vnd.klarna.checkout.aggregated-order-v2+json";  
				
				$orderUri = $_GET['klarna_order'];
				
				$connector = Klarna_Checkout_Connector::create($sharedSecret);  
								
				$klarna_order = new Klarna_Checkout_Order($connector, $orderUri);
				
				$klarna_order->fetch();  
				
				
				if ($klarna_order['status'] == 'checkout_incomplete') {
				
					//echo "Checkout not completed, redirect to checkout.php";
					wp_redirect( $this->klarna_checkout_url );
					exit;  
				}

				$snippet = $klarna_order['gui']['snippet'];
			
				// DESKTOP: Width of containing block shall be at least 750px
				// MOBILE: Width of containing block shall be 100% of browser window (No
				// padding or margin)
				
				ob_start();
				
				do_action( 'klarna_before_kco_confirmation', $_GET['sid'] );
				
				echo '<div>' . $snippet . '</div>';	
				
				do_action( 'klarna_after_kco_confirmation', $_GET['sid'] );
				
				do_action( 'woocommerce_thankyou', $_GET['sid'] );
				
				WC()->session->__unset( 'klarna_checkout' );
				
				// Remove cart
				$woocommerce->cart->empty_cart();
				
				return ob_get_clean();
			
			} else {
				
				// Display Checkout page
				
				// Don't render the Klarna Checkout form if the payment gateway isn't enabled.
				if ($this->enabled != 'yes') return;
				
				
				// If no Klarna country is set - return.
				if ( empty($this->klarna_country) ) {
					echo apply_filters( 'klarna_checkout_wrong_country_message', sprintf(__('Sorry, you can not buy via Klarna Checkout from your country or currency. Please <a href="%s">use another payment method</a>.', 'klarna'), get_permalink( get_option('woocommerce_checkout_page_id') )) );
					return;
				}
				
				// Recheck cart items so that they are in stock
				$result = $woocommerce->cart->check_cart_item_stock();
				if( is_wp_error($result) ) {
					return $result->get_error_message();
				}
				
				// If checkout registration is disabled and not logged in, the user cannot checkout
				$checkout = $woocommerce->checkout();
				if ( ! $checkout->enable_guest_checkout && ! is_user_logged_in() ) {
					//do_action( 'woocommerce_login_form' );
					echo apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) );
					return;
				}
				
				// Process order via Klarna Checkout page				

				if ( !defined( 'WOOCOMMERCE_CHECKOUT' ) ) define( 'WOOCOMMERCE_CHECKOUT', true );
				if ( !defined( 'WOOCOMMERCE_KLARNA_CHECKOUT' ) ) define( 'WOOCOMMERCE_KLARNA_CHECKOUT', true );
				
				// Set Klarna Checkout as the choosen payment method in the WC session
				WC()->session->set( 'chosen_payment_method', 'klarna_checkout' );
				
				// Debug
				if ($this->debug=='yes') $this->log->add( 'klarna', 'Rendering Checkout page...' );
				
				// Mobile or desktop browser
				if (wp_is_mobile() ) {
					$klarna_checkout_layout = 'mobile';
				 } else {
				 	$klarna_checkout_layout = 'desktop';
				 }
		
				// If the WooCommerce terms page or the Klarna Checkout settings field Terms Page isn't set, do nothing.
				if ( empty($this->terms_url) ) return;
				
				// Set $add_klarna_window_size_script to true so that Window size detection script can load in the footer
				global $add_klarna_window_size_script;
				$add_klarna_window_size_script = true;
	
				// Add button to Standard Checkout Page if this is enabled in the settings
				if ( $this->add_std_checkout_button == 'yes' ) {
					echo '<div class="woocommerce"><a href="' . get_permalink( get_option('woocommerce_checkout_page_id') ) . '" class="button std-checkout-button">' . $this->std_checkout_button_label . '</a></div>';
				}
				
				
				if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) {
					
        			// Create a new order
        			$order_id = $this->create_order();
        			
        			// Check that the order doesnt contain an error message (from check_cart_item_stock() fired in create_order())
        			if (!is_numeric($order_id)) {
        				//var_dump($order_id);
	        			echo '<ul class="woocommerce-error"><li>' . __( $order_id, 'woocommerce' ) . '</li></ul>';
	        			exit();
        			}
        			
        			do_action( 'woocommerce_checkout_order_processed', $order_id, false );
        			
        			// Store Order ID in session so it can be re-used if customer navigates away from the checkout and then return again
					$woocommerce->session->order_awaiting_payment = $order_id;
        			
        			// Get an instance of the created order
        			$order = WC_Klarna_Compatibility::wc_get_order( $order_id );			
        			$cart = array();
        			
        			// Cart Contents
        			if ( sizeof( $order->get_items() ) > 0 ) {
						foreach ( $order->get_items() as $item ) {
							
							if ( $item['qty'] ) {
								$_product = $order->get_product_from_item( $item );	
								
								// We manually calculate the tax percentage here
								if ( $_product->is_taxable() && $order->get_line_tax($item)>0 ) {
									// Calculate tax percentage
									$item_tax_percentage = round($order->get_item_tax( $item, false) / $order->get_item_total( $item, false, false ), 2)*100;
								} else {
									$item_tax_percentage = 00;
								}

								$item_name 	= $item['name'];
								
								$item_meta = new WC_Order_Item_Meta( $item );
								if ( $meta = $item_meta->display( true, true ) )
									$item_name .= ' ( ' . $meta . ' )';
									
								// apply_filters to item price so we can filter this if needed
								$klarna_item_price_including_tax = $order->get_item_total( $item, true );
								$item_price = apply_filters( 'klarna_item_price_including_tax', $klarna_item_price_including_tax );
							
								
								// Get SKU or product id
								$reference = '';
								if ( $_product->get_sku() ) {
									$reference = $_product->get_sku();
								} elseif ( $_product->variation_id ) {
									$reference = $_product->variation_id;
								} else {
									$reference = $_product->id;
								}
								$item_price = number_format( $order->get_item_total( $item, true )*100, 0, '', '');
								$cart[] = array(
											'reference' => strval($reference),
											'name' => strip_tags($item_name),
											'quantity' => (int)$item['qty'],
											'unit_price' => (int)$item_price,
											'discount_rate' => 0,
											'tax_rate' => intval($item_tax_percentage . '00')
										);
									
							} // End if qty
						
						} // End foreach
					
					} // End if sizeof get_items()
					
				
					// Shipping
					if( $order->get_total_shipping() > 0 ) {
						
						// We manually calculate the tax percentage here
						if ($order->get_total_shipping() > 0) {
							// Calculate tax percentage
							$shipping_tax_percentage = round($order->get_shipping_tax() / $order->get_total_shipping(), 2)*100;
						} else {
							$shipping_tax_percentage = 00;
						}
						
						$shipping_price = number_format( ($order->get_total_shipping()+$order->get_shipping_tax())*100, 0, '', '');
						
						
						$cart[] = array(  
						 	'type' => 'shipping_fee',  
						 	'reference' => 'SHIPPING',  
						 	'name' => $order->get_shipping_method(),  
						 	'quantity' => 1,  
						 	'unit_price' => (int)$shipping_price,  
						 	'tax_rate' => intval($shipping_tax_percentage . '00')
						 );
					}
				
					// Discount
					if ($order->order_discount>0) {
		
						$klarna_order_discount = (int)number_format( $order->order_discount, 2, '', '');
				
						$cart[] = array(    
						 	'reference' => 'DISCOUNT',  
						 	'name' => __('Discount', 'klarna'),  
						 	'quantity' => 1,  
						 	'unit_price' => -$klarna_order_discount,  
						 	'tax_rate' => 0  
						);
					}
								
					// Merchant ID
					$eid = $this->klarna_eid;
    		
					// Shared secret
					$sharedSecret = $this->klarna_secret;
    		
					Klarna_Checkout_Order::$baseUri = $this->klarna_server;
					Klarna_Checkout_Order::$contentType = 'application/vnd.klarna.checkout.aggregated-order-v2+json';
					
					//@session_start();
					
					$connector = Klarna_Checkout_Connector::create($sharedSecret);   		

					$klarna_order = null;
					
		        	if ( WC()->session->get( 'klarna_checkout' ) ) {
						
						// Resume session
						$klarna_order = new Klarna_Checkout_Order(
							$connector,
							WC()->session->get( 'klarna_checkout' )
						);					
						
						try {

       						$klarna_order->fetch();
       						$klarna_order_as_array = $klarna_order->marshal();

       						// Reset session if the country in the store has changed since last time the checkout was loaded
							if ( strtolower( $this->klarna_country ) != strtolower( $klarna_order_as_array['purchase_country'] ) ) {
	       						
	       						// Reset session
		   						$klarna_order = null;
								WC()->session->__unset( 'klarna_checkout' );
		   						
       						} else {
       						
       							// Update order
       							
	       						// Reset cart
	       						$update['cart']['items'] = array();
	       						foreach ($cart as $item) {
	        					    $update['cart']['items'][] = $item;
	        					}
	        					// Update the order WC id
	        					$update['purchase_country'] = $this->klarna_country;
								$update['purchase_currency'] = $this->klarna_currency;
								$update['locale'] = $this->klarna_language;
								$update['merchant']['id'] = $eid;
								$update['merchant']['terms_uri'] = $this->terms_url;
								$update['merchant']['checkout_uri'] = esc_url_raw( add_query_arg(
									'klarnaListener',
									'checkout',
									$this->klarna_checkout_url
								) );
	        					$update['merchant']['confirmation_uri'] = add_query_arg( 
	        						array(
	        							'klarna_order' => '{checkout.order.uri}',
	        							'sid' => $order_id,
	        							'order-received' => $order_id
	        						),
	        						$this->klarna_checkout_thanks_url
	        					);
	        					$update['merchant']['push_uri'] = add_query_arg(
	        						array(
	        							'sid' => $order_id, 
	        							'scountry' => $this->klarna_country, 
	        							'klarna_order' => '{checkout.order.uri}', 
	        							'wc-api' => 'WC_Gateway_Klarna_Checkout'
	        						), 
	        						$this->klarna_checkout_url 
	        					);
	        					
	        					// Customer info if logged in
								if( $this->testmode !== 'yes' ) {

									if ( $current_user->user_email ) {
										$update['shipping_address']['email'] = $current_user->user_email;
									}
							
									if ( $woocommerce->customer->get_shipping_postcode() ) {
										$update['shipping_address']['postal_code'] = $woocommerce->customer->get_shipping_postcode();
									}
									
								}
							
	        					$klarna_order->update( apply_filters( 'kco_update_order', $update ) );
 
        					} // End if country change
        				
        				} catch ( Exception $e ) {

        					// Reset session
        					$klarna_order = null;
							WC()->session->__unset( 'klarna_checkout' );

        				}
        			}
					
		        		
        			if ( $klarna_order == null ) {
						
	        			// Start new session
	        			$create['purchase_country'] = $this->klarna_country;
	        			$create['purchase_currency'] = $this->klarna_currency;
	        			$create['locale'] = $this->klarna_language;
	        			$create['merchant']['id'] = $eid;
	        			$create['merchant']['terms_uri'] = $this->terms_url;
	        			$create['merchant']['checkout_uri'] = esc_url_raw( add_query_arg(
	        				'klarnaListener',
	        				'checkout',
	        				$this->klarna_checkout_url
	        			) );
	        			$create['merchant']['confirmation_uri'] = add_query_arg(
	        				array(
	        					'klarna_order' => '{checkout.order.uri}',
	        					'sid' => $order_id,
	        					'order-received' => $order_id
	        				),
	        				$this->klarna_checkout_thanks_url
	        			);
	        			$create['merchant']['push_uri'] = add_query_arg(
	        				array(
	        					'sid' => $order_id, 
	        					'scountry' => $this->klarna_country, 
	        					'klarna_order' => '{checkout.order.uri}', 
	        					'wc-api' => 'WC_Gateway_Klarna_Checkout'
	        				),
	        				$this->klarna_checkout_url
	        			);
	        			
	        			// Make phone a mandatory field for German stores?
	        			if( $this->phone_mandatory_de == 'yes' ) {
		        			$create['options']['phone_mandatory'] = true;	
	        			}
	        			
	        			// Enable DHL packstation feature for German stores?
	        			if( $this->dhl_packstation_de == 'yes' ) {
		        			$create['options']['packstation_enabled'] = true;	
	        			}
	        			
	        			
	        			// Customer info if logged in
	        			if( $this->testmode !== 'yes' ) {
							if($current_user->user_email) {
								$create['shipping_address']['email'] = $current_user->user_email;
							}
						
							if($woocommerce->customer->get_shipping_postcode()) {
								$create['shipping_address']['postal_code'] = $woocommerce->customer->get_shipping_postcode();
							}
						}
						
	        			
	        			$create['gui']['layout'] = $klarna_checkout_layout;
	        			
	        			foreach ($cart as $item) {
		        			$create['cart']['items'][] = $item;
		        		}
		        		
		        		
		        		
		        		$klarna_order = new Klarna_Checkout_Order($connector);
		        		$klarna_order->create( apply_filters( 'kco_create_order', $create ) );
		        		$klarna_order->fetch();

		        	}
					
		        	
		        	// Store location of checkout session
		        	$sessionId = $klarna_order->getLocation();
		        	if ( null === WC()->session->get( 'klarna_checkout' ) ) {
		        		WC()->session->set( 'klarna_checkout', $sessionId );
		        	}
		        	
		        	// Display checkout
		        	$snippet = $klarna_order['gui']['snippet'];
		        	
		        	ob_start();
		        	
		        	do_action( 'klarna_before_kco_checkout', $order_id );
		        	
		        	echo '<div>' . apply_filters( 'klarna_kco_checkout', $snippet ) . '</div>';
		        	
		        	do_action( 'klarna_after_kco_checkout', $order_id );
		        	
		        	return ob_get_clean();
		        	
    		    		
		        } // End if sizeof cart 

		    } // End if isset($_GET['klarna_order'])
    	
    	} // End Function
    	
    	
    	
    	/**
	     * Order confirmation via IPN
	     */
		
	    function check_checkout_listener() {
			if (isset($_GET['klarna_order'])) {
				global $woocommerce;
				
				if ($this->debug=='yes') $this->log->add( 'klarna', 'IPN callback from Klarna' );
				if ($this->debug=='yes') $this->log->add( 'klarna', 'Klarna order: ' . $_GET['klarna_order'] );
				if ($this->debug=='yes') $this->log->add( 'klarna', 'GET: ' . json_encode($_GET) );
				
				require_once(KLARNA_DIR . '/src/Klarna/Checkout.php');  
  
				//@session_start();  
				
				Klarna_Checkout_Order::$contentType = "application/vnd.klarna.checkout.aggregated-order-v2+json";  
				
				switch ( $_GET['scountry'] ) {
				case 'SE':
					$klarna_secret = $this->secret_se;
					break;
				case 'FI' :
					$klarna_secret = $this->secret_fi;
					break;
				case 'NO' :
					$klarna_secret = $this->secret_no;
					break;
				case 'DE' :
					$klarna_secret = $this->secret_de;
					break;
				default:
					$klarna_secret = '';
				}
		
				$connector = Klarna_Checkout_Connector::create($klarna_secret);  
				
				$checkoutId = $_GET['klarna_order'];  
				$klarna_order = new Klarna_Checkout_Order($connector, $checkoutId);  
				$klarna_order->fetch();  

				if ($this->debug=='yes') {
					$this->log->add( 'klarna', 'ID: ' . $klarna_order['id']);
					$this->log->add( 'klarna', 'Billing: ' . $klarna_order['billing_address']['given_name']);
					$this->log->add( 'klarna', 'Order ID: ' . $_GET['sid']);
					$this->log->add( 'klarna', 'Reference: ' . $klarna_order['reservation']);
					$this->log->add( 'klarna', 'Fetched order from Klarna: ' . var_export($klarna_order, true));
				}
				
				if ($klarna_order['status'] == "checkout_complete") { 
							
					$order_id = $_GET['sid'];
					$order = WC_Klarna_Compatibility::wc_get_order( $_GET['sid'] );
					
					// Different names on the returned street address if it's a German purchase or not
					$received__billing_address_1 	= '';
					$received__shipping_address_1 	= '';
					
					if( $_GET['scountry'] == 'DE' ) {
						
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
					update_post_meta( $order_id, '_billing_country', $klarna_order['billing_address']['country'] );
					update_post_meta( $order_id, '_billing_email', $klarna_order['billing_address']['email'] );
					update_post_meta( $order_id, '_billing_phone', apply_filters( 'klarna_checkout_billing_phone', $klarna_order['billing_address']['phone'] ) );
					
					// Add customer shipping address - retrieved from callback from Klarna
					$allow_separate_shipping = ( isset( $klarna_order['options']['allow_separate_shipping_address'] ) ) ? $klarna_order['options']['allow_separate_shipping_address'] : '';
					
					if( $allow_separate_shipping == 'true' ||  $_GET['scountry'] == 'DE' ) {
						
						update_post_meta( $order_id, '_shipping_first_name', $klarna_order['shipping_address']['given_name'] );
						update_post_meta( $order_id, '_shipping_last_name', $klarna_order['shipping_address']['family_name'] );
						update_post_meta( $order_id, '_shipping_address_1', $received__shipping_address_1 );
						update_post_meta( $order_id, '_shipping_address_2', $klarna_order['shipping_address']['care_of'] );
						update_post_meta( $order_id, '_shipping_postcode', $klarna_order['shipping_address']['postal_code'] );
						update_post_meta( $order_id, '_shipping_city', $klarna_order['shipping_address']['city'] );
						update_post_meta( $order_id, '_shipping_country', $klarna_order['shipping_address']['country'] );
					
					} else {
						
						update_post_meta( $order_id, '_shipping_first_name', $klarna_order['billing_address']['given_name'] );
						update_post_meta( $order_id, '_shipping_last_name', $klarna_order['billing_address']['family_name'] );
						update_post_meta( $order_id, '_shipping_address_1', $received__billing_address_1 );
						update_post_meta( $order_id, '_shipping_address_2', $klarna_order['billing_address']['care_of'] );
						update_post_meta( $order_id, '_shipping_postcode', $klarna_order['billing_address']['postal_code'] );
						update_post_meta( $order_id, '_shipping_city', $klarna_order['billing_address']['city'] );
						update_post_meta( $order_id, '_shipping_country', $klarna_order['billing_address']['country'] );
					}
					
					
					// Store user id in order so the user can keep track of track it in My account
					if( email_exists( $klarna_order['billing_address']['email'] )) {
						
						if ($this->debug=='yes') 
							$this->log->add( 'klarna', 'Billing email: ' . $klarna_order['billing_address']['email']);
					
						$user = get_user_by('email', $klarna_order['billing_address']['email']);
						
						if ($this->debug=='yes') 
							$this->log->add( 'klarna', 'Customer User ID: ' . $user->ID);
							
						$this->customer_id = $user->ID;
						
						update_post_meta( $order_id, '_customer_user', $this->customer_id );
					
					} else {
						
						// Create new user
						if( $this->create_customer_account == 'yes' ) {
							
							$password = '';
							$new_customer = $this->create_new_customer( $klarna_order['billing_address']['email'], $klarna_order['billing_address']['email'], $password );
							
							// Creation failed
							if ( is_wp_error( $new_customer ) ) {
								$order->add_order_note(sprintf(__('Customer creation failed. Error: %s.', 'klarna'), $new_customer->get_error_message(), $klarna_order['id']));
								$this->customer_id = 0;
							// Creation succeeded
							} else {
								$order->add_order_note(sprintf(__('New customer created (user ID %s).', 'klarna'), $new_customer, $klarna_order['id']));
								$this->customer_id = $new_customer;
								
							}
						
							update_post_meta( $order_id, '_customer_user', $this->customer_id );
						}

	
					}
					
					$order->add_order_note(sprintf(__('Klarna Checkout payment completed. Reservation number: %s.  Klarna order number: %s', 'klarna'), $klarna_order['reservation'], $klarna_order['id']));
					
					// Update the order in Klarnas system
					$update['status'] = 'created';
					$update['merchant_reference'] = array(  
														'orderid1' => ltrim( $order->get_order_number(), '#')
													);  
					$klarna_order->update($update);
					
					
					// Check order not already completed or processing
					// To avoid triggering of multiple payment_complete() callbacks
					if ( $order->status == 'completed' || $order->status == 'processing' ) {
		        
						if ( $this->debug == 'yes' ) {
							$this->log->add( 'klarna', 'Aborting, Order #' . $order_id . ' is already complete.' );
						}
				        
				    } else {
				    
				    	// Payment complete
				    	// Update order meta
				    	update_post_meta( $order_id, 'klarna_order_status', 'created' );
						update_post_meta( $order_id, '_klarna_order_reservation', $klarna_order['reservation'] );
						
						$order->payment_complete();
						// Debug
						if ($this->debug=='yes') $this->log->add( 'klarna', 'Payment complete action triggered' );
						
						// Remove cart
						$woocommerce->cart->empty_cart();
					
					}
					
					// Other plugins and themes can hook into here
					do_action( 'klarna_after_kco_push_notification', $order_id );
					
					
				}
			
			} // Endif klarnaListener == checkout
		
		} // End function check_checkout_listener
		
		
		/**
		 * Create new order
		 */
		public function create_order() {
			
			if( WC_Klarna_Compatibility::is_wc_version_gte_2_2() ) {
	    		// Version 2.2 and above
				$order_id = $this->create_order_2_2();
			} else {
	    		// Version 2.1
	    		$order_id = $this->create_order_2_1();
			}
		
			return $order_id;
		
		} // End function create_order()
		
		
		
		/**
		 * Create new order for WooCommerce version 2.2+
		 * @return int|WP_ERROR 
		 */
		public function create_order_2_2() {
			global $woocommerce, $wpdb;
			
			$this->shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
			
			if ( sizeof( $woocommerce->cart->get_cart() ) == 0 )
				wc_add_notice(sprintf( __( 'Sorry, your session has expired. <a href="%s">Return to homepage &rarr;</a>', 'klarna' ), home_url() ), 'error');
				
			// Recheck cart items so that they are in stock
			$result = $woocommerce->cart->check_cart_item_stock();
			if( is_wp_error($result) ) {
				return $result->get_error_message();
				exit();
			}
			
			// Update cart totals
			$woocommerce->cart->calculate_totals();
				
			// Customer accounts
			$this->customer_id = apply_filters( 'woocommerce_checkout_customer_id', get_current_user_id() );
	
			// Give plugins the opportunity to create an order themselves
			if ( $order_id = apply_filters( 'woocommerce_create_order', null, $this ) ) {
				return $order_id;
			}
	
			try {
				// Start transaction if available
				$wpdb->query( 'START TRANSACTION' );
	
				$order_data = array(
					'status'        => apply_filters( 'woocommerce_default_order_status', 'pending' ),
					'customer_id'   => $this->customer_id,
					'customer_note' => isset( $this->posted['order_comments'] ) ? $this->posted['order_comments'] : ''
				);
	
				// Insert or update the post data
				$order_id = absint( WC()->session->order_awaiting_payment );
	
				// Resume the unpaid order if its pending
				if ( $order_id > 0 && ( $order = wc_get_order( $order_id ) ) && $order->has_status( array( 'pending', 'failed' ) ) ) {
	
					$order_data['order_id'] = $order_id;
					$order                  = wc_update_order( $order_data );
	
					if ( is_wp_error( $order ) ) {
						throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
					} else {
						$order->remove_order_items();
						do_action( 'woocommerce_resume_order', $order_id );
					}
	
				} else {
	
					$order = wc_create_order( $order_data );
	
					if ( is_wp_error( $order ) ) {
						throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
					} else {
						$order_id = $order->id;
						do_action( 'woocommerce_new_order', $order_id );
					}
				}
	
				// Store the line items to the new/resumed order
				foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
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
						throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
					}
	
					// Allow plugins to add order item meta
					do_action( 'woocommerce_add_order_item_meta', $item_id, $values, $cart_item_key );
				}
	
				// Store fees
				foreach ( WC()->cart->get_fees() as $fee_key => $fee ) {
					$item_id = $order->add_fee( $fee );
	
					if ( ! $item_id ) {
						throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
					}
	
					// Allow plugins to add order item meta to fees
					do_action( 'woocommerce_add_order_fee_meta', $order_id, $item_id, $fee, $fee_key );
				}
	
				// Store shipping for all packages
				foreach ( WC()->shipping->get_packages() as $package_key => $package ) {
					if ( isset( $package['rates'][ $this->shipping_methods[ $package_key ] ] ) ) {
						$item_id = $order->add_shipping( $package['rates'][ $this->shipping_methods[ $package_key ] ] );
	
						if ( ! $item_id ) {
							throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
						}
	
						// Allows plugins to add order item meta to shipping
						do_action( 'woocommerce_add_shipping_order_item', $order_id, $item_id, $package_key );
					}
				}
	
				// Store tax rows
				foreach ( array_keys( WC()->cart->taxes + WC()->cart->shipping_taxes ) as $tax_rate_id ) {
					if ( ! $order->add_tax( $tax_rate_id, WC()->cart->get_tax_amount( $tax_rate_id ), WC()->cart->get_shipping_tax_amount( $tax_rate_id ) ) ) {
						throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
					}
				}
	
				// Store coupons
				foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
					if ( ! $order->add_coupon( $code, WC()->cart->get_coupon_discount_amount( $code ) ) ) {
						throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
					}
				}
				/*
				// Billing address
				$billing_address = array();
				if ( $this->checkout_fields['billing'] ) {
					foreach ( array_keys( $this->checkout_fields['billing'] ) as $field ) {
						$field_name = str_replace( 'billing_', '', $field );
						$billing_address[ $field_name ] = $this->get_posted_address_data( $field_name );
					}
				}
	
				// Shipping address.
				$shipping_address = array();
				if ( $this->checkout_fields['shipping'] ) {
					foreach ( array_keys( $this->checkout_fields['shipping'] ) as $field ) {
						$field_name = str_replace( 'shipping_', '', $field );
						$shipping_address[ $field_name ] = $this->get_posted_address_data( $field_name, 'shipping' );
					}
				}
				
				$order->set_address( $billing_address, 'billing' );
				$order->set_address( $shipping_address, 'shipping' );
				*/
				
				// Payment Method
				$available_gateways = WC()->payment_gateways->payment_gateways();
				$this->payment_method = $available_gateways[ 'klarna_checkout' ];
			
				$order->set_payment_method( $this->payment_method );
				$order->set_total( WC()->cart->shipping_total, 'shipping' );
				$order->set_total( WC()->cart->get_total_discount(), 'order_discount' );
				$order->set_total( WC()->cart->get_cart_discount_total(), 'cart_discount' );
				$order->set_total( WC()->cart->tax_total, 'tax' );
				$order->set_total( WC()->cart->shipping_tax_total, 'shipping_tax' );
				$order->set_total( WC()->cart->total );
				
				// Update user meta
				/*
				if ( $this->customer_id ) {
					if ( apply_filters( 'woocommerce_checkout_update_customer_data', true, $this ) ) {
						foreach ( $billing_address as $key => $value ) {
							update_user_meta( $this->customer_id, 'billing_' . $key, $value );
						}
						foreach ( $shipping_address as $key => $value ) {
							update_user_meta( $this->customer_id, 'shipping_' . $key, $value );
						}
					}
					do_action( 'woocommerce_checkout_update_user_meta', $this->customer_id, $this->posted );
				}
				*/
	
				// Let plugins add meta
				do_action( 'woocommerce_checkout_update_order_meta', $order_id, array() );
	
				// If we got here, the order was created without problems!
				$wpdb->query( 'COMMIT' );
	
			} catch ( Exception $e ) {
				// There was an error adding order data!
				$wpdb->query( 'ROLLBACK' );
				return new WP_Error( 'checkout-error', $e->getMessage() );
			}
	
			return $order_id;
		}
		
		
		/**
		 * Create new order for WooCommerce version 2.1+
		 * @return int|WP_ERROR 
		 */
		 
		 function create_order_2_1() { 
			 
			global $woocommerce, $wpdb;
			$order_id = "";
			
			if ( sizeof( $woocommerce->cart->get_cart() ) == 0 )
				wc_add_notice(sprintf( __( 'Sorry, your session has expired. <a href="%s">Return to homepage &rarr;</a>', 'klarna' ), home_url() ), 'error');
				
				
			// Recheck cart items so that they are in stock
			$result = $woocommerce->cart->check_cart_item_stock();
			if( is_wp_error($result) ) {
				return $result->get_error_message();
				exit();
			}
			
			// Update cart totals
			$woocommerce->cart->calculate_totals();
			
			
			// Give plugins the opportunity to create an order themselves
			$order_id = apply_filters( 'woocommerce_create_order', null, $this );
			
			if ( is_numeric( $order_id ) )
				return $order_id;
			
			// Create Order (send cart variable so we can record items and reduce inventory). Only create if this is a new order, not if the payment was rejected.
			$order_data = apply_filters( 'woocommerce_new_order_data', array(
				'post_type' 	=> 'shop_order',
				'post_title' 	=> sprintf( __( 'Order &ndash; %s', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'woocommerce' ) ) ),
				'post_status' 	=> 'publish',
				'ping_status'	=> 'closed',
				'post_excerpt' 	=> isset( $this->posted['order_comments'] ) ? $this->posted['order_comments'] : '',
				'post_author' 	=> 1,
				'post_password'	=> uniqid( 'order_' )	// Protects the post just in case
			) );
			
			// Insert or update the post data
			$create_new_order = true;
			
			if ( WC()->session->order_awaiting_payment > 0 ) {
			
				$order_id = absint( WC()->session->order_awaiting_payment );
			
				/* Check order is unpaid by getting its status */
				$terms = wp_get_object_terms( $order_id, 'shop_order_status', array( 'fields' => 'slugs' ) );
				$order_status = isset( $terms[0] ) ? $terms[0] : 'pending';
			
				// Resume the unpaid order if its pending
				if ( $order_status == 'pending' || $order_status == 'failed' ) {
			
					// Update the existing order as we are resuming it
					$create_new_order = false;
					$order_data['ID'] = $order_id;
					wp_update_post( $order_data );
			
					// Clear the old line items - we'll add these again in case they changed
					$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id IN ( SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d )", $order_id ) );
			
					$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d", $order_id ) );
			
					// Trigger an action for the resumed order
					do_action( 'woocommerce_resume_order', $order_id );
				}
			}
			
			if ( $create_new_order ) {
				$order_id = wp_insert_post( $order_data, true );
			
				if ( is_wp_error( $order_id ) )
					throw new Exception( 'Error: Unable to create order. Please try again.' );
				else
					do_action( 'woocommerce_new_order', $order_id );
			}
			
			
			
			
			// Store the line items to the new/resumed order
			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
			
				$_product = $values['data'];
				
			   	// Add line item
			   	$item_id = wc_add_order_item( $order_id, array(
			 		'order_item_name' 		=> $_product->get_title(),
			 		'order_item_type' 		=> 'line_item'
			 	) );
			
			 	// Add line item meta
			 	if ( $item_id ) {
				 	wc_add_order_item_meta( $item_id, '_qty', apply_filters( 'woocommerce_stock_amount', $values['quantity'] ) );
				 	wc_add_order_item_meta( $item_id, '_tax_class', $_product->get_tax_class() );
				 	wc_add_order_item_meta( $item_id, '_product_id', $values['product_id'] );
				 	wc_add_order_item_meta( $item_id, '_variation_id', $values['variation_id'] );
				 	wc_add_order_item_meta( $item_id, '_line_subtotal', wc_format_decimal( $values['line_subtotal'] ) );
				 	wc_add_order_item_meta( $item_id, '_line_total', wc_format_decimal( $values['line_total'] ) );
				 	wc_add_order_item_meta( $item_id, '_line_tax', wc_format_decimal( $values['line_tax'] ) );
				 	wc_add_order_item_meta( $item_id, '_line_subtotal_tax', wc_format_decimal( $values['line_subtotal_tax'] ) );
			
				 	// Store variation data in meta so admin can view it
					if ( $values['variation'] && is_array( $values['variation'] ) ) {
						foreach ( $values['variation'] as $key => $value ) {
							$key = str_replace( 'attribute_', '', $key );
							wc_add_order_item_meta( $item_id, $key, $value );
						}
					}
			
				 	// Add line item meta for backorder status
				 	if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $values['quantity'] ) ) {
				 		wc_add_order_item_meta( $item_id, apply_filters( 'woocommerce_backordered_item_meta_name', __( 'Backordered', 'woocommerce' ), $cart_item_key, $order_id ), $values['quantity'] - max( 0, $_product->get_total_stock() ) );
				 	}
			
				 	// Allow plugins to add order item meta
				 	do_action( 'woocommerce_add_order_item_meta', $item_id, $values, $cart_item_key );
			 	}
			}
			
			// Store fees
			foreach ( WC()->cart->get_fees() as $fee_key => $fee ) {
				$item_id = wc_add_order_item( $order_id, array(
			 		'order_item_name' 		=> $fee->name,
			 		'order_item_type' 		=> 'fee'
			 	) );
			
			 	if ( $fee->taxable )
			 		wc_add_order_item_meta( $item_id, '_tax_class', $fee->tax_class );
			 	else
			 		wc_add_order_item_meta( $item_id, '_tax_class', '0' );
			
			 	wc_add_order_item_meta( $item_id, '_line_total', wc_format_decimal( $fee->amount ) );
				wc_add_order_item_meta( $item_id, '_line_tax', wc_format_decimal( $fee->tax ) );
			
				// Allow plugins to add order item meta to fees
				do_action( 'woocommerce_add_order_fee_meta', $order_id, $item_id, $fee, $fee_key );
			}
			
			// Store shipping for all packages
			$packages = WC()->shipping->get_packages();
			$this->shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
			
			foreach ( $packages as $i => $package ) {
				
				if ( isset( $package['rates'][ $this->shipping_methods[ $i ] ] ) ) {
			
					$method = $package['rates'][ $this->shipping_methods[ $i ] ];
					
					$item_id = wc_add_order_item( $order_id, array(
				 		'order_item_name' 		=> $method->label,
				 		'order_item_type' 		=> 'shipping'
				 	) );
			
					if ( $item_id ) {
				 		wc_add_order_item_meta( $item_id, 'method_id', $method->id );
			 			wc_add_order_item_meta( $item_id, 'cost', wc_format_decimal( $method->cost ) );
						do_action( 'woocommerce_add_shipping_order_item', $order_id, $item_id, $i );
			 		}
				}
			}
			
			// Store tax rows
			foreach ( array_keys( WC()->cart->taxes + WC()->cart->shipping_taxes ) as $key ) {
				$code = WC()->cart->tax->get_rate_code( $key );
			
				if ( $code ) {
					$item_id = wc_add_order_item( $order_id, array(
				 		'order_item_name' 		=> $code,
				 		'order_item_type' 		=> 'tax'
				 	) );
			
				 	// Add line item meta
				 	if ( $item_id ) {
				 		wc_add_order_item_meta( $item_id, 'rate_id', $key );
				 		wc_add_order_item_meta( $item_id, 'label', WC()->cart->tax->get_rate_label( $key ) );
					 	wc_add_order_item_meta( $item_id, 'compound', absint( WC()->cart->tax->is_compound( $key ) ? 1 : 0 ) );
					 	wc_add_order_item_meta( $item_id, 'tax_amount', wc_format_decimal( isset( WC()->cart->taxes[ $key ] ) ? WC()->cart->taxes[ $key ] : 0 ) );
					 	wc_add_order_item_meta( $item_id, 'shipping_tax_amount', wc_format_decimal( isset( WC()->cart->shipping_taxes[ $key ] ) ? WC()->cart->shipping_taxes[ $key ] : 0 ) );
					}
				}
			}
			
			// Store coupons
			if ( $applied_coupons = WC()->cart->get_coupons() ) {
				foreach ( $applied_coupons as $code => $coupon ) {
			
					$item_id = wc_add_order_item( $order_id, array(
				 		'order_item_name' 		=> $code,
				 		'order_item_type' 		=> 'coupon'
				 	) );
			
				 	// Add line item meta
				 	if ( $item_id ) {
				 		wc_add_order_item_meta( $item_id, 'discount_amount', isset( WC()->cart->coupon_discount_amounts[ $code ] ) ? WC()->cart->coupon_discount_amounts[ $code ] : 0 );
					}
				}
			}
			
			
			update_post_meta( $order_id, '_payment_method', 		$this->id );
			update_post_meta( $order_id, '_payment_method_title', 	$this->method_title );
			
			
			if ( empty( $this->posted['billing_email'] ) && is_user_logged_in() ) {
				$current_user = wp_get_current_user();
				update_post_meta( $order_id, '_billing_email', $current_user->user_email );
			}
			
			// Customer ID
			if ( is_user_logged_in() ) {
				$current_user = wp_get_current_user();
				update_post_meta( $order_id, '_customer_user', 			absint( $current_user->ID ) );
			}
			
			update_post_meta( $order_id, '_order_shipping', 		wc_format_decimal( WC()->cart->shipping_total ) );
			update_post_meta( $order_id, '_order_discount', 		wc_format_decimal( WC()->cart->get_total_discount() ) );
			update_post_meta( $order_id, '_cart_discount', 			wc_format_decimal( WC()->cart->get_cart_discount_total() ) );
			update_post_meta( $order_id, '_order_tax', 				wc_format_decimal( WC()->cart->tax_total ) );
			update_post_meta( $order_id, '_order_shipping_tax', 	wc_format_decimal( WC()->cart->shipping_tax_total ) );
			update_post_meta( $order_id, '_order_total', 			wc_format_decimal( WC()->cart->total, get_option( 'woocommerce_price_num_decimals' ) ) );
			
			update_post_meta( $order_id, '_order_key', 				'wc_' . apply_filters('woocommerce_generate_order_key', uniqid('order_') ) );
			update_post_meta( $order_id, '_order_currency', 		get_woocommerce_currency() );
			update_post_meta( $order_id, '_prices_include_tax', 	get_option( 'woocommerce_prices_include_tax' ) );
			update_post_meta( $order_id, '_customer_ip_address',	isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'] );
			update_post_meta( $order_id, '_customer_user_agent', 	isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '' );
			
			// Let plugins add meta
			do_action( 'woocommerce_checkout_update_order_meta', $order_id, array() );
			
			// Order status
			wp_set_object_terms( $order_id, 'pending', 'shop_order_status' );
			
			// Update customer shipping and payment method to posted method
			$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
			
			if ( isset( $this->posted['shipping_method'] ) && is_array( $this->posted['shipping_method'] ) )
				foreach ( $this->posted['shipping_method'] as $i => $value )
					$chosen_shipping_methods[ $i ] = wc_clean( $value );
			
			WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );
			WC()->session->set( 'chosen_payment_method', $this->id );

			 return $order_id;
		 
		 } // End function
		 
		 

		/**
		 * Create a new customer
		 *
		 * @param  string $email
		 * @param  string $username
		 * @param  string $password
		 * @return WP_Error on failure, Int (user ID) on success
		*/
		function create_new_customer( $email, $username = '', $password = '' ) {

        	// Check the e-mail address
			if ( empty( $email ) || ! is_email( $email ) )
                return new WP_Error( "registration-error", __( "Please provide a valid email address.", "woocommerce" ) );

			if ( email_exists( $email ) )
                return new WP_Error( "registration-error", __( "An account is already registered with your email address. Please login.", "woocommerce" ) );


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
			$password = wp_generate_password();
			$password_generated = true;
        

			// WP Validation
			$validation_errors = new WP_Error();

			do_action( 'woocommerce_register_post', $username, $email, $validation_errors );

			$validation_errors = apply_filters( 'woocommerce_registration_errors', $validation_errors, $username, $email );

			if ( $validation_errors->get_error_code() )
                return $validation_errors;

			$new_customer_data = apply_filters( 'woocommerce_new_customer_data', array(
            	'user_login' => $username,
				'user_pass'  => $password,
				'user_email' => $email,
				'role'       => 'customer'
			) );

			$customer_id = wp_insert_user( $new_customer_data );

			if ( is_wp_error( $customer_id ) )
            	return new WP_Error( "registration-error", '<strong>' . __( 'ERROR', 'woocommerce' ) . '</strong>: ' . __( 'Couldn&#8217;t register you&hellip; please contact us if you continue to have problems.', 'woocommerce' ) );
		
			// Send New account creation email to customer?
			if( $this->send_new_account_email == 'yes' ) {
	        	do_action( 'woocommerce_created_customer', $customer_id, $new_customer_data, $password_generated );
			}
		
			return $customer_id;
		}

		
		/**
		 * Add ajaxurl var to head
		 */
		function ajaxurl() {
			global $post;
			if( has_shortcode( $post->post_content, 'woocommerce_klarna_checkout_order_note') || defined( 'WOOCOMMERCE_KLARNA_CHECKOUT' ) ) {
				?>
				<script type="text/javascript">
						var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
				</script>
				<?php
			}
		}
	
		/**
	 	 * JS for update the Order note field (via ajax) if displayed on KCO checkout 
	 	 *
	 	**/
		function js_order_note() {
			
			global $post;

			if ( is_singular() ) {
				
				if ( has_shortcode( $post->post_content, 'woocommerce_klarna_checkout_order_note') || defined( 'WOOCOMMERCE_KLARNA_CHECKOUT' ) ) {
				
					?>
					<script type="text/javascript">
					jQuery(document).ready(function($){
						
						jQuery('#kco_order_note').blur(function () {
							var kco_order_note = '';
							
							if( jQuery('#kco_order_note').val() != '' ) {
								var kco_order_note = jQuery('#kco_order_note').val();
							}
							
							if(kco_order_note == '') {
							
							} else {
									
								jQuery.post(
									'<?php echo get_option('siteurl') . '/wp-admin/admin-ajax.php' ?>',
									{
										action			: 'customer_update_kco_order_note',
										kco_order_note	: kco_order_note,
										kco_order_id	: '<?php echo WC()->session->order_awaiting_payment;?>',
										_wpnonce		: '<?php echo wp_create_nonce('update-kco-checkout-order-note'); ?>',
									},
									function(response) {
										console.log(response);
									}
								);
								
							}				
						});
					});
					</script>
					<?php
					
				} // End if has_shortcode()

			} // End if is_singular()
			
		} // End function
		
		
		/**
	     * Function customer_update_kco_order_note
	     * Ajax request callback function
	     * 
	     */
		function customer_update_kco_order_note() {
			
			// The $_REQUEST contains all the data sent via ajax
			if ( isset($_REQUEST) && wp_verify_nonce( $_POST['_wpnonce'], 'update-kco-checkout-order-note' ) ) {
			
				$kco_order_note = sanitize_text_field($_REQUEST['kco_order_note']);
				$kco_order_id = sanitize_text_field($_REQUEST['kco_order_id']);
		
				// Update Order Excerpt (Customer note)
				$my_post = array(
					'ID'           => $kco_order_id,
					'post_excerpt' => $kco_order_note
				);
				
				$response = wp_update_post( $my_post );
				
				echo $response;
			
			} else {
			
				echo '';
			
			}
			
			die(); // this is required to terminate immediately and return a proper response
		} // End function
	
		
		/**
		 * Helper function get_enabled
		 */
		 
		function get_enabled() {
			return $this->enabled;
		}
		
		/**
		 * Helper function get_modify_standard_checkout_url
		 */
		 
		function get_modify_standard_checkout_url() {
			return $this->modify_standard_checkout_url;
		}
	
		/**
		 * Helper function get_klarna_checkout_page
		 */
		function get_klarna_checkout_url() {
	 		return $this->klarna_checkout_url;
	 	}
	 	
	 	
	 	/**
		 * Helper function get_klarna_country
		 */
		function get_klarna_country() {
			return $this->klarna_country;
		} // End function
	 	
		

		/**
		 * Helper function - get authorized countries
		 */
		function get_authorized_countries() {
			$this->authorized_countries		= array();
			if(!empty($this->eid_se)) {
				$this->authorized_countries[] = 'SE';
			}
			if(!empty($this->eid_no)) {
				$this->authorized_countries[] = 'NO';
			}
			if(!empty($this->eid_fi)) {
				$this->authorized_countries[] = 'FI';
			}
			if(!empty($this->eid_de)) {
				$this->authorized_countries[] = 'DE';
			}
			
			return $this->authorized_countries;
		}
		
		
		/**
		 * Helper function - get correct currency for selected country
		 */
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
		
		
		/**
		 * Helper function - get Account Signup Text
		 */
	 	public function get_account_signup_text() {
		 	return $this->account_signup_text;
	 	}
	 	
	 	/**
		 * Helper function - get Account Login Text
		 */
	 	public function get_account_login_text() {
		 	return $this->account_login_text;
	 	}
	 	
	 	
	 /**
	  * Activate the order/reservation in Klarnas system and return the result
	 **/
	function activate_reservation() {
		global $woocommerce;
		$order_id = $_GET['post'];
		$order = WC_Klarna_Compatibility::wc_get_order( $order_id );
		require_once(KLARNA_LIB . 'Klarna.php');
//		require_once(KLARNA_LIB . 'pclasses/storage.intf.php');

		if(!function_exists('xmlrpc_encode_entitites') && !class_exists('xmlrpcresp')) {
			require_once(KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc');
			require_once(KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc');
		}
		
		
		
		// Split address into House number and House extension for NL & DE customers
		if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) :
		
			require_once('split-address.php');
			
			$klarna_billing_address				= $order->billing_address_1;
			$splitted_address 					= splitAddress($klarna_billing_address);
			
			$klarna_billing_address				= $splitted_address[0];
			$klarna_billing_house_number		= $splitted_address[1];
			$klarna_billing_house_extension		= $splitted_address[2];
			
			$klarna_shipping_address			= $order->shipping_address_1;
			$splitted_address 					= splitAddress($klarna_shipping_address);
			
			$klarna_shipping_address			= $splitted_address[0];
			$klarna_shipping_house_number		= $splitted_address[1];
			$klarna_shipping_house_extension	= $splitted_address[2];
		
		else :
			
			$klarna_billing_address				= $order->billing_address_1;
			$klarna_billing_house_number		= '';
			$klarna_billing_house_extension		= '';
			
			$klarna_shipping_address			= $order->shipping_address_1;
			$klarna_shipping_house_number		= '';
			$klarna_shipping_house_extension	= '';
			
		endif;
				
		
		
		
		// Test mode or Live mode		
		if ( $this->testmode == 'yes' ):
			// Disable SSL if in testmode
			$klarna_ssl = 'false';
			$klarna_mode = Klarna::BETA;
		else :
			// Set SSL if used in webshop
			if (is_ssl()) {
				$klarna_ssl = 'true';
			} else {
				$klarna_ssl = 'false';
			}
			$klarna_mode = Klarna::LIVE;
		endif;
			
		$k = new Klarna();
		
		$k->config(
		    $eid = $this->klarna_eid,
		    $secret = $this->klarna_secret,
		    $country = $this->klarna_country,
		    $language = $this->klarna_language,
		    $currency = $this->klarna_currency,
		    $mode = $klarna_mode,
		    $pcStorage = 'json',
		    $pcURI = '/srv/pclasses.json',
		    $ssl = $klarna_ssl,
		    $candice = false
		);
		
		Klarna::$xmlrpcDebug = false;
		Klarna::$debug = false;
		
		// Cart Contents
		if (sizeof($order->get_items())>0) : foreach ($order->get_items() as $item) :
			$_product = $order->get_product_from_item( $item );
			if ($_product->exists() && $item['qty']) :
			
				// We manually calculate the tax percentage here
				if ($order->get_line_tax($item) !==0) :
					// Calculate tax percentage
					$item_tax_percentage = @number_format( ( $order->get_line_tax($item) / $order->get_line_total( $item, false ) )*100, 2, '.', '');
				else :
					$item_tax_percentage = 0.00;
				endif;
				
				// apply_filters to item price so we can filter this if needed
				$klarna_item_price_including_tax = $order->get_item_total( $item, true );
				$item_price = apply_filters( 'klarna_item_price_including_tax', $klarna_item_price_including_tax );
					
					if ( $_product->get_sku() ) {
						$sku = $_product->get_sku();
					} else {
						$sku = $_product->id;
					}
					
					$k->addArticle(
		    		$qty = $item['qty'], 					//Quantity
		    		$artNo = strval($sku),		 					//Article number
		    		$title = utf8_decode ($item['name']), 	//Article name/title
		    		$price = $item_price, 					// Price including tax
		    		$vat = round( $item_tax_percentage ),			// Tax
		    		$discount = 0, 
		    		$flags = KlarnaFlags::INC_VAT 			//Price is including VAT.
				);
									
			endif;
		endforeach; endif;
		 
		// Discount
		if ($order->order_discount>0) :
			
			// apply_filters to order discount so we can filter this if needed
			$klarna_order_discount = $order->order_discount;
			$order_discount = apply_filters( 'klarna_order_discount', $klarna_order_discount );
		
			$k->addArticle(
			    $qty = 1,
			    $artNo = "",
			    $title = __('Discount', 'klarna'),
			    $price = -$order_discount,
			    $vat = 0,
			    $discount = 0,
			    $flags = KlarnaFlags::INC_VAT //Price is including VAT
			);
		endif;
		
		// Shipping
		if (get_total_shipping($order)>0) :
			
			// We manually calculate the shipping tax percentage here
			$calculated_shipping_tax_percentage = ($order->order_shipping_tax/get_total_shipping($order))*100; //25.00
			$calculated_shipping_tax_decimal = ($order->order_shipping_tax/get_total_shipping($order))+1; //0.25
			
			// apply_filters to Shipping so we can filter this if needed
			$klarna_shipping_price_including_tax = get_total_shipping($order)*$calculated_shipping_tax_decimal;
			$shipping_price = apply_filters( 'klarna_shipping_price_including_tax', $klarna_shipping_price_including_tax );
			
			$k->addArticle(
			    $qty = 1,
			    $artNo = "",
			    $title = __('Shipping cost', 'klarna'),
			    $price = $shipping_price,
			    $vat = round( $calculated_shipping_tax_percentage ),
			    $discount = 0,
			    $flags = KlarnaFlags::INC_VAT + KlarnaFlags::IS_SHIPMENT //Price is including VAT and is shipment fee
			);
		endif;
		
		//Create the address object and specify the values.
		
		// Billing address
		$addr_billing = new KlarnaAddr(
    		$email = $order->billing_email,
    		$telno = '', //We skip the normal land line phone, only one is needed.
    		$cellno = $order->billing_phone,
    		//$company = $order->billing_company,
    		$fname = utf8_decode ($order->billing_first_name),
    		$lname = utf8_decode ($order->billing_last_name),
    		$careof = utf8_decode ($order->billing_address_2),  //No care of, C/O.
    		$street = utf8_decode ($klarna_billing_address), //For DE and NL specify street number in houseNo.
    		$zip = utf8_decode ($order->billing_postcode),
    		$city = utf8_decode ($order->billing_city),
    		$country = utf8_decode ($order->billing_country),
    		$houseNo = utf8_decode ($klarna_billing_house_number), //For DE and NL we need to specify houseNo.
    		$houseExt = utf8_decode ($klarna_billing_house_extension) //Only required for NL.
		);
		
		
		// Shipping address
		if ( $order->get_shipping_method() == '' ) {
			
			// Use billing address if Shipping is disabled in Woocommerce
			$addr_shipping = new KlarnaAddr(
    			$email = $order->billing_email,
    			$telno = '', //We skip the normal land line phone, only one is needed.
    			$cellno = $order->billing_phone,
    			//$company = $order->shipping_company,
    			$fname = utf8_decode ($order->billing_first_name),
    			$lname = utf8_decode ($order->billing_last_name),
    			$careof = utf8_decode ($order->billing_address_2),  //No care of, C/O.
    			$street = utf8_decode ($klarna_billing_address), //For DE and NL specify street number in houseNo.
    			$zip = utf8_decode ($order->billing_postcode),
    			$city = utf8_decode ($order->billing_city),
    			$country = utf8_decode ($order->billing_country),
    			$houseNo = utf8_decode ($klarna_billing_house_number), //For DE and NL we need to specify houseNo.
    			$houseExt = utf8_decode ($klarna_billing_house_extension) //Only required for NL.
			);
		
		} else {
		
			$addr_shipping = new KlarnaAddr(
    			$email = $order->billing_email,
    			$telno = '', //We skip the normal land line phone, only one is needed.
    			$cellno = $order->billing_phone,
    			//$company = $order->shipping_company,
    			$fname = utf8_decode ($order->shipping_first_name),
    			$lname = utf8_decode ($order->shipping_last_name),
    			$careof = utf8_decode ($order->shipping_address_2),  //No care of, C/O.
    			$street = utf8_decode ($klarna_shipping_address), //For DE and NL specify street number in houseNo.
    			$zip = utf8_decode ($order->shipping_postcode),
    			$city = utf8_decode ($order->shipping_city),
    			$country = utf8_decode ($order->shipping_country),
    			$houseNo = utf8_decode ($klarna_shipping_house_number), //For DE and NL we need to specify houseNo.
    			$houseExt = utf8_decode ($klarna_shipping_house_extension) //Only required for NL.
			);
		
		}

		
		//Next we tell the Klarna instance to use the address in the next order.
		$k->setAddress(KlarnaFlags::IS_BILLING, $addr_billing); //Billing / invoice address
		$k->setAddress(KlarnaFlags::IS_SHIPPING, $addr_shipping); //Shipping / delivery address

		//Set store specific information so you can e.g. search and associate invoices with order numbers.
		$k->setEstoreInfo(
		    $orderid1 = $order_id, //Maybe the estore's order number/id.
		    $orderid2 = $order->order_key, //Could an order number from another system?
		    $user = '' //Username, email or identifier for the user?
		);
		
		/** Shipment type? **/

		//Normal shipment is defaulted, delays the start of invoice expiration/due-date.
		// $k->setShipmentInfo('delay_adjust', KlarnaFlags::EXPRESS_SHIPMENT);		    
		
		try {
    		//Transmit all the specified data, from the steps above, to Klarna.
    		$result = $k->activateReservation(
    		    null,           // PNO (Date of birth for DE and NL).
    		    get_post_meta( $order_id, 'klarna_order_reservation', true ),           // Reservation to activate
    		    null,           // Gender.
    		    '',             // OCR number to use if you have reserved one.
    		    KlarnaFlags::NO_FLAG, //No specific behaviour like RETURN_OCR or TEST_MODE.
    		    -1 // Get the pclass object that the customer has choosen.
    		);
    		
    		// Retreive response
    		$risk = $result[0]; // ok or no_risk
    		$invno = $result[1];
    		
    		// Invoice created
    		if ($risk == 'ok') {
	    		update_post_meta( $order_id, 'klarna_order_status', 'activated' );
				update_post_meta( $order_id, 'klarna_order_invoice', $invno );
				$order->add_order_note(sprintf(__('Klarna payment reservation has been activated. Invoice number: %s.', 'klarna'), $invno));
    		}
    		echo "risk: {$risk}\ninvno: {$invno}\n";
    		// Reservation is activated, proceed accordingly.
        } catch(Exception $e) {
        	// Something went wrong, print the message:
        	$order->add_order_note(sprintf(__('Klarna reservation activation error: %s. Error code: $e->getCode()', 'klarna'), $e->getMessage(), $e->getCode()));
        }
	
	} // End function activate_reservation
	
	
	/**
		* Add Metaboxes
		*/
		public function add_klarna_meta_box() {
			
			global $boxes;
			global $post;
			
			$order = WC_Klarna_Compatibility::wc_get_order( $post->ID );
			
			// Only on WC orders
			if( get_post_type() != 'shop_order' )
				return;
			
			if( $order->order_custom_fields['_payment_method'][0] == 'klarna_checkout') {
				
				$boxes = apply_filters( 'klarna_boxes', array( 
															'status' => 'Klarna' 
														) );
														
				//Add one Metabox for every $box_id
				foreach ($boxes as $box_id=>$box_label) {
		
					$screens = apply_filters( 'klarna_screens', array( 'shop_order' ) );
					foreach ($screens as $screen) {
						add_meta_box(
							'klarna_' . $box_id,
							__( $box_label, 'klarna' ),
							array( &$this, 'render_meta_box_content' ),
							$screen,
							'normal', //('normal', 'advanced', or 'side')
							'high', //('high', 'core', 'default' or 'low')
							array( 'label' => $box_label, 'id' => $box_id)
						);
        			} // End screen
        	
				} // End box
			} // End if payment method  == klarna_checkout
		} // End function
	
	
	/**
     * Render Meta Box content
     */
    public function render_meta_box_content( $post, $metabox ) {
    
    	$url = admin_url('post.php?post=' . $post->ID . '&action=edit&klarnaActivateReservationListener=1');
    	echo '<a class="button" href="' . $url . '">Activate Reservation</a>';
    	
    	if (isset($_GET['klarnaActivateReservationListener']) && $_GET['klarnaActivateReservationListener'] == '1') {
    		echo '<p>';
	    	$this->activate_reservation();
	    	echo '</p>';
    	}

    
    } // End function render_meta_box_content()    
    
} // End class WC_Gateway_Klarna_Checkout

	
// Extra Class for Klarna Checkout
class WC_Gateway_Klarna_Checkout_Extra {
	
	public function __construct() {
		
		add_action( 'init', array( $this, 'start_session' ),1 );
		add_action( 'before_woocommerce_init', array( $this, 'prevent_caching' ) );
		
		add_shortcode( 'woocommerce_klarna_checkout', array( $this, 'klarna_checkout_page') );
		add_shortcode( 'woocommerce_klarna_checkout_order_note', array( $this, 'klarna_checkout_order_note') );
		
		//add_action( 'woocommerce_proceed_to_checkout', array( &$this, 'checkout_button' ), 12 );
		
		add_filter( 'woocommerce_get_checkout_url', array( $this, 'change_checkout_url' ), 20 );
		
		add_action( 'woocommerce_register_form_start', array( $this, 'add_account_signup_text' ) );
		add_action( 'woocommerce_login_form_start', array( $this, 'add_account_login_text' ) );
		
		// Filter Checkout page ID, so WooCommerce Google Analytics integration can
		// output Ecommerce tracking code on Klarna Thank You page
		add_filter( 'woocommerce_get_checkout_page_id', array( $this, 'change_checkout_page_id' ) );

	}

	// Prevent caching in KCO and KCO thank you pages
	function prevent_caching() {
		$data = new WC_Gateway_Klarna_Checkout;
		$klarna_checkout_url = trailingslashit( $data->klarna_checkout_url );

		global $klarna_checkout_thanks_url;
		$klarna_checkout_thanks_url = trailingslashit( $klarna_checkout_thanks_url );

		// Clean request URI to remove all parameters
		$clean_req_uri = explode( '?', $_SERVER['REQUEST_URI'] );
		$clean_req_uri = $clean_req_uri[0];
		$clean_req_uri = trailingslashit( $clean_req_uri );

		$length = strlen( $clean_req_uri );

		// Get last $length characters from KCO and KCO thank you URLs
		$klarna_checkout_compare = substr( $klarna_checkout_url, 0 - $length );
		$klarna_checkout_compare = trailingslashit( $klarna_checkout_compare );

		$klarna_checkout_thanks_compare = substr( $klarna_checkout_thanks_url, 0 - $length );
		$klarna_checkout_thanks_compare = trailingslashit( $klarna_checkout_thanks_compare );

		if ( $clean_req_uri == $klarna_checkout_compare || $clean_req_uri == $klarna_checkout_thanks_compare ) {
			// Prevent caching
			if ( ! defined( 'DONOTCACHEPAGE' ) )
				define( "DONOTCACHEPAGE", "true" );
			if ( ! defined( 'DONOTCACHEOBJECT' ) )
				define( "DONOTCACHEOBJECT", "true" );
			if ( ! defined( 'DONOTCACHEDB' ) )
				define( "DONOTCACHEDB", "true" );

			nocache_headers();
		}
	}	
	
	// Set session
	function start_session() {		
		
		$data = new WC_Gateway_Klarna_Checkout;
		$enabled = $data->get_enabled();
		
    	if(!session_id() && $enabled == 'yes') {
        	session_start();
        }
    }
    
	// Shortcode KCO page
	function klarna_checkout_page() {
		$data = new WC_Gateway_Klarna_Checkout;
		return '<div class="klarna_checkout">' . $data->get_klarna_checkout_page() . '</div>';
	}
	
	// Shortcode Order note
	function klarna_checkout_order_note() {
		global $woocommerce;
    	$field = array(
			'type'              => 'textarea',
			'label'             => __( 'Order Notes', 'woocommerce' ),
			'placeholder'       => _x('Notes about your order, e.g. special notes for delivery.', 'placeholder', 'woocommerce'),
			'class'             => array('notes'),
		);
		
		ob_start();
		
		echo '<div class="woocommerce"><form>';
    	woocommerce_form_field( 'kco_order_note', $field );
		echo '</form></div>';

		return ob_get_clean();
    	
	}
	
	
	/**
	 *  Change Checkout URL
	 *
	 *  Triggered from the 'woocommerce_get_checkout_url' action.
	 *  Alter the checkout url to the custom Klarna Checkout Checkout page.
	 *
	 **/
	 
	function change_checkout_url( $url ) {
		global $woocommerce;
		$data = new WC_Gateway_Klarna_Checkout;
		$enabled = $data->get_enabled();
		$klarna_checkout_url = $data->get_klarna_checkout_url();
		$modify_standard_checkout_url = $data->get_modify_standard_checkout_url();
		$klarna_country = $data->get_klarna_country();
		$available_countries = $data->get_authorized_countries();

		// Change the Checkout URL if this is enabled in the settings
		if( $modify_standard_checkout_url == 'yes' && $enabled == 'yes' && !empty($klarna_checkout_url) && in_array($klarna_country, $available_countries)) {
			$url = $klarna_checkout_url;
		}
		
		return $url;
	}
	
	/**
	 *  Function Add Account signup text
	 *
	 *  @since version 1.8.9
	 * 	Add text above the Account Registration Form. 
	 *  Useful for legal text for German stores. See documentation for more information. Leave blank to disable.
	 *
	 **/
	 
	public function add_account_signup_text() {
		global $woocommerce;
		$data = new WC_Gateway_Klarna_Checkout;
		$account_signup_text = '';
		$account_signup_text = $data->get_account_signup_text();
		

		// Change the Checkout URL if this is enabled in the settings
		if( !empty($account_signup_text) ) {
			echo $account_signup_text;
		}

	}
	
	
	/**
	 *  Function Add Account login text
	 *
	 *  @since version 1.8.9
	 * 	Add text above the Account Login Form. 
	 *  Useful for legal text for German stores. See documentation for more information. Leave blank to disable.
	 *
	 **/

	 
	public function add_account_login_text() {
		global $woocommerce;
		$data = new WC_Gateway_Klarna_Checkout;
		$account_login_text = '';
		$account_login_text = $data->get_account_login_text();
		
	
		// Change the Checkout URL if this is enabled in the settings
		if( !empty($account_login_text) ) {
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
		

} // End class WC_Gateway_Klarna_Checkout_Extra

$wc_klarna_checkout_extra = new WC_Gateway_Klarna_Checkout_Extra;