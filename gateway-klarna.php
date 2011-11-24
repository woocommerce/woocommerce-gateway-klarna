<?php
/*
Plugin Name: WooCommerce Klarna Gateway
Plugin URI: http://woocommerce.com
Description: Extends WooCommerce. Provides a <a href="http://www.klarna.se" target="_blank">klarna</a> API gateway for WooCommerce.<br /> Email <a href="mailto:niklas@krokedil.se">niklas@krokedil.se</a> with any questions.
Version: 1.0
Author: Niklas Högefjord
Author URI: http://krokedil.com
*/

/*  Copyright 2011  Niklas Högefjord  (email : niklas@krokedil.se)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    
*/

// Init Google Checkout Gateway after WooCommerce has loaded
add_action('plugins_loaded', 'init_klarna_gateway', 0);

function init_klarna_gateway() {

// If the WooCommerce payment gateway class is not available, do nothing
if ( !class_exists( 'woocommerce_payment_gateway' ) ) return;

// Define Klarna lib
define('KLARNA_LIB', dirname(__FILE__) . '/library/');

class woocommerce_klarna extends woocommerce_payment_gateway {
		
	public function __construct() { 
		global $woocommerce;
		
        $this->id			= 'klarna';
        $this->icon 		= plugins_url(basename(dirname(__FILE__))."/images/klarna.png");
        $this->has_fields 	= false;
		$this->shop_country	= get_option('woocommerce_default_country');

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
		$this->handlingfee		= $this->settings['handlingfee'];
		$this->handlingfee_tax	= $this->settings['handlingfee_tax'];
		$this->testmode			= $this->settings['testmode'];
		
		if ( $this->handlingfee == "") $this->handlingfee = 0;
		if ( $this->handlingfee_tax == "") $this->handlingfee_tax = 0;
		
		
		// Country and language
		switch ( $this->shop_country )
		{
		case 'DK':
			$this->klarna_country = 'DK';
			$this->klarna_language = 'DA';
			$this->klarna_currency = 'DKK';
			$this->klarna_invoice_terms = 'https://online.klarna.com/villkor_dk.yaws?eid=' . $this->eid . '&charge=' . $this->handlingfee;
			break;
		case 'DE' :
			$this->klarna_country = 'DE';
			$this->klarna_language = 'DE';
			$this->klarna_currency = 'EUR';
			$this->klarna_invoice_terms = 'https://online.klarna.com/villkor_de.yaws?eid=' . $this->eid . '&charge=' . $this->handlingfee;
			break;
		case 'NL' :
			$this->klarna_country = 'NL';
			$this->klarna_language = 'NL';
			$this->klarna_currency = 'EUR';
			$this->klarna_invoice_terms = 'https://online.klarna.com/villkor_nl.yaws?eid=' . $this->eid . '&charge=' . $this->handlingfee;
			break;
		case 'NO' :
			$this->klarna_country = 'NO';
			$this->klarna_language = 'NB';
			$this->klarna_currency = 'NOK';
			$this->klarna_invoice_terms = 'https://online.klarna.com/villkor_no.yaws?eid=' . $this->eid . '&charge=' . $this->handlingfee;
			break;
		case 'FI' :
			$this->klarna_country = 'FI';
			$this->klarna_language = 'FI';
			$this->klarna_currency = 'EUR';
			$this->klarna_invoice_terms = 'https://online.klarna.com/villkor_fi.yaws?eid=' . $this->eid . '&charge=' . $this->handlingfee;
			break;
		case 'SE' :
			$this->klarna_country = 'SE';
			$this->klarna_language = 'SV';
			$this->klarna_currency = 'SEK';
			$this->klarna_invoice_terms = 'https://online.klarna.com/villkor.yaws?eid=' . $this->eid . '&charge=' . $this->handlingfee;
			break;
		default:
			// The sound of one hand clapping
		}

		

		// Actions
		add_action('woocommerce_receipt_klarna', array(&$this, 'receipt_page'));
		if ( $this->testmode !== 'yes' ):
			add_action('admin_notices', array(&$this,'klarna_ssl_check'));
		endif;
		add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
    } 
    
		/**
	 	* Check if SSL is enabled and notify the user
	 	**/
		function klarna_ssl_check() {
		     
		     if (get_option('woocommerce_force_ssl_checkout')=='no' && $this->enabled=='yes') :
		     
		     	echo '<div class="error"><p>'.sprintf(__('Klarna is enabled and the <a href="%s">force SSL option</a> is disabled; your checkout is not secure! Please enable SSL and ensure your server has a valid SSL certificate.', 'woothemes'), admin_url('admin.php?page=woocommerce')).'</p></div>';
		     
		     endif;
		}
    
	/**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields() {
    
    	$this->form_fields = array(
			'enabled' => array(
							'title' => __( 'Enable/Disable', 'woothemes' ), 
							'type' => 'checkbox', 
							'label' => __( 'Enable klarna standard', 'woothemes' ), 
							'default' => 'yes'
						), 
			'title' => array(
							'title' => __( 'Title', 'woothemes' ), 
							'type' => 'text', 
							'description' => __( 'This controls the title which the user sees during checkout.', 'woothemes' ), 
							'default' => __( 'Klarna', 'woothemes' )
						),
			'description' => array(
							'title' => __( 'Description', 'woothemes' ), 
							'type' => 'textarea', 
							'description' => __( 'This controls the description which the user sees during checkout.', 'woothemes' ), 
							'default' => __("Klarna invoice - Pay within 14 days.", 'woothemes')
						), 
			'eid' => array(
							'title' => __( 'Eid', 'woothemes' ), 
							'type' => 'text', 
							'description' => __( 'Please enter your Klarna Eid; this is needed in order to take payment!', 'woothemes' ), 
							'default' => __( '', 'woothemes' )
						),
			'secret' => array(
							'title' => __( 'Shared Secret', 'woothemes' ), 
							'type' => 'text', 
							'description' => __( 'Please enter your Klarna Shared Secret; this is needed in order to take payment!', 'woothemes' ), 
							'default' => __( '', 'woothemes' )
						),
			'handlingfee' => array(
							'title' => __( 'Handling Fee', 'woothemes' ), 
							'type' => 'text', 
							'description' => __( 'Fee <em>including</em> tax. Enter an amount, e.g. 25.5. Leave blank to disable.', 'woothemes' ), 
							'default' => __( '', 'woothemes' )
						),
			'handlingfee_tax' => array(
							'title' => __( 'Tax for Handling Fee', 'woothemes' ), 
							'type' => 'text', 
							'description' => __( '%. Enter tax rate for Handling Fee, e.g. 25.00. Leave blank to disable Tax on Handling Fee.', 'woothemes' ), 
							'default' => __( '', 'woothemes' )
						),
			'testmode' => array(
							'title' => __( 'Test Mode', 'woothemes' ), 
							'type' => 'checkbox', 
							'label' => __( 'Enable Klarna Test Mode. This will only work if you have a Klarna test account. For test purchases with a live account, <a href="http://integration.klarna.com/en/testing/test-persons" target="_blank">follow these instructions</a>.', 'woothemes' ), 
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
    	<h3><?php _e('Klarna', 'woothemes'); ?></h3>
	    	<p><?php _e('With Klarna your customers can pay by invoice. Klarna works by adding extra personal information fields and then sending the details to Klarna for verification.', 'woothemes'); ?></p>
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
			if (!in_array(get_option('woocommerce_currency'), array('DKK', 'EUR', 'NOK', 'SEK'))) return false;
			
			// Base country check
			if (!in_array(get_option('woocommerce_default_country'), array('DK', 'DE', 'FI', 'NL', 'NO', 'SE'))) return false;
			
			// Required fields check
			if (!$this->eid || !$this->secret) return false;
					
		endif;	
	
		return true;
	}


    /**
	 * Payment form on checkout page
	 */
    function payment_fields() {
    	global $woocommerce;
    	?>
    	<?php if ($this->testmode=='yes') : ?><p><?php _e('TEST MODE ENABLED', 'woothemes'); ?></p><?php endif; ?>
		<?php if ($this->description) : ?><p><?php echo $this->description; ?></p><?php endif; ?>
		<?php if ($this->handlingfee>0) : ?><p><?php printf(__('An invoice/handling fee of %1$s %2$s will be added. This cost will only be visible on your invoice from Klarna.', 'woothemes'), $this->handlingfee, get_option('woocommerce_currency') ); ?></p><?php endif; ?>
		
		<p><a href="<?php echo $this->klarna_invoice_terms;?>" target="_blank"><?php echo __("Terms for invoice", 'woocommerce') ?></a></p>
		
		<fieldset>
			<p class="form-row form-row-first">
				<label for="klarna_pno"><?php echo __("Social Security Number", 'woocommerce') ?> <span class="required">*</span></label>
				<input type="text" class="input-text" name="klarna_pno" />
			</p>
			
			<?php if ( in_array(get_option('woocommerce_default_country'), array('DE', 'NL'))) : ?>
				<p class="form-row form-row-last">
					<label for="klarna_gender"><?php echo __("Gender", 'woocommerce') ?> <span class="required">*</span></label><br/>
					<select id="klarna_gender" name="klarna_gender" class="woocommerce-select">
						<option value="MALE">Male</options>
						<option value="FEMALE">Female</options>
					</select>
				</p>
			<?php endif; ?>
			
			<div class="clear"></div>
			
			<?php if ( in_array(get_option('woocommerce_default_country'), array('DE', 'NL'))) : ?>	
				<p class="form-row form-row-first">
					<label for="klarna_house_number"><?php echo __("House Number", 'woocommerce') ?> <span class="required">*</span></label>
					<input type="text" class="input-text" name="klarna_house_number" />
				</p>
			<?php endif; ?>
			
			<?php if ( get_option('woocommerce_default_country') == 'NL' ) : ?>
				<p class="form-row form-row-last">
					<label for="klarna_house_extension"><?php echo __("House Extension", 'woocommerce') ?> <span class="required">*</span></label>
					<input type="text" class="input-text" name="klarna_house_extension" />
				</p>
			<?php endif; ?>
			
			<div class="clear"></div>
		</fieldset>


    	<?php	
    }


	
	/**
	 * Process the payment and return the result
	 **/
	function process_payment( $order_id ) {
		global $woocommerce;
		
		$order = &new woocommerce_order( $order_id );
		
		require_once(KLARNA_LIB . 'Klarna.php');
		require_once(KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc');
		require_once(KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc');
		
		// Get values from klarna form on checkout page
		$klarna_pno 			= isset($_POST['klarna_pno']) ? woocommerce_clean($_POST['klarna_pno']) : '';
		$klarna_gender 			= isset($_POST['klarna_gender']) ? woocommerce_clean($_POST['klarna_gender']) : '';
		$klarna_house_number	= isset($_POST['klarna_house_number']) ? woocommerce_clean($_POST['klarna_house_number']) : '';
		$klarna_house_extension	= isset($_POST['klarna_house_extension']) ? woocommerce_clean($_POST['klarna_house_extension']) : '';
		
				
		// Disable SSL if in testmode
		if ( $this->testmode == 'yes' ):
			$klarna_ssl = 'false';
			$klarna_mode = Klarna::BETA;
		else :
			$klarna_ssl = 'true';
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
		if (sizeof($order->items)>0) : foreach ($order->items as $item) :
			$_product = $order->get_product_from_item( $item );
			if ($_product->exists() && $item['qty']) :		
				$klarna_item_price_including_tax = $item['cost'] * (($item['taxrate']/100)+1);
				$k->addArticle(
		    		$qty = $item['qty'], //Quantity
		    		$artNo = $item['id'], //Article number
		    		$title = utf8_decode ($item['name']), //Article name/title
		    		$price = $klarna_item_price_including_tax, // Price
		    		$vat = $item['taxrate'], //19% VAT
		    		$discount = 0, 
		    		$flags = KlarnaFlags::INC_VAT //Price is including VAT.
				);
									
			endif;
		endforeach; endif;
		 
		// Discount
		if ($order->order_discount>0) :
		
			$k->addArticle(
			    $qty = 1,
			    $artNo = "",
			    $title = __('Discount', 'woothemes'),
			    $price = -$order->order_discount,
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
			$klarna_shipping_price_including_tax = $order->order_shipping*$calculated_shipping_tax_decimal;
			
			$k->addArticle(
			    $qty = 1,
			    $artNo = "",
			    $title = __('Shipping cost', 'woothemes'),
			    $price = $klarna_shipping_price_including_tax,
			    $vat = $calculated_shipping_tax_percentage,
			    $discount = 0,
			    $flags = KlarnaFlags::INC_VAT + KlarnaFlags::IS_SHIPMENT //Price is including VAT and is shipment fee
			);
		endif;
		
		//Invoice/handling fee
		if ( $this->handlingfee>0 ) :
			$k->addArticle(
			    $qty = 1,
			    $artNo = "",
			    $title = __('Handling Fee', 'woothemes'),
			    $price = $this->handlingfee,
			    $vat = $this->handlingfee_tax,
			    $discount = 0,
			    $flags = KlarnaFlags::INC_VAT + KlarnaFlags::IS_HANDLING //Price is including VAT and is handling/invoice fee
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
    		    $pclass = KlarnaPClass::INVOICE //-1, notes that this is an invoice purchase, for part payment purchase you will have a pclass object which you use getId() from.
    		);
    		
    		// Retreive response
    		$invno = $result[0];
    		switch($result[1]) {
            case KlarnaFlags::ACCEPTED:
                $order->add_order_note( __('Klarna payment completed. Klarna Invoice number: ', 'woothemes') . $invno );
                
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
                $order->add_order_note( __('Order is PENDING APPROVAL by Klarna. Please visit Klarna Online for the latest status on this order. Klarna Invoice number: ', 'woothemes') . $invno );
                
                // Payment on-hold
				$order->update_status('on-hold', $message );
				
				// Remove cart
				$woocommerce->cart->empty_cart();
				// $woocommerce->add_error( __('Order is PENDING APPROVAL by Klarna. Please contact us for the latest status on this order. Klarna Invoice number:', 'woothemes') . $invno );
				
				// Return thank you redirect
				return array(
						'result' 	=> 'success',
						'redirect'	=> add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))))
				);
				
                break;
            case KlarnaFlags::DENIED:
                //Order is denied, store it in a database.
				$order->add_order_note( __('Klarna payment denied.', 'woothemes') );
				$woocommerce->add_error( __('Klarna payment denied.', 'woothemes') );
                return;
                break;
            default:
            	//Unknown response, store it in a database.
				$order->add_order_note( __('Unknown response from Klarna.', 'woothemes') );
				$woocommerce->add_error( __('Unknown response from Klarna.', 'woothemes') );
                return;
                break;
        	}
 			
 	   		
			}
		
		catch(Exception $e) {
    		//The purchase was denied or something went wrong, print the message:
			$woocommerce->add_error( sprintf(__('Klarna payment failed (Correlation ID: %s). Payment was rejected due to an error: ', 'woothemes'), $e->getMessage() ) . '"' . $e->getCode() . '"' );
			return;
		}


		
	}
	
	
	
	/**
	 * receipt_page
	 **/
	function receipt_page( $order ) {
		
		echo '<p>'.__('Thank you for your order.', 'woothemes').'</p>';
		
	}	

	


}

} // End init_klarna_gateway

/**
 * Add the gateway to WooCommerce
 **/
function add_klarna_gateway( $methods ) {
	$methods[] = 'woocommerce_klarna'; return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_klarna_gateway' );
