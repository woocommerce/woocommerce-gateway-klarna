<?php

		
class WC_Gateway_Klarna_Account extends WC_Gateway_Klarna {
	
	/**
     * Class for Klarna Account payment.
     *
     */
     
	public function __construct() {
		global $woocommerce;
		parent::__construct();
		
		$this->id			= 'klarna_account';
		$this->method_title = __('Klarna Account', 'klarna');
		$this->icon 		= plugins_url(basename(dirname(__FILE__))."/images/klarna.png");
		$this->has_fields 	= true;
		
		// Load the form fields.
		$this->init_form_fields();
				
		// Load the settings.
		$this->init_settings();
		
		// Define user set variables
		$this->enabled			= $this->settings['enabled'];
		$this->title 			= $this->settings['title'];
		$this->description  	= $this->settings['description'];
		$this->eid				= $this->settings['eid'];
		$this->secret			= $this->settings['secret'];
		$this->testmode			= $this->settings['testmode'];
		
		
		// Country and language
		switch ( $this->shop_country )
		{
		case 'DK':
			$klarna_country = 'DK';
			$klarna_language = 'DA';
			$klarna_currency = 'DKK';
			$klarna_account_info = 'https://online.klarna.com/account_dk.yaws?eid=' . $this->eid;
			break;
		case 'DE' :
			$klarna_country = 'DE';
			$klarna_language = 'DE';
			$klarna_currency = 'EUR';
			$klarna_account_info = 'https://online.klarna.com/account_de.yaws?eid=' . $this->eid;
			break;
		case 'NL' :
			$klarna_country = 'NL';
			$klarna_language = 'NL';
			$klarna_currency = 'EUR';
			$klarna_account_info = 'https://online.klarna.com/account_nl.yaws?eid=' . $this->eid;
			break;
		case 'NO' :
			$klarna_country = 'NO';
			$klarna_language = 'NB';
			$klarna_currency = 'NOK';
			$klarna_account_info = 'https://online.klarna.com/account_no.yaws?eid=' . $this->eid;
			break;
		case 'FI' :
			$klarna_country = 'FI';
			$klarna_language = 'FI';
			$klarna_currency = 'EUR';
			$klarna_account_info = 'https://online.klarna.com/account_fi.yaws?eid=' . $this->eid;
			break;
		case 'SE' :
			$klarna_country = 'SE';
			$klarna_language = 'SV';
			$klarna_currency = 'SEK';
			$klarna_account_info = 'https://online.klarna.com/account_se.yaws?eid=' . $this->eid;
			break;
		default:
			$klarna_country = '';
			$klarna_language = '';
			$klarna_currency = '';
			$klarna_account_info = '';
		}
		
		
		// Actions
		add_action('woocommerce_receipt_klarna_account', array(&$this, 'receipt_page'));

		add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
		
		// Apply filters to Country and language
		$this->klarna_country = apply_filters( 'klarna_country', $klarna_country );
		$this->klarna_language = apply_filters( 'klarna_language', $klarna_language );
		$this->klarna_currency = apply_filters( 'klarna_currency', $klarna_currency );
		$this->klarna_account_info = apply_filters( 'klarna_account_info', $klarna_account_info );
		
				
	}
	
	
		
