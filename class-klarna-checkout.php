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
       	$this->log 								= $woocommerce->logger();
       	
       	
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
       	
       	$this->terms_url						= ( isset( $this->settings['terms_url'] ) ) ? $this->settings['terms_url'] : '';
       	$this->testmode							= ( isset( $this->settings['testmode'] ) ) ? $this->settings['testmode'] : '';
       	$this->debug							= ( isset( $this->settings['debug'] ) ) ? $this->settings['debug'] : '';
       	//$this->klarna_checkout_url			= ( isset( $this->settings['klarna_checkout_url'] ) ) ? $this->settings['klarna_checkout_url'] : '';
       	$this->modify_standard_checkout_url		= ( isset( $this->settings['modify_standard_checkout_url'] ) ) ? $this->settings['modify_standard_checkout_url'] : '';
       	$this->add_std_checkout_button			= ( isset( $this->settings['add_std_checkout_button'] ) ) ? $this->settings['add_std_checkout_button'] : '';
       	$this->std_checkout_button_label		= ( isset( $this->settings['std_checkout_button_label'] ) ) ? $this->settings['std_checkout_button_label'] : '';
       	
		if ( empty($this->terms_url) ) 
			$this->terms_url = esc_url( get_permalink(woocommerce_get_page_id('terms')) );
        	
       	// Check if this is test mode or not
		if ( $this->testmode == 'yes' ):
			$this->klarna_server = 'https://checkout.testdrive.klarna.com/checkout/orders';	
		else :
			$this->klarna_server = 'https://checkout.klarna.com/checkout/orders';
		endif;
		
		/*
		// Analytics
		$analytics 								= '';
		$analytics 								= get_option( 'woocommerce_google_analytics_settings' );
		$this->ga_id 							= $analytics['ga_id'];
		$this->ga_set_domain_name               = $analytics['ga_set_domain_name'];
		$this->ga_standard_tracking_enabled 	= $analytics['ga_standard_tracking_enabled'];
		$this->ga_ecommerce_tracking_enabled 	= $analytics['ga_ecommerce_tracking_enabled'];
		$this->ga_event_tracking_enabled		= $analytics['ga_event_tracking_enabled'];
		*/
		
		// Country and language
		switch ( $this->shop_country )
		{
		/*
		case 'DK':
			$klarna_country = 'DK';
			$klarna_language = 'DA';
			$klarna_currency = 'DKK';
			break;
		case 'DE' :
			$klarna_country = 'DE';
			$klarna_language = 'DE';
			$klarna_currency = 'EUR';
			break;
		case 'NL' :
			$klarna_country = 'NL';
			$klarna_language = 'NL';
			$klarna_currency = 'EUR';
			break;
		*/
		case 'NO' :
		case 'NB' :
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
			$klarna_country 			= 'FI';
			$klarna_language 			= 'fi-fi';
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
		$this->klarna_country 		= apply_filters( 'klarna_country', $klarna_country );
		$this->klarna_language 		= apply_filters( 'klarna_language', $klarna_language );
		$this->klarna_currency 		= apply_filters( 'klarna_currency', $klarna_currency );
		$this->klarna_eid			= apply_filters( 'klarna_eid', $klarna_eid );
       	$this->klarna_secret		= apply_filters( 'klarna_secret', $klarna_secret );
       	$this->klarna_checkout_url	= apply_filters( 'klarna_checkout_url', $klarna_checkout_url );
       	$this->klarna_checkout_thanks_url	= apply_filters( 'klarna_checkout_thanks_url', $klarna_checkout_thanks_url );
		
       	/* 1.6.6 */
		add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
 
		/* 2.0.0 */
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
       	
       	add_action( 'woocommerce_api_wc_gateway_klarna_checkout', array($this, 'check_checkout_listener') );
       	
       	//add_action( 'add_meta_boxes', array( $this, 'add_klarna_meta_box' ) );
       	
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
/*
			'klarna_checkout_url' => array(
							'title' => __( 'Custom Checkout Page', 'klarna' ), 
							'type' => 'text', 
							'description' => __( 'Please enter the URL to the page that acts as Checkout Page for Klarna Checkout. This page must contain the shortcode [woocommerce_klarna_checkout].', 'klarna' ), 
							'default' => ''
						),
*/
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
			
			ob_start();
			require_once 'src/Klarna/Checkout.php';
			ob_end_clean();
			
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
				
				//$checkoutId = $_SESSION['klarna_checkout'];	
				
				$klarna_order = new Klarna_Checkout_Order($connector, $orderUri);
				
				$klarna_order->fetch();  
				
				
				if ($klarna_order['status'] == 'checkout_incomplete') {
				
					//echo "Checkout not completed, redirect to checkout.php";
					wp_redirect( $this->klarna_checkout_url );
					exit;  
				}
				
				// Analytics eCommerce tracking
				//$this->ecommerce_tracking_code( $_GET['sid'] );
				$data = new WC_Google_Analytics;
				$data->ecommerce_tracking_code($_GET['sid']);
				
				$snippet = $klarna_order['gui']['snippet'];
			
				// DESKTOP: Width of containing block shall be at least 750px
				// MOBILE: Width of containing block shall be 100% of browser window (No
				// padding or margin)
				echo '<div>' . $snippet . '</div>';	
				
				unset($_SESSION['klarna_checkout']);
				
				// Remove cart
				$woocommerce->cart->empty_cart();
				
				
			
			} else {
				
				// Don't render the Klarna Checkout form if the payment gateway isn't enabled.
				if ($this->enabled != 'yes' || $this->klarna_country == '' || $this->klarna_eid == '') return;
				
				
				// If checkout registration is disabled and not logged in, the user cannot checkout
				$checkout = $woocommerce->checkout();
				if ( ! $checkout->enable_guest_checkout && ! is_user_logged_in() ) {
					//do_action( 'woocommerce_login_form' );
					echo apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) );
					return;
				}
				
				// Process order via Klarna Checkout page				

				if ( !defined( 'WOOCOMMERCE_CHECKOUT' ) ) define( 'WOOCOMMERCE_CHECKOUT', true );
				
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
        			
        			
        			// Get an instance of the created order
        			$order = new WC_Order( $order_id );
									
        			$cart = array();
        			
        			// Cart Contents
        			if ( sizeof( $order->get_items() ) > 0 ) {
						foreach ( $order->get_items() as $item ) {
							if ( $item['qty'] ) {
								$_product = $order->get_product_from_item( $item );	
								
								// We manually calculate the tax percentage here
								if ( $_product->is_taxable() && $order->get_line_tax($item)>0 ) {
									// Calculate tax percentage
									$item_tax_percentage = (int)number_format( ( $order->get_line_tax($item) / $order->get_line_total( $item, false ) )*100, 2, '', '');
								} else {
									$item_tax_percentage = 00;
								}
				
								$item_name 	= $item['name'];
								
								$item_meta = new WC_Order_Item_Meta( $item['item_meta'] );
								if ( $meta = $item_meta->display( true, true ) )
									$item_name .= ' ( ' . $meta . ' )';
									
								// apply_filters to item price so we can filter this if needed
								$klarna_item_price_including_tax = $order->get_item_total( $item, true );
								$item_price = apply_filters( 'klarna_item_price_including_tax', $klarna_item_price_including_tax );
							
								
								// Get SKU or product id
								$reference = '';
								if ( $_product->get_sku() ) {
									$reference = $_product->get_sku();
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
											'tax_rate' => $item_tax_percentage
										);
									
							} // End if qty
						
						} // End foreach
					
					} // End if sizeof get_items()
				
				
					// Shipping
					if( $woocommerce->cart->shipping_total > 0 ) {
						
						// We manually calculate the tax percentage here
						if ($woocommerce->cart->shipping_tax_total > 0) {
							// Calculate tax percentage
							$shipping_tax_percentage = (int)number_format( ( $woocommerce->session->shipping_tax_total / $woocommerce->session->shipping_total )*100, 2, '', '');
						} else {
							$shipping_tax_percentage = 00;
						}
					
						$shipping_price = number_format( ($woocommerce->cart->shipping_total+$woocommerce->cart->shipping_tax_total)*100, 0, '', '');
					
						$cart[] = array(  
						 	'type' => 'shipping_fee',  
						 	'reference' => 'SHIPPING',  
						 	'name' => $woocommerce->cart->shipping_label,  
						 	'quantity' => 1,  
						 	'unit_price' => (int)$shipping_price,  
						 	'tax_rate' => $shipping_tax_percentage  
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
    			
					if (array_key_exists('klarna_checkout', $_SESSION)) {
				
						// Resume session
						$klarna_order = new Klarna_Checkout_Order(
							$connector,
							$_SESSION['klarna_checkout']
						);
					
		
						try {
       						$klarna_order->fetch();
       						
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
							$update['merchant']['checkout_uri'] = add_query_arg( 'klarnaListener', 'checkout', $this->klarna_checkout_url );
	        			
        					$update['merchant']['confirmation_uri'] = add_query_arg ( array('klarna_order' => '{checkout.order.uri}', 'sid' => $order_id ), $this->klarna_checkout_thanks_url);
        					$update['merchant']['push_uri'] = add_query_arg( array('sid' => $order_id, 'klarna_order' => '{checkout.order.uri}', 'wc-api' => 'WC_Gateway_Klarna_Checkout'), $this->klarna_checkout_url );
        					
        					//$update['gui']['layout'] = $klarna_checkout_layout;
        					
        					$klarna_order->update($update);
        				} catch (Exception $e) {
        					// Reset session
        					$klarna_order = null;
        					unset($_SESSION['klarna_checkout']);
        				}
        			}
        	
        			if ($klarna_order == null) {
						
	        			// Start new session
	        			$create['purchase_country'] = $this->klarna_country;
	        			$create['purchase_currency'] = $this->klarna_currency;
	        			$create['locale'] = $this->klarna_language;
	        			$create['merchant']['id'] = $eid;
	        			$create['merchant']['terms_uri'] = $this->terms_url;
	        			$create['merchant']['checkout_uri'] = add_query_arg( 'klarnaListener', 'checkout', $this->klarna_checkout_url );
	        			$create['merchant']['confirmation_uri'] = add_query_arg ( array('klarna_order' => '{checkout.order.uri}', 'sid' => $order_id ), $this->klarna_checkout_thanks_url);
	        			$create['merchant']['push_uri'] = add_query_arg( array('sid' => $order_id, 'klarna_order' => '{checkout.order.uri}', 'wc-api' => 'WC_Gateway_Klarna_Checkout'), $this->klarna_checkout_url );
	        			
	        			$create['gui']['layout'] = $klarna_checkout_layout;
	        			
	        			foreach ($cart as $item) {
		        			$create['cart']['items'][] = $item;
		        		}
		        		
		        		
		        		$klarna_order = new Klarna_Checkout_Order($connector);
		        		$klarna_order->create($create);
		        		$klarna_order->fetch();
		        	}
					
					
		        	
		        	// Store location of checkout session
		        	$_SESSION['klarna_checkout'] = $sessionId = $klarna_order->getLocation();
		        	
		        	// Display checkout
		        	$snippet = $klarna_order['gui']['snippet'];
		        	echo '<div>' . $snippet . '</div>';
		        	
		        	
    		    		
		        } // End if sizeof cart 

		    } // End if isset($_GET['klarna_order'])
    	
    	} // End Function
    	
    	
    	
    	/**
	     * Order confirmation via IPN
	     */
		
	    function check_checkout_listener() {
			if (isset($_GET['klarna_order'])) {
				global $woocommerce;
				
				if ($this->debug=='yes') $this->log->add( 'klarna', 'Response from Klarna...' );
				
				require_once 'src/Klarna/Checkout.php';  
  
				//@session_start();  
				
				Klarna_Checkout_Order::$contentType = "application/vnd.klarna.checkout.aggregated-order-v2+json";  
				
				$connector = Klarna_Checkout_Connector::create($this->klarna_secret);  
				
				$checkoutId = $_GET['klarna_order'];  
				$klarna_order = new Klarna_Checkout_Order($connector, $checkoutId);  
				$klarna_order->fetch();  

				if ($this->debug=='yes') :
					$this->log->add( 'klarna', 'ID: ' . $klarna_order['id'] . '\r\n');
					$this->log->add( 'klarna', 'Billing: ' . $klarna_order['billing_address']['given_name'] . '\r\n');
					$this->log->add( 'klarna', 'Order ID: ' . $_GET['sid']);
					$this->log->add( 'klarna', 'Reference: ' . $klarna_order['reservation']);
				endif;
				
				if ($klarna_order['status'] == "checkout_complete") {  
							
					$order_id = $_GET['sid'];
					$order = new WC_Order( $_GET['sid'] );
					
					// Add customer billing address - retrieved from callback from Klarna
					update_post_meta( $order_id, '_billing_first_name', $klarna_order['billing_address']['given_name'] );
					update_post_meta( $order_id, '_billing_last_name', $klarna_order['billing_address']['family_name'] );
					update_post_meta( $order_id, '_billing_address_1', $klarna_order['billing_address']['street_address'] );
					update_post_meta( $order_id, '_billing_address_2', $klarna_order['billing_address']['care_of'] );
					update_post_meta( $order_id, '_billing_postcode', $klarna_order['billing_address']['postal_code'] );
					update_post_meta( $order_id, '_billing_city', $klarna_order['billing_address']['city'] );
					update_post_meta( $order_id, '_billing_country', $klarna_order['billing_address']['country'] );
					update_post_meta( $order_id, '_billing_email', $klarna_order['billing_address']['email'] );
					update_post_meta( $order_id, '_billing_phone', $klarna_order['billing_address']['phone'] );
					
					// Add customer shipping address - retrieved from callback from Klarna
					update_post_meta( $order_id, '_shipping_first_name', $klarna_order['billing_address']['given_name'] );
					update_post_meta( $order_id, '_shipping_last_name', $klarna_order['billing_address']['family_name'] );
					update_post_meta( $order_id, '_shipping_address_1', $klarna_order['billing_address']['street_address'] );
					update_post_meta( $order_id, '_shipping_address_2', $klarna_order['billing_address']['care_of'] );
					update_post_meta( $order_id, '_shipping_postcode', $klarna_order['billing_address']['postal_code'] );
					update_post_meta( $order_id, '_shipping_city', $klarna_order['billing_address']['city'] );
					update_post_meta( $order_id, '_shipping_country', $klarna_order['billing_address']['country'] );
					
					// Store user id in order so the user can keep track of track it in My account
					if( email_exists( $klarna_order['billing_address']['email'] )) {
						$user = get_user_by('email', $klarna_order['billing_address']['email']);
						$this->customer_id = $user->ID;
					} else {
						// $this->customer_id = get_current_user_id();
						$this->customer_id = 0;
					}		
					update_post_meta( $order_id, '_customer_user', 			absint( $this->customer_id ) );
					
					$order->add_order_note(sprintf(__('Klarna Checkout payment completed. Reservation number: %s.  Klarna order number: %s', 'klarna'), $klarna_order['reservation'], $klarna_order['id']));
					
					// Update the order in Klarnas system
					$update['status'] = 'created';
					$update['merchant_reference'] = array(  
														'orderid1' => $order->get_order_number()
													);  
					$klarna_order->update($update);
					
					
					
					// Payment complete
					update_post_meta( $order_id, 'klarna_order_status', 'created' );
					update_post_meta( $order_id, 'klarna_order_reservation', $klarna_order['reservation'] );
					$order->payment_complete();
					
					// Remove cart
					$woocommerce->cart->empty_cart();
					
					
				}
			
			} // Endif klarnaListener == checkout
		
		} // End function check_checkout_listener
		
		
		/**
		 * Create new order
		 */
		public function create_order() {
    
			global $woocommerce, $wpdb;
			
			$order_id = "";
			

			if ( sizeof( $woocommerce->cart->get_cart() ) == 0 )
				$woocommerce->add_error( sprintf( __( 'Sorry, your session has expired. <a href="%s">Return to homepage &rarr;</a>', 'klarna' ), home_url() ) );
				
				
			// Recheck cart items so that they are in stock
			$result = $woocommerce->cart->check_cart_item_stock();
			if( is_wp_error($result) ) {
				return $result->get_error_message();
				exit();
			}
			
			// Update cart totals
			$woocommerce->cart->calculate_totals();
		
			// Create Order (send cart variable so we can record items and reduce inventory). Only create if this is a new order, not if the payment was rejected last time.
			$order_data = apply_filters( 'woocommerce_new_order_data', array(
				'post_type' 	=> 'shop_order',
				'post_title' 	=> sprintf( __( 'Order &ndash; %s', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'woocommerce' ) ) ),
				'post_status' 	=> 'publish',
				'ping_status'	=> 'closed',
				'post_excerpt' 	=> '',
				'post_author' 	=> 1,
				'post_password'	=> uniqid( 'order_' )	// Protects the post just in case
			) );

			// Insert or update the post data
			$create_new_order = true;
			
			if ( $woocommerce->session->order_awaiting_payment > 0 ) {

				$order_id = absint( $woocommerce->session->order_awaiting_payment );
				
				/* Check order is unpaid by getting its status */
				$terms = wp_get_object_terms( $order_id, 'shop_order_status', array( 'fields' => 'slugs' ) );
				$order_status = isset( $terms[0] ) ? $terms[0] : 'pending';
				
				// Resume the unpaid order if its pending
				if ( $order_status == 'pending' ) {
					
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
				$order_id = wp_insert_post( $order_data );
				
				if ( is_wp_error( $order_id ) )
					throw new MyException( 'Error: Unable to create order. Please try again.' );
				else
					do_action( 'woocommerce_new_order', $order_id );
			}
		
			
			// Add Cart items
			
			foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
			
				$_product = $values['data'];
				
				// Add line item
				$item_id = woocommerce_add_order_item( $order_id, array(
		 			'order_item_name' 		=> $_product->get_title(),
		 			'order_item_type' 		=> 'line_item'
		 		) );
		 		
		 		$klarna_product = get_product($values['product_id']);
		 		
		 		$klarna_price_inc_tax = $klarna_product->get_price_including_tax()*$values['quantity'];
		 		$klarna_price_ex_tax = $klarna_product->get_price_excluding_tax()*$values['quantity'];
		 		$klarna_tax = $klarna_price_inc_tax - $klarna_price_ex_tax;
		 		
		 		// Add line item meta
		 		if ( $item_id ) {
		 		/*
				 	woocommerce_add_order_item_meta( $item_id, '_qty', apply_filters( 'woocommerce_stock_amount', $values['quantity'] ) );
				 	woocommerce_add_order_item_meta( $item_id, '_tax_class', $_product->get_tax_class() );
				 	woocommerce_add_order_item_meta( $item_id, '_product_id', $values['product_id'] );
				 	woocommerce_add_order_item_meta( $item_id, '_variation_id', $values['variation_id'] );
				 	
				 	woocommerce_add_order_item_meta( $item_id, '_line_subtotal', woocommerce_format_decimal( $klarna_price_ex_tax ) );
				 	woocommerce_add_order_item_meta( $item_id, '_line_total', woocommerce_format_decimal( $klarna_price_ex_tax ) );
				 	woocommerce_add_order_item_meta( $item_id, '_line_tax', woocommerce_format_decimal( $klarna_tax, 4 ) );
				 	woocommerce_add_order_item_meta( $item_id, '_line_subtotal_tax', woocommerce_format_decimal( $klarna_tax, 4 ) );
				 	*/
				 	
				 	
				 	woocommerce_add_order_item_meta( $item_id, '_qty', apply_filters( 'woocommerce_stock_amount', $values['quantity'] ) );
				 	woocommerce_add_order_item_meta( $item_id, '_tax_class', $_product->get_tax_class() );
				 	woocommerce_add_order_item_meta( $item_id, '_product_id', $values['product_id'] );
				 	woocommerce_add_order_item_meta( $item_id, '_variation_id', $values['variation_id'] );
				 	woocommerce_add_order_item_meta( $item_id, '_line_subtotal', woocommerce_format_decimal( $values['line_subtotal'], 4 ) );
				 	woocommerce_add_order_item_meta( $item_id, '_line_total', woocommerce_format_decimal( $values['line_total'], 4 ) );
				 	woocommerce_add_order_item_meta( $item_id, '_line_tax', woocommerce_format_decimal( $values['line_tax'], 4 ) );
				 	woocommerce_add_order_item_meta( $item_id, '_line_subtotal_tax', woocommerce_format_decimal( $values['line_subtotal_tax'], 4 ) );
			 	
			 	
				 	// Store variation data in meta so admin can view it
				 	if ( $values['variation'] && is_array( $values['variation'] ) )
						foreach ( $values['variation'] as $key => $value )
							woocommerce_add_order_item_meta( $item_id, esc_attr( str_replace( 'attribute_', '', $key ) ), $value );

					// Add line item meta for backorder status
					if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $values['quantity'] ) )
			 			woocommerce_add_order_item_meta( $item_id, __( 'Backordered', 'woocommerce' ), $values['quantity'] - max( 0, $_product->get_total_stock() ) );

			 		//allow plugins to add order item meta
			 		do_action( 'woocommerce_add_order_item_meta', $item_id, $values );
			 	}
			 }

			 // Store fees
			 foreach ( $woocommerce->cart->get_fees() as $fee ) {
				 $item_id = woocommerce_add_order_item( $order_id, array(
				 	'order_item_name' 		=> $fee->name,
				 	'order_item_type' 		=> 'fee'
				 ) );

				if ( $fee->taxable )
		 			woocommerce_add_order_item_meta( $item_id, '_tax_class', $fee->tax_class );
		 		else
		 			woocommerce_add_order_item_meta( $item_id, '_tax_class', '0' );

		 		woocommerce_add_order_item_meta( $item_id, '_line_total', woocommerce_format_decimal( $fee->amount ) );
		 		woocommerce_add_order_item_meta( $item_id, '_line_tax', woocommerce_format_decimal( $fee->tax ) );
		 	}

		 	// Store tax rows
		 	foreach ( array_keys( $woocommerce->cart->taxes + $woocommerce->cart->shipping_taxes ) as $key ) {

				$item_id = woocommerce_add_order_item( $order_id, array(
			 		'order_item_name' 		=> $woocommerce->cart->tax->get_rate_code( $key ),
			 		'order_item_type' 		=> 'tax'
			 	) );

			 	// Add line item meta
			 	if ( $item_id ) {
		 			woocommerce_add_order_item_meta( $item_id, 'rate_id', $key );
		 			woocommerce_add_order_item_meta( $item_id, 'label', $woocommerce->cart->tax->get_rate_label( $key ) );
		 			woocommerce_add_order_item_meta( $item_id, 'compound', absint( $woocommerce->cart->tax->is_compound( $key ) ? 1 : 0 ) );
		 			woocommerce_add_order_item_meta( $item_id, 'tax_amount', woocommerce_clean( isset( $woocommerce->cart->taxes[ $key ] ) ? $woocommerce->cart->taxes[ $key ] : 0 ) );
		 			woocommerce_add_order_item_meta( $item_id, 'shipping_tax_amount', woocommerce_clean( isset( $woocommerce->cart->shipping_taxes[ $key ] ) ? $woocommerce->cart->shipping_taxes[ $key ] : 0 ) );
		 		}
		 	}

		 	// Store coupons
		 	if ( $applied_coupons = $woocommerce->cart->get_applied_coupons() ) {
				foreach ( $applied_coupons as $code ) {
					
					$item_id = woocommerce_add_order_item( $order_id, array(
			 			'order_item_name' 		=> $code,
			 			'order_item_type' 		=> 'coupon'
			 		) );

			 		// Add line item meta
			 		if ( $item_id ) {
			 			woocommerce_add_order_item_meta( $item_id, 'discount_amount', isset( $woocommerce->cart->coupon_discount_amounts[ $code ] ) ? $woocommerce->cart->coupon_discount_amounts[ $code ] : 0 );
			 		}
				}
			}

			// Store meta
			 
			if ( $woocommerce->session->shipping_total ) {
				$shipping_method_id = strtolower(str_replace(' ', '_', $woocommerce->session->shipping_label));
				update_post_meta( $order_id, '_shipping_method', 		$shipping_method_id );
				update_post_meta( $order_id, '_shipping_method_title', 	$woocommerce->session->shipping_label );
			}
		
		
			update_post_meta( $order_id, '_payment_method', 		$this->id );
			update_post_meta( $order_id, '_payment_method_title', 	$this->method_title );
		
			update_post_meta( $order_id, '_order_shipping', 		woocommerce_format_total( $woocommerce->cart->shipping_total ) );
			update_post_meta( $order_id, '_order_discount', 		woocommerce_format_total( $woocommerce->cart->get_order_discount_total() ) );
			update_post_meta( $order_id, '_cart_discount', 			woocommerce_format_total( $woocommerce->cart->get_cart_discount_total() ) );
			update_post_meta( $order_id, '_order_tax', 				woocommerce_format_total( $woocommerce->cart->tax_total ) );
			update_post_meta( $order_id, '_order_shipping_tax', 	woocommerce_format_total( $woocommerce->cart->shipping_tax_total ) );
			update_post_meta( $order_id, '_order_total', 			woocommerce_format_total( $woocommerce->cart->total ) );
			//update_post_meta( $order_id, '_order_total', 			woocommerce_format_total( $woocommerce->cart->subtotal ) );
			update_post_meta( $order_id, '_order_key', 				apply_filters('woocommerce_generate_order_key', uniqid('order_') ) );
			//update_post_meta( $order_id, '_customer_user', 			absint( $this->customer_id ) );
			update_post_meta( $order_id, '_order_currency', 		get_woocommerce_currency() );
			update_post_meta( $order_id, '_prices_include_tax', 	get_option( 'woocommerce_prices_include_tax' ) );
			update_post_meta( $order_id, '_customer_ip_address',	isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'] );
			update_post_meta( $order_id, '_customer_user_agent', 	isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '' );

			// Let plugins add meta
			// do_action( 'woocommerce_checkout_update_order_meta', $order_id, $this->posted );
			
			// Order status
			wp_set_object_terms( $order_id, 'pending', 'shop_order_status' );
			
			return $order_id;
		
		} // End function create_order()
		
		
		
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
	 	}
	 	
		
	
	 	
	 	
	 /**
	 * Activate the order/reservation in Klarnas system and return the result
	 **/
	function activate_reservation() {
		global $woocommerce;
		$order_id = $_GET['post'];
		$order = new WC_order( $order_id );
		require_once(KLARNA_LIB . 'Klarna.php');
//		require_once(KLARNA_LIB . 'pclasses/storage.intf.php');
		require_once(KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc');
		require_once(KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc');
		
		
		
		
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
		if ($order->order_shipping>0) :
			
			// We manually calculate the shipping tax percentage here
			$calculated_shipping_tax_percentage = ($order->order_shipping_tax/$order->order_shipping)*100; //25.00
			$calculated_shipping_tax_decimal = ($order->order_shipping_tax/$order->order_shipping)+1; //0.25
			
			// apply_filters to Shipping so we can filter this if needed
			$klarna_shipping_price_including_tax = $order->order_shipping*$calculated_shipping_tax_decimal;
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
			
			$order = new WC_order( $post->ID );
			
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
		
		add_action('init', array( &$this, 'start_session' ), 1);
		
		add_shortcode( 'woocommerce_klarna_checkout', array(&$this, 'klarna_checkout_page') );
		
		//add_action( 'woocommerce_proceed_to_checkout', array( &$this, 'checkout_button' ), 12 );
		
		add_filter( 'woocommerce_get_checkout_url', array( &$this, 'change_checkout_url' ), 20 );
		
		
		 
	}
	
	// Set session
	function start_session() {		
		
		$data = new WC_Gateway_Klarna_Checkout;
		$enabled = $data->get_enabled();
		
    	if(!session_id() && $enabled == 'yes') {
        	session_start();
        }
    }
	
	// Shortcode
	function klarna_checkout_page() {
		
		$data = new WC_Gateway_Klarna_Checkout;
		$data->get_klarna_checkout_page();
		
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
		$available_countries = array('NO', 'FI', 'SE');
		// Change the Checkout URL if this is enabled in the settings
		if( $modify_standard_checkout_url == 'yes' && $enabled == 'yes' && in_array($klarna_country, $available_countries)) {
			$url = $klarna_checkout_url;
		}
		return $url;
	}
		

} // End class WC_Gateway_Klarna_Checkout_Extra

$wc_klarna_checkout_extra = new WC_Gateway_Klarna_Checkout_Extra;