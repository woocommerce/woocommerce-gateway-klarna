<?php

class WC_Gateway_Klarna_Campaign extends WC_Gateway_Klarna {
	
	/**
     * Class for Klarna Campaign payment.
     *
     */
     
	public function __construct() {
		global $woocommerce;
		
		parent::__construct();
		
		$this->id			= 'klarna_special_campaign';
		$this->method_title = __('Klarna Special Campaign', 'klarna');
		$this->has_fields 	= true;
		
		// Klarna warning banner - used for NL only
		$klarna_wb_img_checkout = 'http://www.afm.nl/~/media/Images/wetten-regels/kredietwaarschuwing/balk_afm1-jpg.ashx';
		
		// Load the form fields.
		$this->init_form_fields();
		
		// Load the settings.
		$this->init_settings();
		
		

		// Define user set variables
		$this->enabled								= ( isset( $this->settings['enabled'] ) ) ? $this->settings['enabled'] : '';
		$this->title 								= ( isset( $this->settings['title'] ) ) ? $this->settings['title'] : '';
		$this->description  						= ( isset( $this->settings['description'] ) ) ? $this->settings['description'] : '';
		$this->icon_url  							= ( isset( $this->settings['icon_url'] ) ) ? $this->settings['icon_url'] : '';
		$this->eid									= ( isset( $this->settings['eid'] ) ) ? $this->settings['eid'] : '';
		$this->secret								= ( isset( $this->settings['secret'] ) ) ? $this->settings['secret'] : '';
		$this->lower_threshold						= ( isset( $this->settings['lower_threshold'] ) ) ? $this->settings['lower_threshold'] : '';
		$this->upper_threshold						= ( isset( $this->settings['upper_threshold'] ) ) ? $this->settings['upper_threshold'] : '';
		$this->testmode								= ( isset( $this->settings['testmode'] ) ) ? $this->settings['testmode'] : '';
		$this->de_consent_terms						= ( isset( $this->settings['de_consent_terms'] ) ) ? $this->settings['de_consent_terms'] : '';
		
		// Icon for payment method
		if ( $this->icon_url == '' ) {
			$this->icon 		= apply_filters( 'klarna_campaign_icon', '' );
		} else {
			$this->icon 		= apply_filters( 'klarna_campaign_icon', $this->icon_url );
		}
		
		
		// Country and language
		switch ( $this->shop_country )
		{
		case 'DK':
			$klarna_country = 'DK';
			$klarna_language = 'DA';
			$klarna_currency = 'DKK';
			$klarna_campaign_info = 'https://online.klarna.com/account_dk.yaws?eid=' . $this->eid;
			break;
		case 'DE' :
			$klarna_country = 'DE';
			$klarna_language = 'DE';
			$klarna_currency = 'EUR';
			$klarna_campaign_info = 'https://online.klarna.com/account_de.yaws?eid=' . $this->eid;
			break;
		case 'NL' :
			$klarna_country = 'NL';
			$klarna_language = 'NL';
			$klarna_currency = 'EUR';
			$klarna_campaign_info = 'https://online.klarna.com/account_nl.yaws?eid=' . $this->eid;
			break;
		case 'NO' :
			$klarna_country = 'NO';
			$klarna_language = 'NB';
			$klarna_currency = 'NOK';
			$klarna_campaign_info = 'https://online.klarna.com/account_no.yaws?eid=' . $this->eid;
			break;
		case 'FI' :
			$klarna_country = 'FI';
			$klarna_language = 'FI';
			$klarna_currency = 'EUR';
			$klarna_campaign_info = 'https://online.klarna.com/account_fi.yaws?eid=' . $this->eid;
			break;
		case 'SE' :
			$klarna_country = 'SE';
			$klarna_language = 'SV';
			$klarna_currency = 'SEK';
			$klarna_campaign_info = 'https://online.klarna.com/account_se.yaws?eid=' . $this->eid;
			break;
		default:
			$klarna_country = '';
			$klarna_language = '';
			$klarna_currency = '';
			$klarna_campaign_info = '';
			$klarna_warning_banner = '';
		}
		
		
		
		// Apply filters to Country and language
		$this->klarna_country 				= apply_filters( 'klarna_country', $klarna_country );
		$this->klarna_language 				= apply_filters( 'klarna_language', $klarna_language );
		$this->klarna_currency 				= apply_filters( 'klarna_currency', $klarna_currency );
		$this->klarna_campaign_info 		= apply_filters( 'klarna_campaign_info', $klarna_campaign_info );
		$this->klarna_wb_img_checkout		= apply_filters( 'klarna_wb_img_checkout', $klarna_wb_img_checkout );
		
		
		// Actions
		
		/* 1.6.6 */
		add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
 
		/* 2.0.0 */
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		
		add_action('woocommerce_receipt_klarna_campaign', array(&$this, 'receipt_page'));
		
		add_action('woocommerce_checkout_process', array(&$this, 'klarna_campaign_checkout_field_process'));
		
		add_action('wp_footer', array(&$this, 'klarna_special_terms_js'));
				
	}
	
	
	
	
		
