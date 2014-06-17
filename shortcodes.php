<?php

// Shortcodes for display cost/month
add_shortcode( 'klarna_price', 'return_klarna_price' );
add_shortcode( 'klarna_currency', 'return_klarna_currency' );
add_shortcode( 'klarna_img', 'return_klarna_basic_img' );
add_shortcode( 'klarna_account_info_link', 'return_klarna_account_info_link' );

// Return Monthly price
function return_klarna_price() {
	global $klarna_account_shortcode_price;
	return $klarna_account_shortcode_price;
}

// Return Currency
function return_klarna_currency() {
	global $klarna_account_shortcode_currency;
	return $klarna_account_shortcode_currency;
}

// Return Klarna basic image
function return_klarna_basic_img() {
	global $klarna_shortcode_img;
	return '<img class="klarna-logo-img" src="' . $klarna_shortcode_img . '" />';
}

// Return Account info popup link
function return_klarna_account_info_link() {
	global $klarna_account_country;
	//global $klarna_account_shortcode_info_link;	
	//return '<a id="klarna_partpayment" onclick="ShowKlarnaPartPaymentPopup();return false;" href="#">' . __('Read more', 'klarna') . '</a>';
	
	ob_start();
	echo '<a id="klarna_partpayment" onclick="ShowKlarnaPartPaymentPopup();return false;" href="#">' . /*WC_Gateway_Klarna_Account::get_account_terms_link_text($klarna_account_country) .*/ 'hh</a>';
	echo '';
	$output_string = ob_get_clean();
	return $output_string;
}
?>