	/**
	 * Initialise Gateway Settings Form Fields
	 */
	function init_form_fields() {
	    
	   	$this->form_fields = array(
			'enabled' => array(
							'title' => __( 'Enable/Disable', 'klarna' ), 
							'type' => 'checkbox', 
							'label' => __( 'Enable Klarna Account', 'klarna' ), 
							'default' => 'yes'
						), 
			'title' => array(
							'title' => __( 'Title', 'klarna' ), 
							'type' => 'text', 
							'description' => __( 'This controls the title which the user sees during checkout.', 'klarna' ), 
							'default' => __( 'Part Payment - Klarna', 'klarna' )
						),
			'description' => array(
							'title' => __( 'Description', 'klarna' ), 
							'type' => 'textarea', 
							'description' => __( 'This controls the description which the user sees during checkout.', 'klarna' ), 
							'default' => __("Part Payment.", 'klarna')
						), 
			'eid' => array(
							'title' => __( 'Eid', 'klarna' ), 
							'type' => 'text', 
							'description' => __( 'Please enter your Klarna Eid; this is needed in order to take payment!', 'klarna' ), 
							'default' => __( '', 'klarna' )
						),
			'secret' => array(
							'title' => __( 'Shared Secret', 'klarna' ), 
							'type' => 'text', 
							'description' => __( 'Please enter your Klarna Shared Secret; this is needed in order to take payment!', 'klarna' ), 
							'default' => __( '', 'klarna' )
						),
			'testmode' => array(
							'title' => __( 'Test Mode', 'klarna' ), 
							'type' => 'checkbox', 
							'label' => __( 'Enable Klarna Test Mode. This will only work if you have a Klarna test account. For test purchases with a live account, <a href="http://integration.klarna.com/en/testing/test-persons" target="_blank">follow these instructions</a>.', 'klarna' ), 
							'default' => 'no'
						)
		);
	    
	} // End init_form_fields()
	
	
	/**
	 * Admin Panel Options 
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {

    	?>
    	<h3><?php _e('Klarna Account', 'klarna'); ?></h3>
	    	<p><?php _e('With Klarna your customers can pay by invoice. Klarna works by adding extra personal information fields and then sending the details to Klarna for verification.', 'klarna'); ?></p>
	    	<div class="updated inline">
		    	<p><?php _e('Note that read and write permissions for the directory <i>srv</i> (located in woocommerce-gateway-klarna) and the containing file <i>pclasses.json</i> must be set to 777 in order to fetch the available PClasses from Klarna.', 'klarna'); ?></p>
		    </div>
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
		
		if ($this->enabled=="yes") :
		
			// if (!is_ssl()) return false;
			
			// Currency check
			// if (!in_array(get_option('woocommerce_currency'), array('DKK', 'EUR', 'NOK', 'SEK'))) return false;
			
			// Base country check
			if (!in_array(get_option('woocommerce_default_country'), array('DK', 'DE', 'FI', 'NL', 'NO', 'SE'))) return false;
			
			// Required fields check
			if (!$this->eid || !$this->secret) return false;
			
			return true;
					
		endif;	
	
		return false;
	}
	
	
	
	/**
	 * Payment form on checkout page
	 */
	
