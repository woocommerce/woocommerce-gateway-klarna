<?php
/**
 * This file is used to markup payment fields displayed in checkout page.
 *
 * @link http://www.woothemes.com/products/klarna/
 * @since 1.0.0
 *
 * @package WC_Gateway_Klarna
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Use Klarna PMS for Norway
if ( 'NO' == $this->klarna_helper->get_klarna_country() ) { ?>
	<fieldset>
		<?php
		$klarna_pms = new WC_Klarna_PMS;
		if ( $this->testmode == 'yes' ) {
			$klarna_mode = 'test';
		} else {
			$klarna_mode = 'live';
		}
		global $woocommerce;
		$klarna_pms_data = $klarna_pms->get_data( $this->klarna_helper->get_eid(),            // $eid
			$this->klarna_helper->get_secret(),         // $secret
			$this->selected_currency,                   // $selected_currency
			$this->klarna_helper->get_klarna_country(), // $shop_country
			$woocommerce->cart->total,                  // $cart_total
			'part_payment',                             // $payment_method_group
			'klarna_part_payment_pclass',               // $select_id,
			$klarna_mode,                               // $klarna_mode
			false );
		echo $klarna_pms_data;
		?>

		<p class="form-row form-row-wide">
			<label
				for="klarna_part_payment_pno"><?php echo esc_attr( __( 'Date of Birth', 'woocommerce-gateway-klarna' ) ); ?>
				<span class="required">*</span></label>
			<input type="text" class="input-text" id="klarna_part_payment_pno" name="klarna_part_payment_pno"
			       placeholder="<?php _e( 'DDMMYYXXXXX', 'woocommerce-gateway-klarna' ); ?>" />

			<?php
			// Button/form for getAddress
			$data = new WC_Klarna_Get_Address;
			echo $data->get_address_button( $this->klarna_helper->get_klarna_country() );
			?>
		</p>

	</fieldset>

	<?php
	// For countries other than NO do the old thing
} else {
	// Show klarna_warning_banner if NL
	if ( $this->klarna_helper->get_klarna_country() == 'NL' ) {
		echo '<p><img src="' . $this->klarna_wb_img_checkout . '" class="klarna-wb" style="max-width:100%;float:none;max-height:none"/></p>';
	}

	// Mobile or desktop browser
	if ( wp_is_mobile() ) {
		$klarna_layout = 'mobile';
	} else {
		$klarna_layout = 'desktop';
	}
	// Script for displaying the terms link
	?>

	<script type="text/javascript">
		// Document ready
		jQuery(document).ready(function ($) {
			var klarna_part_payment_selected_country = $("#billing_country").val();

			// If no Billing Country is set in the checkout form, use the default shop country
			if (!klarna_part_payment_selected_country) {
				var klarna_part_payment_selected_country = '<?php echo $this->shop_country;?>';
			}

			if (klarna_part_payment_selected_country == 'SE') {
				var klarna_part_payment_current_locale = 'sv_SE';
			} else if (klarna_part_payment_selected_country == 'NO') {
				var klarna_part_payment_current_locale = 'nb_NO';
			} else if (klarna_part_payment_selected_country == 'DK') {
				var klarna_part_payment_current_locale = 'da_DK';
			} else if (klarna_part_payment_selected_country == 'FI') {
				var klarna_part_payment_current_locale = 'fi_FI';
			} else if (klarna_part_payment_selected_country == 'DE') {
				var klarna_part_payment_current_locale = 'de_DE';
			} else if (klarna_part_payment_selected_country == 'NL') {
				var klarna_part_payment_current_locale = 'nl_NL';
			} else if (klarna_part_payment_selected_country == 'AT') {
				var klarna_part_payment_current_locale = 'de_AT';
			} else {
				var klarna_part_payment_current_locale = 'en_GB';
			}

			new Klarna.Terms.Account({
				el: 'klarna-account-terms',
				eid: '<?php echo $this->klarna_helper->get_eid(); ?>',
				locale: klarna_part_payment_current_locale,
				type: '<?php echo $klarna_layout;?>',
			});
		});
	</script>
	<span id="klarna-account-terms"></span>
	<div class="clear"></div>

	<fieldset>
		<p class="form-row form-row-wide">
			<?php
			$country = $this->klarna_helper->get_klarna_country();
			$klarna  = new Klarna();
			$this->configure_klarna( $klarna, $country );

			$klarna_pclasses = new WC_Gateway_Klarna_PClasses( $klarna, $pclass_type, $country );
			$pclasses        = $klarna_pclasses->get_pclasses_for_country_and_type();

			if ( $pclasses ) { ?>

				<label for="<?php echo esc_attr( $klarna_select_pclass_element ); ?>">
					<?php echo __( "Payment plan", 'woocommerce-gateway-klarna' ) ?> <span class="required">*</span>
				</label>

				<select id="<?php echo esc_attr( $klarna_select_pclass_element ); ?>"
				        name="<?php echo esc_attr( $klarna_select_pclass_element ); ?>" class="woocommerce-select"
				        style="max-width:100%;width:100% !important;">

					<?php foreach ( $pclasses as $pclass ) { // Loop through the available PClasses stored in the file srv/pclasses.json

						if ( in_array( $pclass->getType(), $pclass_type ) ) {
							// Get monthly cost for current pclass
							$monthly_cost = KlarnaCalc::calc_monthly_cost( $sum, $pclass, $flag );

							// Get total credit purchase cost for current pclass (only required in Norway)
							$total_credit_purchase_cost = KlarnaCalc::total_credit_purchase_cost( $sum, $pclass, $flag );

							// Check that Cart total is larger than min amount for current PClass
							if ( $sum > $pclass->getMinAmount() ) {
								echo '<option value="' . $pclass->getId() . '">';
								if ( $pclass->getType() == 1 ) {
									// If Account - Do not show startfee. This is always 0.
									echo sprintf( __( '%s - %s %s/month - %s%s', 'woocommerce-gateway-klarna' ), $pclass->getDescription(), $monthly_cost, $this->selected_currency, $pclass->getInterestRate(), '%' );
								} else {
									// Sweden, Denmark, Finland, Germany & Netherlands - Don't show total cost
									echo sprintf( __( '%s - %s %s/month - %s%s - Start %s', 'woocommerce-gateway-klarna' ), $pclass->getDescription(), $monthly_cost, $this->selected_currency, $pclass->getInterestRate(), '%', $pclass->getStartFee() );
								}
								echo '</option>';

							} // End if ($sum > $pclass->getMinAmount())

						} // End PClass type check

					} // End foreach
					?>
				</select>

			<?php } else {
				echo __( 'Klarna PClasses seem to be missing. Klarna Part Payment does not work.', 'woocommerce-gateway-klarna' );
			} ?>
		</p>
		<div class="clear"></div>

		<p class="form-row form-row-wide" id="klarna-part-payment-get-address">
			<?php if ( $this->klarna_helper->get_klarna_country() == 'NL' || $this->klarna_helper->get_klarna_country() == 'DE' ) { ?>
				<label for="<?php echo esc_attr( $klarna_dob_element ); ?>">
					<?php echo __( "Date of Birth", 'woocommerce-gateway-klarna' ) ?> <span class="required">*</span>
				</label>
				<select class="dob_select dob_day" name="klarna_part_payment_date_of_birth_day" style="width:60px;">
					<option value=""><?php echo __( "Day", 'woocommerce-gateway-klarna' ) ?></option>
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
				<select class="dob_select dob_month" name="klarna_part_payment_date_of_birth_month" style="width:80px;">
					<option value=""><?php echo __( "Month", 'woocommerce-gateway-klarna' ) ?></option>
					<option value="01"><?php echo __( "Jan", 'woocommerce-gateway-klarna' ) ?></option>
					<option value="02"><?php echo __( "Feb", 'woocommerce-gateway-klarna' ) ?></option>
					<option value="03"><?php echo __( "Mar", 'woocommerce-gateway-klarna' ) ?></option>
					<option value="04"><?php echo __( "Apr", 'woocommerce-gateway-klarna' ) ?></option>
					<option value="05"><?php echo __( "May", 'woocommerce-gateway-klarna' ) ?></option>
					<option value="06"><?php echo __( "Jun", 'woocommerce-gateway-klarna' ) ?></option>
					<option value="07"><?php echo __( "Jul", 'woocommerce-gateway-klarna' ) ?></option>
					<option value="08"><?php echo __( "Aug", 'woocommerce-gateway-klarna' ) ?></option>
					<option value="09"><?php echo __( "Sep", 'woocommerce-gateway-klarna' ) ?></option>
					<option value="10"><?php echo __( "Oct", 'woocommerce-gateway-klarna' ) ?></option>
					<option value="11"><?php echo __( "Nov", 'woocommerce-gateway-klarna' ) ?></option>
					<option value="12"><?php echo __( "Dec", 'woocommerce-gateway-klarna' ) ?></option>
				</select>
				<select class="dob_select dob_year" name="klarna_part_payment_date_of_birth_year" style="width:60px;">
					<option value=""><?php echo __( "Year", 'woocommerce-gateway-klarna' ) ?></option>
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

			<?php } else { // Swedish is here ?>
				<label
					for="<?php echo esc_attr( $klarna_dob_element ); ?>"><?php echo __( 'Date of Birth', 'woocommerce-gateway-klarna' ) ?>
					<span class="required">*</span></label>
				<input type="text" class="input-text" id="<?php echo esc_attr( $klarna_dob_element ); ?>"
				       name="<?php echo esc_attr( $klarna_dob_element ); ?>" placeholder="<?php _e( 'YYMMDD-XXXX', 'woocommerce-gateway-klarna' ); ?>" />
			<?php }
			// Button/form for getAddress
			$data = new WC_Klarna_Get_Address;
			echo $data->get_address_button( $this->klarna_helper->get_klarna_country() );
			?>
		</p>

		<?php if ( $this->klarna_helper->get_klarna_country() == 'NL' || $this->klarna_helper->get_klarna_country() == 'DE' ) { ?>
			<p class="form-row form-row-wide">
				<label for="klarna_part_payment_gender">
					<?php echo __( "Gender", 'woocommerce-gateway-klarna' ) ?> <span class="required">*</span>
				</label>
				<select id="klarna_part_payment_gender" name="klarna_part_payment_gender" class="woocommerce-select"
				        style="width:120px;">
					<option value=""><?php echo __( "Select gender", 'woocommerce-gateway-klarna' ) ?></option>
					<option value="f"><?php echo __( "Female", 'woocommerce-gateway-klarna' ) ?></option>
					<option value="m"><?php echo __( "Male", 'woocommerce-gateway-klarna' ) ?></option>
				</select>
			</p>
		<?php } ?>
		<div class="clear"></div>

		<?php if ( ( $this->klarna_helper->get_klarna_country() == 'DE' || $this->klarna_helper->get_klarna_country() == 'AT' ) && $this->de_consent_terms == 'yes' ) { // Consent terms for German & Austrian shops ?>
			<p class="form-row form-row-wide">
				<label for="klarna_part_payment_de_consent_terms">
				<input type="checkbox" class="input-checkbox" value="yes" name="klarna_part_payment_de_consent_terms"  id="klarna_part_payment_de_consent_terms" />
				<?php echo sprintf( __( 'Mit der Übermittlung der für die Abwicklung der gewählten Klarna Zahlungsmethode und einer Identitäts- und Bonitätsprüfung erforderlichen Daten an Klarna bin ich einverstanden. Meine <a href="%s" target="_blank">Einwilligung</a> kann ich jederzeit mit Wirkung für die Zukunft widerrufen. Es gelten die AGB des Händlers.', 'woocommerce-gateway-klarna' ), 'https://online.klarna.com/consent_de.yaws' ) ?>
				</label>
			</p>
		<?php } ?>
		<div class="clear"></div>
	</fieldset>
<?php } ?>