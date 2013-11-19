<?php

class WC_Gateway_Klarna_Invoice extends WC_Gateway_Klarna {
	
	/**
     * Class for Klarna Invoice payment.
     *
     */
     
	public function __construct() {
		global $woocommerce;
		
		parent::__construct();
		
		$this->id			= 'klarna';
		$this->method_title = __('Klarna Invoice', 'klarna');
		$this->has_fields 	= true;
		
		// Load the form fields.
		$this->init_form_fields();
				
		// Load the settings.
		$this->init_settings();
		
		// Define user set variables
		$this->enabled					= ( isset( $this->settings['enabled'] ) ) ? $this->settings['enabled'] : '';
		$this->title 					= ( isset( $this->settings['title'] ) ) ? $this->settings['title'] : '';
		$this->description  			= ( isset( $this->settings['description'] ) ) ? $this->settings['description'] : '';
		$this->eid						= ( isset( $this->settings['eid'] ) ) ? $this->settings['eid'] : '';
		$this->secret					= ( isset( $this->settings['secret'] ) ) ? $this->settings['secret'] : '';
		$this->lower_threshold			= ( isset( $this->settings['lower_threshold'] ) ) ? $this->settings['lower_threshold'] : '';
		$this->upper_threshold			= ( isset( $this->settings['upper_threshold'] ) ) ? $this->settings['upper_threshold'] : '';
		$this->invoice_fee_id			= ( isset( $this->settings['invoice_fee_id'] ) ) ? $this->settings['invoice_fee_id'] : '';
		$this->testmode					= ( isset( $this->settings['testmode'] ) ) ? $this->settings['testmode'] : '';
		$this->de_consent_terms			= ( isset( $this->settings['de_consent_terms'] ) ) ? $this->settings['de_consent_terms'] : '';
		$this->ship_to_billing_address	= ( isset( $this->settings['ship_to_billing_address'] ) ) ? $this->settings['ship_to_billing_address'] : '';
		
		//if ( $this->handlingfee == "") $this->handlingfee = 0;
		//if ( $this->handlingfee_tax == "") $this->handlingfee_tax = 0;
		if ( $this->invoice_fee_id == "") $this->invoice_fee_id = 0;
		
		if ( $this->invoice_fee_id > 0 ) :
			
			// Version check - 1.6.6 or 2.0
			if ( function_exists( 'get_product' ) ) {
				$product = get_product($this->invoice_fee_id);
			} else {
				$product = new WC_Product( $this->invoice_fee_id );
			}
		
			if ( $product->exists() ) :
			
				// We manually calculate the tax percentage here
				$this->invoice_fee_tax_percentage = number_format( (( $product->get_price() / $product->get_price_excluding_tax() )-1)*100, 2, '.', '');
				
				// apply_filters to invoice fee price so we can filter this if needed
				$klarna_invoice_fee_price_including_tax = $product->get_price();
				$this->invoice_fee_price = apply_filters( 'klarna_invoice_fee_price_including_tax', $klarna_invoice_fee_price_including_tax );
				
			else :
			
				$this->invoice_fee_price = 0;	
							
			endif;
		
		else :
		
		$this->invoice_fee_price = 0;
		
		endif;
		
		
		// Country and language
		switch ( $this->shop_country )
		{
		case 'DK':
			$klarna_country = 'DK';
			$klarna_language = 'DA';
			$klarna_currency = 'DKK';
			$klarna_invoice_terms = 'https://online.klarna.com/villkor_dk.yaws?eid=' . $this->eid . '&charge=' . $this->invoice_fee_price;
			//$klarna_invoice_icon = plugins_url(basename(dirname(__FILE__))."/images/klarna_invoice_dk.png");
			$klarna_invoice_icon = 'https://cdn.klarna.com/public/images/DK/badges/v1/invoice/DK_invoice_badge_std_blue.png?width=60&eid=' . $this->eid;
			break;
		case 'DE' :
			$klarna_country = 'DE';
			$klarna_language = 'DE';
			$klarna_currency = 'EUR';
			$klarna_invoice_terms = 'https://online.klarna.com/villkor_de.yaws?eid=' . $this->eid . '&charge=' . $this->invoice_fee_price;
			//$klarna_invoice_icon = plugins_url(basename(dirname(__FILE__))."/images/klarna_invoice_de.png");
			$klarna_invoice_icon = 'https://cdn.klarna.com/public/images/DE/badges/v1/invoice/DE_invoice_badge_std_blue.png?width=60&eid=' . $this->eid;
			break;
		case 'NL' :
			$klarna_country = 'NL';
			$klarna_language = 'NL';
			$klarna_currency = 'EUR';
			$klarna_invoice_terms = 'https://online.klarna.com/villkor_nl.yaws?eid=' . $this->eid . '&charge=' . $this->invoice_fee_price;
			//$klarna_invoice_icon = plugins_url(basename(dirname(__FILE__))."/images/klarna_invoice_nl.png");
			$klarna_invoice_icon = 'https://cdn.klarna.com/public/images/NL/badges/v1/invoice/NL_invoice_badge_std_blue.png?width=60&eid=' . $this->eid;
			break;
		case 'NO' :
		case 'NB' :
			$klarna_country = 'NO';
			$klarna_language = 'NB';
			$klarna_currency = 'NOK';
			$klarna_invoice_terms = 'https://online.klarna.com/villkor_no.yaws?eid=' . $this->eid . '&charge=' . $this->invoice_fee_price;
			//$klarna_invoice_icon = plugins_url(basename(dirname(__FILE__))."/images/klarna_invoice_no.png");
			$klarna_invoice_icon = 'https://cdn.klarna.com/public/images/NO/badges/v1/invoice/NO_invoice_badge_std_blue.png?width=60&eid=' . $this->eid;
			break;
		case 'FI' :
			$klarna_country = 'FI';
			$klarna_language = 'FI';
			$klarna_currency = 'EUR';
			$klarna_invoice_terms = 'https://online.klarna.com/villkor_fi.yaws?eid=' . $this->eid . '&charge=' . $this->invoice_fee_price;
			//$klarna_invoice_icon = plugins_url(basename(dirname(__FILE__))."/images/klarna_invoice_fi.png");
			$klarna_invoice_icon = 'https://cdn.klarna.com/public/images/FI/badges/v1/invoice/FI_invoice_badge_std_blue.png?width=60&eid=' . $this->eid;
			break;
		case 'SE' :
		case 'SV' :
			$klarna_country = 'SE';
			$klarna_language = 'SV';
			$klarna_currency = 'SEK';
			$klarna_invoice_terms = 'https://online.klarna.com/villkor.yaws?eid=' . $this->eid . '&charge=' . $this->invoice_fee_price;
			//$klarna_invoice_icon = plugins_url(basename(dirname(__FILE__))."/images/klarna_invoice_se.png");
			$klarna_invoice_icon = 'https://cdn.klarna.com/public/images/SE/badges/v1/invoice/SE_invoice_badge_std_blue.png?width=60&eid=' . $this->eid;
			break;
		default:
			$klarna_country = '';
			$klarna_language = '';
			$klarna_currency = '';
			$klarna_invoice_terms = '';
			$klarna_invoice_icon = '';
		}
		
		// Apply filters to Country and language
		$this->klarna_country 		= apply_filters( 'klarna_country', $klarna_country );
		$this->klarna_language 		= apply_filters( 'klarna_language', $klarna_language );
		$this->klarna_currency 		= apply_filters( 'klarna_currency', $klarna_currency );
		$this->klarna_invoice_terms = apply_filters( 'klarna_invoice_terms', $klarna_invoice_terms );
		$this->icon 				= apply_filters( 'klarna_invoice_icon', $klarna_invoice_icon );
		
		
		// Actions

		/* 1.6.6 */
		add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
 
		/* 2.0.0 */
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		
		
		
		add_action('woocommerce_receipt_klarna', array(&$this, 'receipt_page'));
		
		add_action('wp_footer', array(&$this, 'klarna_invoice_terms_js'));
		
	}

	
	
	
	/**
	 * Initialise Gateway Settings Form Fields
	 */
	function init_form_fields() {
	    
	   	$this->form_fields = apply_filters('klarna_invoice_form_fields', array(
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
							'default' => __( 'Klarna Invoice - pay within 14 days', 'klarna' )
						),
			'description' => array(
							'title' => __( 'Description', 'klarna' ), 
							'type' => 'textarea', 
							'description' => __( 'This controls the description which the user sees during checkout.', 'klarna' ), 
							'default' => ''
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
							'title' => __( 'Klarna concent terms (DE only)', 'klarna' ), 
							'type' => 'checkbox', 
							'label' => __( 'Enable Klarna concent terms checkbox in checkout. This only apply to German merchants.', 'klarna' ), 
							'default' => 'no'
						),
			'testmode' => array(
							'title' => __( 'Test Mode', 'klarna' ), 
							'type' => 'checkbox', 
							'label' => __( 'Enable Klarna Test Mode. This will only work if you have a Klarna test account. For test purchases with a live account, <a href="http://integration.klarna.com/en/testing/test-persons" target="_blank">follow these instructions</a>.', 'klarna' ), 
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
    	<h3><?php _e('Klarna Invoice', 'klarna'); ?></h3>
	    	
	    	<p><?php printf(__('With Klarna your customers can pay by invoice. Klarna works by adding extra personal information fields and then sending the details to Klarna for verification. Documentation <a href="%s" target="_blank">can be found here</a>.', 'klarna'), 'http://wcdocs.woothemes.com/user-guide/extensions/klarna/' ); ?></p>
	    	
	    	
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
		
		if ($this->enabled=="yes") {
			
			// Required fields check
			if (!$this->eid || !$this->secret) return false;
			
			// Checkout form check
			if (isset($woocommerce->cart->total)) {
			
				// Cart totals check - Lower threshold
				if ( $this->lower_threshold !== '' ) {
					if ( $woocommerce->cart->total < $this->lower_threshold ) return false;
				}
			
				// Cart totals check - Upper threshold
				if ( $this->upper_threshold !== '' ) {
					if ( $woocommerce->cart->total > $this->upper_threshold ) return false;
				}
			
				// Only activate the payment gateway if the customers country is the same as the filtered shop country ($this->klarna_country)
				if ( $woocommerce->customer->get_country() == true && $woocommerce->customer->get_country() != $this->klarna_country ) return false;
			
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
	   	
	   	?>
	   	
	   	<?php if ($this->testmode=='yes') : ?><p><?php _e('TEST MODE ENABLED', 'klarna'); ?></p>
	   	
	   	<?php 
	   	endif;
		
		// Description
		if ($this->description) :
			// apply_filters to the description so we can filter this if needed
			$klarna_description = $this->description;
			echo '<p>' . apply_filters( 'klarna_invoice_description', $klarna_description ) . '</p>';
		endif; 
		
		if ($this->invoice_fee_price>0) : ?><p><?php printf(__('An invoice fee of %1$s %2$s will be added to your order.', 'klarna'), $this->invoice_fee_price, $this->klarna_currency ); ?></p><?php endif; ?>
		
		<fieldset>
			<p class="form-row form-row-first">
				<?php if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) : ?>
				
				<label for="klarna_pno"><?php echo __("Date of Birth", 'klarna') ?> <span class="required">*</span></label>
				<span class="dob">
                    <select class="dob_select dob_day" name="klarna_invo_date_of_birth_day" style="width:60px;">
                        <option value="">
                        <?php echo __("Day", 'klarna') ?>
                        </option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        <option value="24">24</option>
                        <option value="25">25</option>
                        <option value="26">26</option>
                        <option value="27">27</option>
                        <option value="28">28</option>
                        <option value="29">29</option>
                        <option value="30">30</option>
                        <option value="31">31</option>
                    </select>
                    <select class="dob_select dob_month" name="klarna_invo_date_of_birth_month" style="width:80px;">
                        <option value="">
                        <?php echo __("Month", 'klarna') ?>
                        </option>
                        <option value="01"><?php echo __("Jan", 'klarna') ?></option>
                        <option value="02"><?php echo __("Feb", 'klarna') ?></option>
                        <option value="03"><?php echo __("Mar", 'klarna') ?></option>
                        <option value="04"><?php echo __("Apr", 'klarna') ?></option>
                        <option value="05"><?php echo __("May", 'klarna') ?></option>
                        <option value="06"><?php echo __("Jun", 'klarna') ?></option>
                        <option value="07"><?php echo __("Jul", 'klarna') ?></option>
                        <option value="08"><?php echo __("Aug", 'klarna') ?></option>
                        <option value="09"><?php echo __("Sep", 'klarna') ?></option>
                        <option value="10"><?php echo __("Oct", 'klarna') ?></option>
                        <option value="11"><?php echo __("Nov", 'klarna') ?></option>
                        <option value="12"><?php echo __("Dec", 'klarna') ?></option>
                    </select>
                    <select class="dob_select dob_year" name="klarna_invo_date_of_birth_year" style="width:60px;">
                        <option value="">
                        <?php echo __("Year", 'klarna') ?>
                        </option>
                        <option value="1920">1920</option>
                        <option value="1921">1921</option>
                        <option value="1922">1922</option>
                        <option value="1923">1923</option>
                        <option value="1924">1924</option>
                        <option value="1925">1925</option>
                        <option value="1926">1926</option>
                        <option value="1927">1927</option>
                        <option value="1928">1928</option>
                        <option value="1929">1929</option>
                        <option value="1930">1930</option>
                        <option value="1931">1931</option>
                        <option value="1932">1932</option>
                        <option value="1933">1933</option>
                        <option value="1934">1934</option>
                        <option value="1935">1935</option>
                        <option value="1936">1936</option>
                        <option value="1937">1937</option>
                        <option value="1938">1938</option>
                        <option value="1939">1939</option>
                        <option value="1940">1940</option>
                        <option value="1941">1941</option>
                        <option value="1942">1942</option>
                        <option value="1943">1943</option>
                        <option value="1944">1944</option>
                        <option value="1945">1945</option>
                        <option value="1946">1946</option>
                        <option value="1947">1947</option>
                        <option value="1948">1948</option>
                        <option value="1949">1949</option>
                        <option value="1950">1950</option>
                        <option value="1951">1951</option>
                        <option value="1952">1952</option>
                        <option value="1953">1953</option>
                        <option value="1954">1954</option>
                        <option value="1955">1955</option>
                        <option value="1956">1956</option>
                        <option value="1957">1957</option>
                        <option value="1958">1958</option>
                        <option value="1959">1959</option>
                        <option value="1960">1960</option>
                        <option value="1961">1961</option>
                        <option value="1962">1962</option>
                        <option value="1963">1963</option>
                        <option value="1964">1964</option>
                        <option value="1965">1965</option>
                        <option value="1966">1966</option>
                        <option value="1967">1967</option>
                        <option value="1968">1968</option>
                        <option value="1969">1969</option>
                        <option value="1970">1970</option>
                        <option value="1971">1971</option>
                        <option value="1972">1972</option>
                        <option value="1973">1973</option>
                        <option value="1974">1974</option>
                        <option value="1975">1975</option>
                        <option value="1976">1976</option>
                        <option value="1977">1977</option>
                        <option value="1978">1978</option>
                        <option value="1979">1979</option>
                        <option value="1980">1980</option>
                        <option value="1981">1981</option>
                        <option value="1982">1982</option>
                        <option value="1983">1983</option>
                        <option value="1984">1984</option>
                        <option value="1985">1985</option>
                        <option value="1986">1986</option>
                        <option value="1987">1987</option>
                        <option value="1988">1988</option>
                        <option value="1989">1989</option>
                        <option value="1990">1990</option>
                        <option value="1991">1991</option>
                        <option value="1992">1992</option>
                        <option value="1993">1993</option>
                        <option value="1994">1994</option>
                        <option value="1995">1995</option>
                        <option value="1996">1996</option>
                        <option value="1997">1997</option>
                        <option value="1998">1998</option>
                        <option value="1999">1999</option>
                        <option value="2000">2000</option>
                    </select>
                </span><!-- .dob -->
					
				<?php else : ?>
					<label for="klarna_invo_pno"><?php echo __("Date of Birth", 'klarna') ?> <span class="required">*</span></label>
					<input type="text" class="input-text" name="klarna_invo_pno" />
				<?php endif; ?>
			</p>
			
			<?php if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) : 
				
			?>
				<p class="form-row form-row-last">
					<label for="klarna_invo_gender"><?php echo __("Gender", 'klarna') ?> <span class="required">*</span></label>
					<select id="klarna_invo_gender" name="klarna_invo_gender" class="woocommerce-select" style="width:120px;">
						<option value=""><?php echo __("Select gender", 'klarna') ?></options>
						<option value="0"><?php echo __("Female", 'klarna') ?></options>
						<option value="1"><?php echo __("Male", 'klarna') ?></options>
					</select>
				</p>
			<?php endif; ?>
						
			<div class="clear"></div>
		
			<p><a id="klarna_invoice" onclick="ShowKlarnaInvoicePopup();return false;" href="#"><?php echo $this->get_invoice_terms_link_text($this->klarna_country); ?></a></p>
        	
			<div class="clear"></div>
		
			<?php if ( $this->shop_country == 'DE' && $this->de_consent_terms == 'yes' ) : ?>
				<p class="form-row">
					<label for="klarna_invo_de_consent_terms"></label>
					<input type="checkbox" class="input-checkbox" value="yes" name="klarna_invo_de_consent_terms" />
					<?php echo sprintf(__('Mit der Übermittlung der für die Abwicklungdes Rechnungskaufes und einer Identitäts-und Bonitätsprüfung erforderlichen Daten an Klarna bin ich einverstanden. Meine <a href="%s" target="_blank">Einwilligung</a> kann ich jederzeit mit Wirkung für die Zukunft widerrufen. Es gelten die AGB des Händlers.', 'klarna'), 'https://online.klarna.com/consent_de.yaws') ?>
					
				</p>
			<?php endif; ?>
			
		</fieldset>
		<?php	
	}
	
		

	
	
	/**
	 * Process the payment and return the result
	 **/
	function process_payment( $order_id ) {
		global $woocommerce;
		
		$order = new WC_order( $order_id );
		
		require_once(KLARNA_LIB . 'Klarna.php');
		require_once(KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc');
		require_once(KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc');
		
		// Get values from klarna form on checkout page
		
		// Collect the dob different depending on country
		if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) :
			$klarna_pno_day 			= isset($_POST['klarna_invo_date_of_birth_day']) ? woocommerce_clean($_POST['klarna_invo_date_of_birth_day']) : '';
			$klarna_pno_month 			= isset($_POST['klarna_invo_date_of_birth_month']) ? woocommerce_clean($_POST['klarna_invo_date_of_birth_month']) : '';
			$klarna_pno_year 			= isset($_POST['klarna_invo_date_of_birth_year']) ? woocommerce_clean($_POST['klarna_invo_date_of_birth_year']) : '';
			$klarna_pno 				= $klarna_pno_day . $klarna_pno_month . $klarna_pno_year;
		else :
			$klarna_pno 				= isset($_POST['klarna_invo_pno']) ? woocommerce_clean($_POST['klarna_invo_pno']) : '';
		endif;
		
		$klarna_gender 					= isset($_POST['klarna_invo_gender']) ? woocommerce_clean($_POST['klarna_invo_gender']) : '';
		$klarna_de_consent_terms		= isset($_POST['klarna_invo_de_consent_terms']) ? woocommerce_clean($_POST['klarna_invo_de_consent_terms']) : '';
		

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
		
		// Store Klarna specific form values in order as post meta
		update_post_meta( $order_id, 'klarna_pno', $klarna_pno);
		
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
			
			if ( function_exists( 'get_product' ) ) {
				
				// Version 2.0
				$_product = $order->get_product_from_item($item);
				
				// Get SKU or product id
					if ( $_product->get_sku() ) {
						$sku = $_product->get_sku();
					} else {
						$sku = $_product->id;
					}
					
			} else {
				
				// Version 1.6.6
				$_product = new WC_Product( $item['id'] );
				
				// Get SKU or product id
				if ( $_product->get_sku() ) {
					$sku = $_product->get_sku();
				} else {
					$sku = $item['id'];
				}
					
			}	
				
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
		    		$artNo = strval($sku),		 					//Article number
		    		$title = utf8_decode ($item['name']), 	//Article name/title
		    		$price = $item_price, 					// Price including tax
		    		$vat = round( $item_tax_percentage ),			// Tax
		    		$discount = 0, 
		    		$flags = KlarnaFlags::INC_VAT 			//Price is including VAT.
		    	);
									
		//endif;
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
		
		
		// Fees
		if ( sizeof( $order->get_fees() ) > 0 ) {
			foreach ( $order->get_fees() as $item ) {
			
			// We manually calculate the tax percentage here
			if ($order->get_total_tax() >0) :
				// Calculate tax percentage
				$item_tax_percentage = number_format( ( $item['line_tax'] / $item['line_total'] )*100, 2, '.', '');
			else :
				$item_tax_percentage = 0.00;
			endif;
			
			// apply_filters to item price so we can filter this if needed
			$klarna_item_price_including_tax = $item['line_total'];
			$item_price = apply_filters( 'klarna_fee_price_including_tax', $klarna_item_price_including_tax );
			
				$item_loop++;
				
				$k->addArticle(
				    $qty = 1,
				    $artNo = "",
				    $title = $item['name'],
				    $price = $item['line_total'],
				    $vat = round( $item_tax_percentage ),
				    $discount = 0,
			    	$flags = KlarnaFlags::INC_VAT + KlarnaFlags::IS_HANDLING //Price is including VAT and is handling/invoice fee
			    );
			    
			}
		}
             
        /*           
		// Invoice/handling fee
		
		// Get the invoice fee product if invoice fee is used
		if ( $this->invoice_fee_price > 0 ) {

			// We have already checked that the product exists in klarna_invoice_init()		
			// Version check - 1.6.6 or 2.0
			if ( function_exists( 'get_product' ) ) {
				$product = get_product($this->invoice_fee_id);
			} else {
				$product = new WC_Product( $this->invoice_fee_id );
			}
			
			if ( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) {
				
				// Pre 2.0				
				$k->addArticle(
				    $qty = 1,
				    $artNo = "",
				    $title = __('Handling Fee', 'klarna'),
				    $price = $this->invoice_fee_price,
				    $vat = round( $this->invoice_fee_tax_percentage ),
				    $discount = 0,
			    	$flags = KlarnaFlags::INC_VAT + KlarnaFlags::IS_HANDLING //Price is including VAT and is handling/invoice fee
			    );
			
			    // Add the invoice fee to the order
			    // Get all order items and unserialize the array
			    $originalarray = unserialize($order->order_custom_fields['_order_items'][0]);
			
			
			    // TODO: check that Invoice fee can't be added multiple times to order?
			    $addfee[] = array (
   					'id' => $this->invoice_fee_id,
   					'variation_id' => '',
   					'name' => $product->get_title(),
   					'qty' => '1',
   					'item_meta' => 
    					array (
    					),
    				'line_subtotal' => $product->get_price_excluding_tax(),
    				'line_subtotal_tax' => $product->get_price()-$product->get_price_excluding_tax(),
    				'line_total' => $product->get_price_excluding_tax(),
    				'line_tax' => $product->get_price()-$product->get_price_excluding_tax(),
    				'tax_class' => $product->get_tax_class(),
    			);
  				
    			// Merge the invoice fee product to order items
    			$newarray = array_merge($originalarray, $addfee);
    			
    			// Update order items with the added invoice fee product
    			update_post_meta( $order->id, '_order_items', $newarray );
    			
    			// Update _order_total
    			$old_order_total = $order->order_custom_fields['_order_total'][0];
    			$new_order_total = $old_order_total+$product->get_price();
    			update_post_meta( $order->id, '_order_total', $new_order_total );
    			
    			// Update _order_tax	
    			$invoice_fee_tax = $product->get_price()-$product->get_price_excluding_tax();
    			$old_order_tax = $order->order_custom_fields['_order_tax'][0];
    			$new_order_tax = $old_order_tax+$invoice_fee_tax;
    			update_post_meta( $order->id, '_order_tax', $new_order_tax );
    		
    		} else {
	    		
	    		// 2.0+				
				$k->addArticle(
				    $qty = 1,
				    $artNo = "",
				    $title = __('Handling Fee', 'klarna'),
				    $price = $this->invoice_fee_price,
				    $vat = round( $this->invoice_fee_tax_percentage ),
				    $discount = 0,
			    	$flags = KlarnaFlags::INC_VAT + KlarnaFlags::IS_HANDLING //Price is including VAT and is handling/invoice fee
			    );
			    
    		} // End version check
    		
		} // End invoice_fee_price > 0
			
		*/
		//Create the address object and specify the values.
		
		// Billing address
		$addr_billing = new KlarnaAddr(
    		$email = $order->billing_email,
    		$telno = '', //We skip the normal land line phone, only one is needed.
    		$cellno = $order->billing_phone,
    		//$company = utf8_decode ($order->billing_company),
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
		
		// Add Company if one is set
		if($order->billing_company)
			$addr_billing->setCompanyName($order->billing_company);
		
		// Shipping address
		if ( $order->get_shipping_method() == '' || $this->ship_to_billing_address == 'yes') {
			
			// Use billing address if Shipping is disabled in Woocommerce
			$addr_shipping = new KlarnaAddr(
    			$email = $order->billing_email,
    			$telno = '', //We skip the normal land line phone, only one is needed.
    			$cellno = $order->billing_phone,
    			//$company = utf8_decode ($order->billing_company),
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
    			//$company = utf8_decode ($order->shipping_company),
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
	
	
	/**
	 * Javascript for Invoice terms popup on checkout page
	 **/
	function klarna_invoice_terms_js() {
		if ( is_checkout() && $this->enabled=="yes" ) {
			?>
			<script type="text/javascript">
				var klarna_invoice_eid = "<?php echo $this->eid; ?>";
				var klarna_invoice_country = "<?php echo strtolower($this->klarna_country); ?>";
				var klarna_invoice_fee_price = "<?php echo $this->invoice_fee_price; ?>";
				addKlarnaInvoiceEvent(function(){InitKlarnaInvoiceElements('klarna_invoice', klarna_invoice_eid, klarna_invoice_country, klarna_invoice_fee_price); });
			</script>
			<?php
		}
	} // End function
	
	
	

	
	
	
	
	/**
	 * get_terms_invoice_link_text function.
	 * Helperfunction - Get Invoice Terms link text based on selected Billing Country in the Ceckout form
	 * Defaults to $this->klarna_country
	 * At the moment $this->klarna_country is allways returned. This will change in the next update.
	 **/
	 
	function get_invoice_terms_link_text($country) {
				
		switch ( $country )
		{
		case 'SE':
			$term_link_account = 'Villkor f&ouml;r faktura';
			break;
		case 'NO':
			$term_link_account = 'Vilk&aring;r for faktura';
			break;
		case 'DK':
			$term_link_account = 'Vilk&aring;r for faktura';
			break;
		case 'DE':
			$term_link_account = 'Rechnungsbedingungen';
			break;
		case 'FI':
			$term_link_account = 'Laskuehdot';
			break;
		case 'NL':
			$term_link_account = 'Factuurvoorwaarden';
			break;
		default:
			$term_link_account = __('Terms for Invoice', 'klarna');
		}
		
		return $term_link_account;
	} // end function get_account_terms_link_text()
	
	
	// Helper function - get Invoice fee id
	function get_klarna_invoice_fee_product() {
		return $this->invoice_fee_id;
	}
	
	// Helper function - get Shop Country
	function get_klarna_shop_country() {
		return $this->shop_country;
	}

} // End class WC_Gateway_Klarna_Invoice


/**
 * Class WC_Gateway_Klarna_Invoice_Extra
 * Extra class for functions that needs to be executed outside the payment gateway class.
 * Since version 1.5.4 (WooCommerce version 2.0)
**/

class WC_Gateway_Klarna_Invoice_Extra {

	public function __construct() {
		
		// Add Invoice fee via the new Fees API
		//add_action( 'woocommerce_checkout_process', array($this, 'add_invoice_fee_process') );
		
		// Add Invoice fee via the new Fees API
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'calculate_totals' ), 10, 1 );
		
		// Chcek Klarna specific fields on Checkout
		add_action('woocommerce_checkout_process', array(&$this, 'klarna_invoice_checkout_field_process'));
	}
	
	
	
	
	
	
		/**
	 * Calculate totals on checkout form.
	 **/
	 
	public function calculate_totals( $totals ) {
    	global $woocommerce;
		$available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();
		
		$current_gateway = '';
		if ( ! empty( $available_gateways ) ) {
			// Chosen Method
			if ( isset( $woocommerce->session->chosen_payment_method ) && isset( $available_gateways[ $woocommerce->session->chosen_payment_method ] ) ) {
				$current_gateway = $available_gateways[ $woocommerce->session->chosen_payment_method ];
			} elseif ( isset( $available_gateways[ get_option( 'woocommerce_default_gateway' ) ] ) ) {
            	$current_gateway = $available_gateways[ get_option( 'woocommerce_default_gateway' ) ];
			} else {
            	$current_gateway =  current( $available_gateways );

			}
			
		}
	
		if($current_gateway->id=='klarna'){
        	$current_gateway_id = $current_gateway -> id;
			
			$this->add_fee_to_cart();
			//add_action( 'woocommerce_review_order_before_order_total',  array( $this, 'add_payment_gateway_extra_charges_row'));
			//add_filter('woocommerce_cart_total', array( $this, 'add_fee_to_cart_total'));
			
			
		}
		
		return $totals;
	}
	
	
	
	/**
	 * Add the fee to the cart if Klarna is selected payment method and if a fee is used.
	 **/
	 function add_fee_to_cart() {
		 global $woocommerce;
		 
		 $invoice_fee = new WC_Gateway_Klarna_Invoice;
		 $this->invoice_fee_id = $invoice_fee->get_klarna_invoice_fee_product();
		 
		 // Only run this if Klarna is the choosen payment method and this is WC +2.0
		 //if ($_POST['payment_method'] == 'klarna' && version_compare( WOOCOMMERCE_VERSION, '2.0', '>=' )) {
		 			 	
		 	if ( $this->invoice_fee_id > 0 ) {
		 		$product = get_product($this->invoice_fee_id);
		 	
		 		if ( $product->exists() ) :
		 		
		 			// Is this a taxable product?
		 			if ( $product->is_taxable() ) {
			 			$product_tax = true;
			 		} else {
				 		$product_tax = false;
				 	}
    	   	 	
				 	$woocommerce->cart->add_fee($product->get_title(),$product->get_price_excluding_tax(),$product_tax,$product->get_tax_class());
				 	
    	    
				endif;
			} // End if invoice_fee_id > 0
		
		//}
	} // End function add_invoice_fee_process
	
	
	
	
	
	
	/**
	 * Add the invoice fee to the cart if Klarna Invoice is selected payment method, if this is WC 2.0 and if invoice fee is used.
	 **/
	 function add_invoice_fee_process() {
		 global $woocommerce;
		 	 
		 // Only run this if Klarna invoice is the choosen payment method and this is WC +2.0
		 if ($_POST['payment_method'] == 'klarna' && version_compare( WOOCOMMERCE_VERSION, '2.0', '>=' )) {
		 	
		 	$invoice_fee = new WC_Gateway_Klarna_Invoice;
		 	$this->invoice_fee_id = $invoice_fee->get_klarna_invoice_fee_product();
		 	
		 	if ( $this->invoice_fee_id > 0 ) {
		 		$product = get_product($this->invoice_fee_id);
		 	
		 		if ( $product->exists() ) :
		 		
		 			// Is this a taxable product?
		 			if ( $product->is_taxable() ) {
			 			$product_tax = true;
			 		} else {
				 		$product_tax = false;
				 	}
    	   	 	
				 	$woocommerce->cart->add_fee($product->get_title(),$product->get_price_excluding_tax(),$product_tax,$product->get_tax_class());
    	    
				endif;
			} // End if invoice_fee_id > 0
		
		}
	} // End function add_invoice_fee_process
	
	
	
	/**
 	 * Process the gateway specific checkout form fields
 	 **/
	function klarna_invoice_checkout_field_process() {
    	global $woocommerce;
    	
    	$data = new WC_Gateway_Klarna_Invoice;
		$this->shop_country = $data->get_klarna_shop_country();
 		
 		// Only run this if Klarna invoice is the choosen payment method
 		if ($_POST['payment_method'] == 'klarna') {
 			
 			// SE, NO, DK & FI
	 		if ( $this->shop_country == 'SE' || $this->shop_country == 'NO' || $this->shop_country == 'DK' || $this->shop_country == 'FI' ){
 			
    			// Check if set, if its not set add an error.
    			if (!$_POST['klarna_invo_pno'])
    	    	 	$woocommerce->add_error( __('<strong>Date of birth</strong> is a required field', 'klarna') );

			}
			// NL & DE
	 		if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ){
	    		// Check if set, if its not set add an error.
	    		
	    		// Gender
	    		if (!isset($_POST['klarna_invo_gender']))
	        	 	$woocommerce->add_error( __('<strong>Gender</strong> is a required field', 'klarna') );
	         	
	         	// Date of birth
				if (!$_POST['klarna_invo_date_of_birth_day'] || !$_POST['klarna_invo_date_of_birth_month'] || !$_POST['klarna_invo_date_of_birth_year'])
	         		$woocommerce->add_error( __('<strong>Date of birth</strong> is a required field', 'klarna') );
	         	
	         	// Shipping and billing address must be the same
	         	$klarna_shiptobilling = ( isset( $_POST['shiptobilling'] ) ) ? $_POST['shiptobilling'] : '';
	         	
	         	if ($klarna_shiptobilling !=1 && isset($_POST['shipping_first_name']) && $_POST['shipping_first_name'] !== $_POST['billing_first_name'])
	        	 	$woocommerce->add_error( __('Shipping and billing address must be the same when paying via Klarna.', 'klarna') );
	        	 
	        	 if ($klarna_shiptobilling !=1 && isset($_POST['shipping_last_name']) && $_POST['shipping_last_name'] !== $_POST['billing_last_name'])
	        	 	$woocommerce->add_error( __('Shipping and billing address must be the same when paying via Klarna', 'klarna') );
	        	 
	        	 if ($klarna_shiptobilling !=1 && isset($_POST['shipping_address_1']) && $_POST['shipping_address_1'] !== $_POST['billing_address_1'])
	        	 	$woocommerce->add_error( __('Shipping and billing address must be the same when paying via Klarna', 'klarna') );
	        	 
	        	 if ($klarna_shiptobilling !=1 && isset($_POST['shipping_postcode']) && $_POST['shipping_postcode'] !== $_POST['billing_postcode'])
	        	 	$woocommerce->add_error( __('Shipping and billing address must be the same when paying via Klarna', 'klarna') );
	        	 	
	        	 if ($klarna_shiptobilling !=1 && isset($_POST['shipping_city']) && $_POST['shipping_city'] !== $_POST['billing_city'])
	        	 	$woocommerce->add_error( __('Shipping and billing address must be the same when paying via Klarna', 'klarna') );
			}
			
			// DE
			if ( $this->shop_country == 'DE' && $this->de_consent_terms == 'yes'){
	    		// Check if set, if its not set add an error.
	    		if (!isset($_POST['klarna_invo_de_consent_terms']))
	        	 	$woocommerce->add_error( __('You must accept the Klarna consent terms.', 'klarna') ); 	
			}
		}
	} // End function klarna_invoice_checkout_field_process
}
$wc_klarna_invoice_extra = new WC_Gateway_Klarna_Invoice_Extra;