	function payment_fields( ) {
	   	global $woocommerce;
	   	
	   	
	   	
	   	// Get PClasses so that the customer can chose between different payment plans.
	  	require_once(KLARNA_LIB . 'Klarna.php');
		require_once(KLARNA_LIB . 'pclasses/storage.intf.php');
		require_once(KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc');
		require_once(KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc');
		
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
		    $eid = $this->settings['eid'],
		    $secret = $this->settings['secret'],
		    $country = $this->klarna_country,
		    $language = $this->klarna_language,
		    $currency = $this->klarna_currency,
		    $mode = $klarna_mode,
		    $pcStorage = 'json',
		    $pcURI = KLARNA_DIR . 'srv/pclasses.json',
		    $ssl = $klarna_ssl,
		    $candice = true
		);

		Klarna::$xmlrpcDebug = false;
		Klarna::$debug = false;
	   	?>
	   	
	   	<?php if ($this->testmode=='yes') : ?><p><?php _e('TEST MODE ENABLED', 'klarna'); ?></p><?php endif; ?>
		<?php if ($this->description) : ?><p><?php echo $this->description; ?></p><?php endif; ?>
		
		<fieldset>
			<p class="form-row form-row-first">
			
				<?php
				/**
 				* 2. Retrieve the PClasses from Klarna.
 				*/
				
				try {
				    $k->fetchPClasses($this->klarna_country); //You can specify country (and language, currency if you wish) if you don't want to use the configured country.
				    	/* PClasses successfully fetched, now you can use getPClasses() to load them locally or getPClass to load a specific PClass locally. */
						?>
						<label for="klarna_pclass"><?php echo __("Payment plan", 'klarna') ?> <span class="required">*</span></label><br/>
						<select id="klarna_pclass" name="klarna_pclass" class="woocommerce-select">
						
						<?php
				    	// Loop through the available PClasses
						foreach ($k->getPClasses() as $pclass) {
				   			echo '<option value="' . $pclass->getId() . '">'. $pclass->getDescription() . '</option>';
						}
						?>
						
						</select>
						
						<?php
					}
				catch(Exception $e) {
				    //Something went wrong, print the message:
				    $woocommerce->add_error( sprintf(__('Klarna PClass problem: %s. Error code: ', 'klarna'), utf8_encode($e->getMessage()) ) . '"' . $e->getCode() . '"' );
				}
				?>
				
			</p>
			<?php
			// Calculate monthly cost and display it
			$sum = $woocommerce->cart->total; // Cart total.
			$flag = KlarnaFlags::CHECKOUT_PAGE; //or KlarnaFlags::PRODUCT_PAGE, if you want to do it for one item.
			$pclass = $k->getCheapestPClass($sum, $flag);
	
			//Did we get a PClass? (it is false if we didn't)
			if($pclass) {
	    		//Here we reuse the same values as above:
    			$value = KlarnaCalc::calc_monthly_cost(
    	    	$sum,
    	    	$pclass,
    	    	$flag
    			);
	
	    		/* $value is now a rounded monthly cost amount to be displayed to the customer. */
	    		echo '<p class="form-row form-row-last">' . sprintf(__('From %s %s/month', 'klarna'), $value, $this->klarna_currency ) . '</p>';
	    		
			}
			?>
			<div class="clear"></div>
			
			<p class="form-row form-row-first">
				<label for="klarna_pno"><?php echo __("Social Security Number", 'klarna') ?> <span class="required">*</span></label>
				<input type="text" class="input-text" name="klarna_pno" />
			</p>
			
			<?php if ( in_array(get_option('woocommerce_default_country'), array('DE', 'NL'))) : ?>
				<p class="form-row form-row-last">
					<label for="klarna_gender"><?php echo __("Gender", 'klarna') ?> <span class="required">*</span></label><br/>
					<select id="klarna_gender" name="klarna_gender" class="woocommerce-select">
						<option value="MALE">Male</options>
						<option value="FEMALE">Female</options>
					</select>
				</p>
			<?php endif; ?>
			
			<div class="clear"></div>
			
			<?php if ( in_array(get_option('woocommerce_default_country'), array('DE', 'NL'))) : ?>	
				<p class="form-row form-row-first">
					<label for="klarna_house_number"><?php echo __("House Number", 'klarna') ?> <span class="required">*</span></label>
					<input type="text" class="input-text" name="klarna_house_number" />
				</p>
			<?php endif; ?>
			
			<?php if ( get_option('woocommerce_default_country') == 'NL' ) : ?>
				<p class="form-row form-row-last">
					<label for="klarna_house_extension"><?php echo __("House Extension", 'klarna') ?> <span class="required">*</span></label>
					<input type="text" class="input-text" name="klarna_house_extension" />
				</p>
			<?php endif; ?>
			
			<div class="clear"></div>
		</fieldset>
		
		<p><a href="<?php echo $this->klarna_account_info;?>" target="_blank" class="iframe"><?php echo __('Read more', 'klarna') ?></a></p>
		<div class="clear"></div>
		
		<?php	
	}
	
	
	/**
	 * Process the payment and return the result
	 **/
	function process_payment( $order_id ) {
		global $woocommerce;
		
		$order = &new woocommerce_order( $order_id );
		
		require_once(KLARNA_LIB . 'Klarna.php');
		require_once(KLARNA_LIB . 'pclasses/storage.intf.php');
		require_once(KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc');
		require_once(KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc');
		
		// Get values from klarna form on checkout page
		$klarna_pclass 			= isset($_POST['klarna_pclass']) ? woocommerce_clean($_POST['klarna_pclass']) : '';
		$klarna_pno 			= isset($_POST['klarna_pno']) ? woocommerce_clean($_POST['klarna_pno']) : '';
		$klarna_gender 			= isset($_POST['klarna_gender']) ? woocommerce_clean($_POST['klarna_gender']) : '';
		$klarna_house_number	= isset($_POST['klarna_house_number']) ? woocommerce_clean($_POST['klarna_house_number']) : '';
		$klarna_house_extension	= isset($_POST['klarna_house_extension']) ? woocommerce_clean($_POST['klarna_house_extension']) : '';
		
		
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
		    $eid = $this->settings['eid'],
		    $secret = $this->settings['secret'],
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
				if ($order->get_total_tax() >0) :
					// Calculate tax percentage
					$item_tax_percentage = number_format( ( $order->get_line_tax($item) / $order->get_line_total( $item, false ) )*100, 2, '.', '');
				else :
					$item_tax_percentage = 0.00;
				endif;
				
				// apply_filters to item price so we can filter this if needed
				$klarna_item_price_including_tax = $order->get_item_total( $item, true );
				$item_price = apply_filters( 'klarna_item_price_including_tax', $klarna_item_price_including_tax );
				
					$k->addArticle(
		    		$qty = $item['qty'], 					//Quantity
		    		$artNo = $item['id'], 					//Article number
		    		// $title = utf8_decode ($item['name']), 	//Article name/title
		    		$title = 'Konto', 	//Article name/title
		    		$price = $item_price, 					// Price including tax
		    		$vat = $item_tax_percentage,			// Tax
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
			    $vat = $calculated_shipping_tax_percentage,
			    $discount = 0,
			    $flags = KlarnaFlags::INC_VAT + KlarnaFlags::IS_SHIPMENT //Price is including VAT and is shipment fee
			);
		endif;
		
		
		//Create the address object and specify the values.
		$addr_billing = new KlarnaAddr(
    		$email = $order->billing_email,
    		$telno = '', //We skip the normal land line phone, only one is needed.
    		$cellno = $order->billing_phone,
    		//$company = $order->billing_company,
    		$fname = utf8_decode ($order->billing_first_name),
    		$lname = utf8_decode ($order->billing_last_name),
    		$careof = utf8_decode ($order->billing_address_2),  //No care of, C/O.
    		$street = utf8_decode ($order->billing_address_1), //For DE and NL specify street number in houseNo.
    		$zip = utf8_decode ($order->billing_postcode),
    		$city = utf8_decode ($order->billing_city),
    		$country = utf8_decode ($order->billing_country),
    		$houseNo = utf8_decode ($klarna_house_number), //For DE and NL we need to specify houseNo.
    		$houseExt = utf8_decode ($klarna_house_extension) //Only required for NL.
		);
		
		$addr_shipping = new KlarnaAddr(
    		$email = $order->billing_email,
    		$telno = '', //We skip the normal land line phone, only one is needed.
    		$cellno = $order->billing_phone,
    		//$company = $order->shipping_company,
    		$fname = utf8_decode ($order->shipping_first_name),
    		$lname = utf8_decode ($order->shipping_last_name),
    		$careof = utf8_decode ($order->shipping_address_2),  //No care of, C/O.
    		$street = utf8_decode ($order->shipping_address_1), //For DE and NL specify street number in houseNo.
    		$zip = utf8_decode ($order->shipping_postcode),
    		$city = utf8_decode ($order->shipping_city),
    		$country = utf8_decode ($order->shipping_country),
    		$houseNo = utf8_decode ($klarna_house_number), //For DE and NL we need to specify houseNo.
    		$houseExt = utf8_decode ($klarna_house_extension) //Only required for NL.
		);


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
    		$result = $k->addTransaction(
    		    $pno = $klarna_pno, //Date of birth.
    		    
    		    $gender = intval($klarna_gender),//Gender.
    		    $flags = KlarnaFlags::NO_FLAG, //No specific behaviour like RETURN_OCR or TEST_MODE.
    		    $pclass = $klarna_pclass // Get the pclass object that the customer has choosen.
    		);
    		
    		// Retreive response
    		$invno = $result[0];
    		switch($result[1]) {
            case KlarnaFlags::ACCEPTED:
                $order->add_order_note( __('Klarna payment completed. Klarna Invoice number: ', 'klarna') . $invno );
                
                // Payment complete
				$order->payment_complete();		
				
				// Remove cart
				$woocommerce->cart->empty_cart();			
				
				// Return thank you redirect
				return array(
						'result' 	=> 'success',
						'redirect'	=> add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))))
				);
						
                break;
            case KlarnaFlags::PENDING:
                $order->add_order_note( __('Order is PENDING APPROVAL by Klarna. Please visit Klarna Online for the latest status on this order. Klarna Invoice number: ', 'klarna') . $invno );
                
                // Payment on-hold
				$order->update_status('on-hold', $message );
				
				// Remove cart
				$woocommerce->cart->empty_cart();
				
				// Return thank you redirect
				return array(
						'result' 	=> 'success',
						'redirect'	=> add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))))
				);
				
                break;
            case KlarnaFlags::DENIED:
                //Order is denied, store it in a database.
				$order->add_order_note( __('Klarna payment denied.', 'klarna') );
				$woocommerce->add_error( __('Klarna payment denied.', 'klarna') );
                return;
                break;
            default:
            	//Unknown response, store it in a database.
				$order->add_order_note( __('Unknown response from Klarna.', 'klarna') );
				$woocommerce->add_error( __('Unknown response from Klarna.', 'klarna') );
                return;
                break;
        	}
 			
 	   		
			}
		
		catch(Exception $e) {
    		//The purchase was denied or something went wrong, print the message:
			$woocommerce->add_error( sprintf(__('%s (Error code: %s)', 'klarna'), utf8_encode($e->getMessage()), $e->getCode() ) );
			return;
		}

	
	}
	
	/**
	 * receipt_page
	 **/
	function receipt_page( $order ) {
		
		echo '<p>'.__('Thank you for your order.', 'klarna').'</p>';
		
	}	

} // End class WC_Gateway_Klarna_Invoice