<?php
/**
 * Klarna plugin shortcodes
 *
 * @link http://www.woothemes.com/products/klarna/
 * @since 1.0.0
 *
 * @package WC_Gateway_Klarna
 */


/**
 * Class for Klarna shortodes.
 */
class WC_Gateway_Klarna_Shortcodes {

	/**
	 * Return Monthly price
	 * 
	 * @return string $klarna_account_shortcode_price
	 */
	function return_price() {

		global $klarna_account_shortcode_price;
		return $klarna_account_shortcode_price;

	}

	/**
	 * Return Currency
	 * 
	 * @return string $klarna_account_shortcode_currency
	 */
	function return_currency() {

		global $klarna_account_shortcode_currency;
		return $klarna_account_shortcode_currency;

	}

	/**
	 * Return Klarna basic image
	 * 
	 * @return string full image HTML code
	 */
	function return_basic_img() {

		global $klarna_shortcode_img;
		return '<img class="klarna-logo-img" src="' . $klarna_shortcode_img . '" />';

	}

	/**
	 * Return Account info popup link
	 * 
	 * @return string account info popup link
	 */
	function return_account_info_link() {

		global $klarna_account_country;

		ob_start();
		echo '<a id="klarna_partpayment" onclick="ShowKlarnaPartPaymentPopup(); return false;" href="#">' . /*WC_Gateway_Klarna_Account::get_account_terms_link_text( $klarna_account_country ) .*/ 'hh</a>';
		echo '';
		$output_string = ob_get_clean();

		return $output_string;

	}

}

add_shortcode( 'klarna_price',             array( 'WC_Gateway_Klarna_Shortcodes', 'return_price' ) );
add_shortcode( 'klarna_currency',          array( 'WC_Gateway_Klarna_Shortcodes', 'return_currency' ) );
add_shortcode( 'klarna_img',               array( 'WC_Gateway_Klarna_Shortcodes', 'return_basic_img' ) );
add_shortcode( 'klarna_account_info_link', array( 'WC_Gateway_Klarna_Shortcodes', 'return_account_info_link' ) );