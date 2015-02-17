<?php
/**
 * This file is used to markup payment fields displayed in checkout page.
 *
 * @link  http://www.woothemes.com/products/klarna/
 * @since 1.0.0
 * @todo  Change part_payment and klarna_account_pclass to variables.
 *
 * @package WC_Gateway_Klarna
 */
?>

<fieldset>
	<?php
	$klarna_pms = new WC_Klarna_PMS;
	if ( $this->testmode == 'yes' ) {
		$klarna_mode = 'test';
	} else {
		$klarna_mode = 'live';
	}
	$klarna_pms_data = $klarna_pms->get_data(
		$this->get_eid(),            // $eid
		$this->get_secret(),         // $secret
		$this->selected_currency,    // $selected_currency
		$this->get_klarna_country(), // $shop_country
		$woocommerce->cart->total,   // $cart_total
		$payment_method_group,       // $payment_method_group
		$payment_method_select_id,   // $select_id,
		$klarna_mode                 // $klarna_mode
	);
	echo $klarna_pms_data;
	?>

	<p class="form-row form-row-first">
		<label for="klarna_pno"><?php echo __( 'Date of Birth', 'klarna' ) ?> <span class="required">*</span></label>
		<input type="text" class="input-text" id="klarna_pno" name="klarna_pno" />

		<?php
		// Button/form for getAddress
		$data = new WC_Klarna_Get_Address;
		echo $data->get_address_button( $this->get_klarna_country() );
		?>
	</p>
</fieldset>