<?
class WC_Gateway_Klarna_Checkout extends WC_Gateway_Klarna {
			
	public function __construct() { 
		global $woocommerce;
		
		parent::__construct();
        
		$this->shop_country	= get_option('woocommerce_default_country');
       	
       	$this->id			= 'klarna_checkout';
       	$this->method_title = __('Klarna Checkout', 'klarna');
       	$this->has_fields 	= false;
       	
       	// Load the form fields.
       	$this->init_form_fields();
				
       	// Load the settings.
       	$this->init_settings();
       	
       	
       	// Define user set variables
       	$this->enabled						= ( isset( $this->settings['enabled'] ) ) ? $this->settings['enabled'] : '';
       	$this->title 						= ( isset( $this->settings['title'] ) ) ? $this->settings['title'] : '';
       	$this->log 							= $woocommerce->logger();
       	$this->eid							= ( isset( $this->settings['eid'] ) ) ? $this->settings['eid'] : '';
       	$this->secret						= ( isset( $this->settings['secret'] ) ) ? $this->settings['secret'] : '';
       	$this->terms_url					= ( isset( $this->settings['terms_url'] ) ) ? $this->settings['terms_url'] : '';
       	$this->testmode						= ( isset( $this->settings['testmode'] ) ) ? $this->settings['testmode'] : '';
       	$this->debug						= ( isset( $this->settings['debug'] ) ) ? $this->settings['debug'] : '';
       	$this->klarna_checkout_url			= ( isset( $this->settings['klarna_checkout_url'] ) ) ? $this->settings['klarna_checkout_url'] : '';
       	$this->modify_standard_checkout_url	= ( isset( $this->settings['modify_standard_checkout_url'] ) ) ? $this->settings['modify_standard_checkout_url'] : '';
       	$this->add_std_checkout_button		= ( isset( $this->settings['add_std_checkout_button'] ) ) ? $this->settings['add_std_checkout_button'] : '';
       	$this->std_checkout_button_label	= ( isset( $this->settings['std_checkout_button_label'] ) ) ? $this->settings['std_checkout_button_label'] : '';
       	
		if ( empty($this->terms_url) ) 
			$this->terms_url = esc_url( get_permalink(woocommerce_get_page_id('terms')) );
        	
       	// Check if this is test mode or not
		if ( $this->testmode == 'yes' ):
			$this->klarna_server = 'https://checkout.testdrive.klarna.com/checkout/orders';	
		else :
			$this->klarna_server = 'https://checkout.klarna.com/checkout/orders';
		endif;
		
       	/* 1.6.6 */
		add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
 
		/* 2.0.0 */
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
       	
       	add_action( 'woocommerce_api_wc_gateway_klarna_checkout', array($this, 'check_checkout_listener') );
       	
       	
       	
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
			'eid' => array(
							'title' => __( 'Eid', 'klarna' ), 
							'type' => 'text', 
							'description' => __( 'Please enter your Klarna Eid; this is needed in order to take payment!', 'klarna' ), 
							'default' => ''
						),
			'secret' => array(
							'title' => __( 'Shared Secret', 'klarna' ), 
							'type' => 'text', 
							'description' => __( 'Please enter your Klarna Shared Secret; this is needed in order to take payment!', 'klarna' ), 
							'default' => ''
						),
			'klarna_checkout_url' => array(
							'title' => __( 'Custom Checkout Page', 'klarna' ), 
							'type' => 'text', 
							'description' => __( 'Please enter the URL to the page that acts as Checkout Page for Klarna Checkout. This page must contain the shortcode [woocommerce_klarna_checkout].', 'klarna' ), 
							'default' => ''
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
			
			global $woocommerce;
			
			ob_start();
			require_once 'src/Klarna/Checkout.php';
			ob_end_clean();
			
			if ($this->debug=='yes') $this->log->add( 'klarna', 'Rendering Checkout page...' );
			
			if (isset($_GET['klarna_order'])) {
			
				// Display Order response/thank you page via iframe from Klarna
				
				//@session_start();
				
				// Shared secret
				$sharedSecret = $this->secret;
				
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
				if ($this->enabled != 'yes') return;
				
				// Process order via Klarna Checkout page				

				if ( !defined( 'WOOCOMMERCE_CHECKOUT' ) ) define( 'WOOCOMMERCE_CHECKOUT', true );
				
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
        			
        			// Get an instance of the created order
        			$order = &new WC_Order( $order_id );
									
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
							
								$reference = '';
								if ( $_product->get_sku() )
									$reference = $_product->get_sku();
								
								$item_price = number_format( $order->get_item_total( $item, true )*100, 0, '', '');
								$cart[] = array(
											'reference' => $reference,
											'name' => esc_attr($item_name),
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
					$eid = $this->eid;
    		
					// Shared secret
					$sharedSecret = $this->secret;
    		
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
        					$update['merchant']['confirmation_uri'] = add_query_arg ( array('klarna_order' => '{checkout.order.uri}', 'sid' => $order_id ), $this->klarna_checkout_url);
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
	        			$create['purchase_country'] = 'SE';
	        			$create['purchase_currency'] = 'SEK';
	        			$create['locale'] = 'sv-se';
	        			$create['merchant']['id'] = $eid;
	        			$create['merchant']['terms_uri'] = $this->terms_url;
	        			$create['merchant']['checkout_uri'] = add_query_arg( 'klarnaListener', 'checkout', $this->klarna_checkout_url );
	        			$create['merchant']['confirmation_uri'] = add_query_arg ( array('klarna_order' => '{checkout.order.uri}', 'sid' => $order_id ), $this->klarna_checkout_url);
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
				
				$connector = Klarna_Checkout_Connector::create($this->secret);  
				
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
					$order = &new WC_Order( $_GET['sid'] );
					
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
					
					// Payment complete
					$order->payment_complete();		
					
					// Remove cart
					$woocommerce->cart->empty_cart();
					
					// Update the order in Klarnas system
					$update['status'] = 'created';
					$update['merchant_reference'] = array(  
														'orderid1' => $order_id
													);  
					$klarna_order->update($update);
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
		 * Helper function get_modify_standard_checkout_url
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
	 	
	 	
	 	
	 	
	
	} // End class WC_Gateway_Klarna_Checkout
	
// Extra Class for Klarna Checkout
class WC_Gateway_Klarna_Checkout_Extra {
	
	public function __construct() {
		
		add_action('init', array( &$this, 'start_session' ), 1);
		
		add_shortcode( 'woocommerce_klarna_checkout', array(&$this, 'klarna_checkout_page') );
		
		//add_action( 'woocommerce_proceed_to_checkout', array( &$this, 'checkout_button' ), 12 );
		
		add_filter( 'woocommerce_get_checkout_url', array( &$this, 'change_checkout_url' ) );
		
		
		 
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
		
		// Change the Checkout URL if this is enabled in the settings
		if( $modify_standard_checkout_url == 'yes' && $enabled == 'yes' ) {
			$url = $klarna_checkout_url;
		}
		
		return $url;
	}
		

} // End class WC_Gateway_Klarna_Checkout_Extra

$wc_klarna_checkout_extra = new WC_Gateway_Klarna_Checkout_Extra;