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
		
		add_action('admin_init', array(&$this, 'update_pclasses_from_klarna'));
		
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
	    	
		    
		    
		    <?php
		    // Check if the pclasses.json file exist
		    $klarna_filename = KLARNA_DIR . 'srv/pclasses.json';
		    $klarna_filename_path = KLARNA_DIR . 'srv/';

			if (file_exists($klarna_filename)) {
    			echo '<p>';
    			echo sprintf(__('The file pclasses.json does exist on your web server. You can update the file by clicking the button below or create the file manually and upload it to <i>%s</i>. Note that read and write permissions for the directory <i>srv</i> and the containing file <i>pclasses.json</i> must be set to 777 in order to fetch the available PClasses from Klarna. This does not apply if you manually upload your pclasses.json file via ftp.', 'klarna'),$klarna_filename_path);
				echo '</p>';
    			/*
    			if (is_writable ( $klarna_filename )) {
    				echo __("Writable.", 'klarna');
    			} else {
	    			echo __("NOT Writable.", 'klarna');
    			}
    			*/
			} else {
				
				echo '<div class="error inline">';
    			echo sprintf(__('The file pclasses.json does not exist on your web server. This is needed to store your Klarna PClasses. Either create and update the file by clicking the button below or create the file manually and upload it to <i>%s</i>. Note that read and write permissions for the directory <i>srv</i> and the containing file <i>pclasses.json</i> must be set to 777 in order to fetch the available PClasses from Klarna. This does not apply if you manually upload your pclasses.json file via ftp.', 'klarna'),$klarna_filename_path);
    			echo '</div>';
			}
			
			if (isset($_GET['klarna_error_status']) && $_GET['klarna_error_status'] == '0') {
				// pclasses.json file saved sucessfully
				echo '<div class="updated">The file pclasses.json was sucessfully updated.</div>';
			}
			
			if (isset($_GET['klarna_error_status']) && $_GET['klarna_error_status'] == '1') {
				// pclasses.json file could not be updated
				echo '<div class="error">The file pclasses.json could not be updated. Klarna error code: ' . $_GET['klarna_error_code'] . '</div>';
			}
			?>
			<p>
		    <a class="button" href="<?php echo admin_url('admin.php?page=woocommerce_settings&klarnaPclassListener=1');?>">Update the PClass file pclasses.json</a>
		    
		    </p>
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
			// if (!in_array(get_option('woocommerce_default_country'), array('DK', 'DE', 'FI', 'NL', 'NO', 'SE'))) return false;
			
			// Required fields check
			if (!$this->eid || !$this->secret) return false;
			
			return true;
					
		endif;	
	
		return false;
	}
	

		/**
 		* Retrieve the PClasses from Klarna and store it in the file pclasses.json.
 		*/
 		function update_pclasses_from_klarna( ) {
 		
 		global $woocommerce;

 		if (isset($_GET['klarnaPclassListener']) && $_GET['klarnaPclassListener'] == '1'):
 		
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
	   
			// Check if the pclasses.json file exist
		    $klarna_pclass_file = KLARNA_DIR . 'srv/pclasses.json';

			if (!file_exists($klarna_pclass_file)) {
    			$file=fopen($klarna_pclass_file,"w") or exit("Unable to open file!");
    			fclose($file);
    			/*
    			if (is_writable ( $klarna_filename )) {
    				echo __("Writable.", 'klarna');
    			} else {
	    			echo __("NOT Writable.", 'klarna');
    			}
    			*/
    		}

				
			try {
			    $k->fetchPClasses($this->klarna_country); //You can specify country (and language, currency if you wish) if you don't want to use the configured country.
			    /* PClasses successfully fetched, now you can use getPClasses() to load them locally or getPClass to load a specific PClass locally. */
				// Redirect to settings page
				wp_redirect(admin_url('admin.php?page=woocommerce_settings&tab=payment_gateways&subtab=gateway-klarna_account&klarna_error_status=0'));
				}
				catch(Exception $e) {
				    //Something went wrong, print the message:
				    // $woocommerce->add_error( sprintf(__('Klarna PClass problem: %s. Error code: ', 'klarna'), utf8_encode($e->getMessage()) ) . '"' . $e->getCode() . '"' );
				    //$klarna_error_code = utf8_encode($e->getMessage()) . 'Error code: ' . $e->getCode();
				    
				    $redirect_url = 'admin.php?page=woocommerce_settings&tab=payment_gateways&subtab=gateway-klarna_account&klarna_error_status=1&klarna_error_code=' . $e->getCode();
				    
				    //wp_redirect(admin_url($redirect_url));
				    wp_redirect(admin_url($redirect_url));
				}
				
			endif;
				
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
		<?php 
		if ($this->description) :
			// apply_filters to the description so we can filter this if needed
			$klarna_description = $this->description;
			echo '<p>' . apply_filters( 'klarna_account_description', $klarna_description ) . '</p>';
		endif; 
		?>
		
		<fieldset>
			<p class="form-row form-row-first">
			
				<?php
				// Check if we have any PClasses
				// TODO Deactivate this gateway if the file pclasses.json doesn't exist 
				if($k->getPClasses()) {
				?>
						<label for="klarna_pclass"><?php echo __("Payment plan", 'klarna') ?> <span class="required">*</span></label><br/>
						<select id="klarna_pclass" name="klarna_pclass" class="woocommerce-select">
						
						<?php
				    	// Loop through the available PClasses stored in the file srv/pclasses.json
						foreach ($k->getPClasses() as $pclass) {
				   			echo '<option value="' . $pclass->getId() . '">'. $pclass->getDescription() . '</option>';
						}
						?>
						
						</select>
				<?php
				} else {
					echo __('Klarna PClasses seem to be missing. Klarna Account does not work.', 'klarna');
				}
				?>
						
				
			</p>
			<?php
			// Calculate monthly cost and display it
			
			// apply_filters to cart total so we can filter this if needed
			$klarna_cart_total = $woocommerce->cart->total;
			$sum = apply_filters( 'klarna_cart_total', $klarna_cart_total ); // Cart total.
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
	    		// apply_filters to the monthly cost message so we can filter this if needed
	    		
	    		$klarna_account_monthly_cost_message = sprintf(__('From %s %s/month', 'klarna'), $value, $this->klarna_currency );
	    		echo '<p class="form-row form-row-last klarna-monthly-cost">' . apply_filters( 'klarna_account_monthly_cost_message', $klarna_account_monthly_cost_message ) . '</p>';
	    		
			}
			?>
			<div class="clear"></div>
			
			<p class="form-row form-row-first">
				<?php if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) : ?>
				
				<label for="klarna_pno"><?php echo __("Date of Birth", 'klarna') ?> <span class="required">*</span></label>
                    <select class="dob_select dob_day" name="date_of_birth_day" style="width:60px;">
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
                    <select class="dob_select dob_month" name="date_of_birth_month" style="width:80px;">
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
                    <select class="dob_select dob_year" name="date_of_birth_year" style="width:60px;">
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
					<label for="klarna_pno"><?php echo __("Date of Birth", 'klarna') ?> <span class="required">*</span></label>
					<input type="text" class="input-text" name="klarna_pno" />
				<?php endif; ?>
			</p>
			
			<?php if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) : ?>
				<p class="form-row form-row-last">
					<label for="klarna_gender"><?php echo __("Gender", 'klarna') ?> <span class="required">*</span></label>
					<select id="klarna_gender" name="klarna_gender" class="woocommerce-select" style="width:120px;">
						<option value=""><?php echo __("Select gender", 'klarna') ?></options>
						<option value="MALE"><?php echo __("Male", 'klarna') ?></options>
						<option value="FEMALE"><?php echo __("Female", 'klarna') ?></options>
					</select>
				</p>
			<?php endif; ?>
			
			<div class="clear"></div>
			
			<?php if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) : ?>	
				<p class="form-row form-row-first">
					<label for="klarna_house_number"><?php echo __("House Number", 'klarna') ?> <span class="required">*</span></label>
					<input type="text" class="input-text" name="klarna_house_number" />
				</p>
			<?php endif; ?>
			
			<?php if ( $this->shop_country == 'NL' ) : ?>
				<p class="form-row form-row-last">
					<label for="klarna_house_extension"><?php echo __("House Extension", 'klarna') ?></label>
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
		
		// Collect the dob different depending on country
		if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) :
			$klarna_pno_day 			= isset($_POST['date_of_birth_day']) ? woocommerce_clean($_POST['date_of_birth_day']) : '';
			$klarna_pno_month 			= isset($_POST['date_of_birth_month']) ? woocommerce_clean($_POST['date_of_birth_month']) : '';
			$klarna_pno_year 			= isset($_POST['date_of_birth_year']) ? woocommerce_clean($_POST['date_of_birth_year']) : '';
			$klarna_pno 		= $klarna_pno_day . $klarna_pno_month . $klarna_pno_year;
		else :
			$klarna_pno 			= isset($_POST['klarna_pno']) ? woocommerce_clean($_POST['klarna_pno']) : '';
		endif;
		
		$klarna_pclass 			= isset($_POST['klarna_pclass']) ? woocommerce_clean($_POST['klarna_pclass']) : '';
		$klarna_gender 			= isset($_POST['klarna_gender']) ? woocommerce_clean($_POST['klarna_gender']) : '';
		$klarna_house_number	= isset($_POST['klarna_house_number']) ? woocommerce_clean($_POST['klarna_house_number']) : '';
		$klarna_house_extension	= isset($_POST['klarna_house_extension']) ? woocommerce_clean($_POST['klarna_house_extension']) : '';
		
		
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
				
					$k->addArticle(
		    		$qty = $item['qty'], 					//Quantity
		    		$artNo = $item['id'], 					//Article number
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