	/**
	 * Initialise Gateway Settings Form Fields
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
							'description' => __( 'Disable Klarna Special Campaigns if Cart Total is lower than the specified value. Leave blank to disable this feature.', 'klarna' ), 
							'default' => ''
						),
			'upper_threshold' => array(
							'title' => __( 'Upper threshold', 'klarna' ), 
							'type' => 'text', 
							'description' => __( 'Disable Klarna Special Campaigns if Cart Total is higher than the specified value. Leave blank to disable this feature.', 'klarna' ), 
							'default' => ''
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
    	<h3><?php _e('Klarna Special Campaign', 'klarna'); ?></h3>
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
		
		if ($this->enabled=="yes") :
		
			// PClass check
			
			// Check if the pclasses.json file exist
		    $klarna_filename = KLARNA_DIR . 'srv/pclasses.json';

			if (file_exists($klarna_filename)) {
			
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
					
				if($k->getPClasses()) {
					
					// PClasses exists. Check if they are valid for Special Campaigns 
					$find_special_campaign = 0;
					
					// Loop through the available PClasses stored in the file srv/pclasses.json
					foreach ($k->getPClasses() as $pclass) {
						
						// Check if there are any Special Campaign classes and that these classes is still active
						if ( $pclass->getType() == 2 && $pclass->getExpire() >= time() )
	       	    			$find_special_campaign++;
					}
				
					// All PClasses have been checked
					// If $find_special_campaign == 0 then we did not find any valid PClass for Special Campaign
					if ( $find_special_campaign == 0)
						return false;
				
				} else {
				
					// No PClasses available for Special Campaigns
					return false;
			
				}
				
			} else {
				
				// pclasses.json does not exist
				return false;
				
			} // End file_exists
		
		
			// if (!is_ssl()) return false;
			
			// Currency check
			// if (!in_array(get_option('woocommerce_currency'), array('DKK', 'EUR', 'NOK', 'SEK'))) return false;
			
			// Base country check
			// if (!in_array(get_option('woocommerce_default_country'), array('DK', 'DE', 'FI', 'NL', 'NO', 'SE'))) return false;
			
			// Required fields check
			if (!$this->eid || !$this->secret) return false;
			
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
		
		// apply_filters to cart total so we can filter this if needed
		$klarna_cart_total = $woocommerce->cart->total;
		$sum = apply_filters( 'klarna_cart_total', $klarna_cart_total ); // Cart total.
		$flag = KlarnaFlags::CHECKOUT_PAGE; //or KlarnaFlags::PRODUCT_PAGE, if you want to do it for one item.
	   	
	   	?>
	   	
	   	<?php if ($this->testmode=='yes') : ?><p><?php _e('TEST MODE ENABLED', 'klarna'); ?></p><?php endif; ?>
		<?php
		// Description
		if ($this->description) :
			// apply_filters to the description so we can filter this if needed
			$klarna_description = $this->description;
			echo '<p>' . apply_filters( 'klarna_campaign_description', $klarna_description ) . '</p>';
		endif;
		
		// Show klarna_warning_banner if NL
		if ( $this->shop_country == 'NL' ) {
			echo '<p><img src="' . $this->klarna_wb_img_checkout . '" class="klarna-wb"/></p>';	
		}
		?>
		
		<fieldset>
			<p class="form-row form-row-first">
			
				<?php
				// Check if we have any PClasses
				// TODO Deactivate this gateway if the file pclasses.json doesn't exist 
				if($k->getPClasses()) {
				?>
					<label for="klarna_campaign_pclass"><?php echo __("Payment plan", 'klarna') ?> <span class="required">*</span></label><br/>
					<select id="klarna_campaign_pclass" name="klarna_campaign_pclass" class="woocommerce-select">
						
					<?php
				   	// Loop through the available PClasses stored in the file srv/pclasses.json
					foreach ($k->getPClasses() as $pclass) {
						
						if ( $pclass->getType() == 2 ) {
							// Get monthly cost for current pclass
							$monthly_cost = KlarnaCalc::calc_monthly_cost(
    	    									$sum,
    	    									$pclass,
    	    									$flag
    	    								);
    									
    	    				// Get total credit purchase cost for current pclass
    	    				$total_credit_purchase_cost = KlarnaCalc::total_credit_purchase_cost(
    	    									$sum,
    	    									$pclass,
    	    									$flag
    	    								);
    					
    	    				// Check that Cart total is larger than min amount for current PClass				
    	    				if($sum > $pclass->getMinAmount()) {				
	    	    				
			   					echo '<option value="' . $pclass->getId() . '">';
				   					echo sprintf(__('%s - Start %s', 'klarna'), $pclass->getDescription(), $pclass->getStartFee() );
			   					echo '</option>';
				   		
			   				} // End if ($sum > $pclass->getMinAmount())
			   			
			   			} // End if ( $pclass->getType() == 2 )
					
					} // End foreach
					?>
						
					</select>
				
					<?php
				} else {
					echo __('Klarna PClasses seem to be missing. Klarna Campaign does not work.', 'klarna');
				}
				?>				
				
			</p>
			<?php
			// Calculate lowest monthly cost and display it
			
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
	    		// apply_filters to the monthly cost message so we can filter this if needed
	    		
	    		$klarna_campaign_monthly_cost_message = sprintf(__('From %s %s/month', 'klarna'), $value, $this->klarna_currency );
	    		echo '<p class="form-row form-row-last klarna-monthly-cost">' . apply_filters( 'klarna_campaign_monthly_cost_message', $klarna_campaign_monthly_cost_message ) . '</p>';
	    		
			
			}
			?>
			<div class="clear"></div>
			
			<p class="form-row form-row-first">
				<?php if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) : ?>
				
				<label for="klarna_campaign_pno"><?php echo __("Date of Birth", 'klarna') ?> <span class="required">*</span></label>
                    <select class="dob_select dob_day" name="klarna_campaign_date_of_birth_day" style="width:60px;">
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
                    <select class="dob_select dob_month" name="klarna_campaign_date_of_birth_month" style="width:80px;">
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
                    <select class="dob_select dob_year" name="klarna_campaign_date_of_birth_year" style="width:60px;">
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
					
				<?php else : ?>
					<label for="klarna_campaign_pno"><?php echo __("Date of Birth", 'klarna') ?> <span class="required">*</span></label>
					<input type="text" class="input-text" name="klarna_campaign_pno" />
				<?php endif; ?>
			</p>
			
			<?php if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) : ?>
				<p class="form-row form-row-last">
					<label for="klarna_campaign_gender"><?php echo __("Gender", 'klarna') ?> <span class="required">*</span></label>
					<select id="klarna_campaign_gender" name="klarna_campaign_gender" class="woocommerce-select" style="width:120px;">
						<option value=""><?php echo __("Select gender", 'klarna') ?></options>
						<option value="0"><?php echo __("Female", 'klarna') ?></options>
						<option value="1"><?php echo __("Male", 'klarna') ?></options>
					</select>
				</p>
			<?php endif; ?>
			
			<div class="clear"></div>
		
		
			
			
			<p><a id="klarna_specialpayment" onclick="ShowKlarnaSpecialPaymentPopup();return false;" href="#"><?php echo $this->get_terms_link_text($this->klarna_country); ?></a></p>
			<?php if ( $this->shop_country == 'DE' && $this->de_consent_terms == 'yes' ) : ?>
				<p class="form-row">
					<label for="klarna_de_terms"></label>
					<input type="checkbox" class="input-checkbox" value="yes" name="klarna_de_consent_terms" />
					<?php echo sprintf(__('Mit der Übermittlung der für die Abwicklungdes Rechnungskaufes und einer Identitäts-und Bonitätsprüfung erforderlichen Daten an Klarna bin ich einverstanden. Meine <a href="%s" target="_blank">Einwilligung</a> kann ich jederzeit mit Wirkung für die Zukunft widerrufen. Es gelten die AGB des Händlers.', 'klarna'), 'https://online.klarna.com/consent_de.yaws') ?>
					
				</p>
			<?php endif; ?>
			<div class="clear"></div>
		</fieldset>
		<?php	
	}
	
	
	/**
 	 * Process the gateway specific checkout form fields
 	**/
	function klarna_campaign_checkout_field_process() {
    	global $woocommerce;
 		
 		// Only run this if Klarna account is the choosen payment method
 		if ($_POST['payment_method'] == 'klarna_special_campaign') {
 		
 			// SE, NO, DK & FI
 			if ( $this->shop_country == 'SE' || $this->shop_country == 'NO' || $this->shop_country == 'DK' || $this->shop_country == 'FI' ){
 			
    			// Check if set, if its not set add an error.
    			if (!$_POST['klarna_campaign_pno'])
        		 	$woocommerce->add_error( __('<strong>Date of birth</strong> is a required field', 'klarna') );
        	 	
			}
			
			// NL & DE
	 		if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ){
	    		// Check if set, if its not set add an error.
	    		
	    		// Gender
	    		if (!isset($_POST['klarna_campaign_gender']))
	        	 	$woocommerce->add_error( __('<strong>Gender</strong> is a required field', 'klarna') );
	         	
	         	// Date of birth
				if (!$_POST['date_of_birth_day'] || !$_POST['date_of_birth_month'] || !$_POST['date_of_birth_year'])
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
	    		if (!isset($_POST['klarna_de_consent_terms']))
	        	 	$woocommerce->add_error( __('You must accept the Klarna consent terms.', 'klarna') ); 	
			}
		}
	}
	
	
	/**
	 * Process the payment and return the result
	 **/
	function process_payment( $order_id ) {
		global $woocommerce;
		
		$order = &new WC_order( $order_id );
		
		require_once(KLARNA_LIB . 'Klarna.php');
		require_once(KLARNA_LIB . 'pclasses/storage.intf.php');
		require_once(KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc');
		require_once(KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc');
		
		// Get values from klarna form on checkout page
		
		// Collect the dob different depending on country
		if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) :
			$klarna_pno_day 			= isset($_POST['klarna_campaign_date_of_birth_day']) ? woocommerce_clean($_POST['klarna_campaign_date_of_birth_day']) : '';
			$klarna_pno_month 			= isset($_POST['klarna_campaign_date_of_birth_month']) ? woocommerce_clean($_POST['klarna_campaign_date_of_birth_month']) : '';
			$klarna_pno_year 			= isset($_POST['klarna_campaign_date_of_birth_year']) ? woocommerce_clean($_POST['klarna_campaign_date_of_birth_year']) : '';
			$klarna_pno 		= $klarna_pno_day . $klarna_pno_month . $klarna_pno_year;
		else :
			$klarna_pno 			= isset($_POST['klarna_campaign_pno']) ? woocommerce_clean($_POST['klarna_campaign_pno']) : '';
		endif;
		
		$klarna_pclass 				= isset($_POST['klarna_campaign_pclass']) ? woocommerce_clean($_POST['klarna_campaign_pclass']) : '';
		$klarna_gender 				= isset($_POST['klarna_campaign_gender']) ? woocommerce_clean($_POST['klarna_campaign_gender']) : '';
		$klarna_de_consent_terms	= isset($_POST['klarna_campaign_de_consent_terms']) ? woocommerce_clean($_POST['klarna_campaign_de_consent_terms']) : '';
		
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
		    		$artNo = $sku,		 					//Article number
		    		$title = utf8_decode ($item['name']), 	//Article name/title
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
	 * Javascript for Special Campaign info/terms popup on checkout page
	 **/
	function klarna_special_terms_js() {
		
		if ( is_checkout() && $this->enabled=="yes" ) {
			?>
			<script type="text/javascript">
				var klarna_eid = "<?php echo $this->eid; ?>";
				var klarna_special_linktext = "<?php echo $this->get_terms_link_text($this->klarna_country); ?>";
				var klarna_special_country = "<?php echo $this->get_terms_country(); ?>";
				addKlarnaSpecialPaymentEvent(function(){InitKlarnaSpecialPaymentElements('klarna_specialpayment', klarna_eid, klarna_special_country, klarna_special_linktext, 0); });
			</script>
			<?php
		}
	}
	
	
	/**
	* get_terms_country function.
 	* Helperfunction - Get Terms Country based on selected Billing Country in the Ceckout form
 	* Defaults to $this->klarna_country
 	* At the moment $this->klarna_country is allways returned. This will change in the next update.
 	**/
	
	function get_terms_country() {
		global $woocommerce;
			
		if ( $woocommerce->customer->get_country() == true && in_array( $woocommerce->customer->get_country(), array('SE', 'NO', 'DK', 'DE', 'FI', 'NL') ) ) {
			
			// 
			//return strtolower($woocommerce->customer->get_country());
			return strtolower($this->klarna_country);
			
		} else {
		
			return strtolower($this->klarna_country);
		
		}
	} // End function get_terms_country()
	
	
	/**
	 * get_terms_link_text function.
	 * Helperfunction - Get Terms link text based on selected Billing Country in the Ceckout form
	 * Defaults to $this->klarna_country
	 * At the moment $this->klarna_country is allways returned. This will change in the next update.
	 **/
	 
	function get_terms_link_text($country) {
				
		switch ( $country )
		{
		case 'SE':
			$term_link = 'Läs mer';
			break;
		case 'NO':
			$term_link = 'Les mer';
			break;
		case 'DK':
			$term_link = 'L&aelig;s mere';
			break;
		case 'DE':
			$term_link = 'Lesen Sie mehr!';
			break;
		case 'FI':
			$term_link = 'Lue lis&auml;&auml;';
			break;
		case 'NL':
			$term_link = 'Lees meer!';
			break;
		default:
			$term_link = __('Read more', 'klarna');
		}
		
		return $term_link;
	} // end function get_account_terms_link_text()
	
		 
	
} // End class WC_Gateway_Klarna_Campaign