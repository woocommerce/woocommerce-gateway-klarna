<?php
/**
 * Plugin shortcodes
 *
 * @package WC_Gateway_Klarna
 */

/**
 * Return Monthly price
 * 
 * @return string $klarna_account_shortcode_price
 */
function return_klarna_price() {

	global $klarna_account_shortcode_price;
	return $klarna_account_shortcode_price;

}
add_shortcode( 'klarna_price', 'return_klarna_price' );

/**
 * Return Currency
 * 
 * @return string $klarna_account_shortcode_currency
 */
function return_klarna_currency() {

	global $klarna_account_shortcode_currency;
	return $klarna_account_shortcode_currency;

}
add_shortcode( 'klarna_currency', 'return_klarna_currency' );

/**
 * Return Klarna basic image
 * 
 * @return string full image HTML code
 */
function return_klarna_basic_img() {

	global $klarna_shortcode_img;
	return '<img class="klarna-logo-img" src="' . $klarna_shortcode_img . '" />';

}
add_shortcode( 'klarna_img', 'return_klarna_basic_img' );

/**
 * Return Account info popup link
 * 
 * @return string account info popup link
 */
function return_klarna_account_info_link() {

	global $klarna_account_country;

	ob_start();
	echo '<a id="klarna_partpayment" onclick="ShowKlarnaPartPaymentPopup(); return false;" href="#">' . /*WC_Gateway_Klarna_Account::get_account_terms_link_text( $klarna_account_country ) .*/ 'hh</a>';
	echo '';
	$output_string = ob_get_clean();

	return $output_string;

}
add_shortcode( 'klarna_account_info_link', 'return_klarna_account_info_link' );