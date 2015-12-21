<?php
/**
 * This process the fields in checkout page.
 *
 * @link http://www.woothemes.com/products/klarna/
 * @since 1.0.0
 *
 * @package WC_Gateway_Klarna
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * For Sweden, Norway, Denmark and Finland check personal number.
 */
if (
	isset( $_POST['billing_country'] ) && 
	( $_POST['billing_country'] == 'SE' || 
	$_POST['billing_country'] == 'NO' || 
	$_POST['billing_country'] == 'DK' || 
	$_POST['billing_country'] == 'FI' )
) {
	// Check if set, if its not set add an error.
	if ( empty( $_POST[$klarna_field_prefix . 'pno'] ) ) {
		wc_add_notice(
			__( '<strong>Date of birth</strong> is a required field.', 'woocommerce-gateway-klarna' ), 
			'error'
		);
	}
}

/**
 * For Netherlands and Germany check gender and DoB.
 */
if ( 
	isset( $_POST['billing_country'] ) && 
	( $_POST['billing_country'] == 'NL' || 
	$_POST['billing_country'] == 'DE' ||
	$_POST['billing_country'] == 'AT' )
) {
	// Check if gender is set, if not add an error
	if ( empty( $_POST[$klarna_field_prefix . 'gender'] ) ) {
		wc_add_notice(
			__( '<strong>Gender</strong> is a required field.', 'woocommerce-gateway-klarna' ), 
			'error'
		);
	}
	
	// Check if date of birth is set, if not add an error
	if ( empty( $_POST[$klarna_field_prefix . 'date_of_birth_day'] ) || empty( $_POST[$klarna_field_prefix . 'date_of_birth_month'] ) || empty( $_POST[$klarna_field_prefix . 'date_of_birth_year'] ) ) {
		wc_add_notice(
			__( '<strong>Date of birth</strong> is a required field.', 'woocommerce-gateway-klarna' ), 
			'error'
		);
	}

	// Check if shipping and billing address are the same
	$compare_billing_and_shipping = 0;

	if ( isset( $_POST['ship_to_different_address'] ) && $_POST['ship_to_different_address'] = 1 ) {
		$compare_billing_and_shipping = 1;	
	}

	if ( $compare_billing_and_shipping == 1 && isset( $_POST['billing_first_name'] ) && isset( $_POST['shipping_first_name'] ) && $_POST['shipping_first_name'] !== $_POST['billing_first_name'] ) {
		wc_add_notice(
			__( 'Shipping and billing address must be the same when paying via Klarna.', 'woocommerce-gateway-klarna' ), 
			'error'
		);
	}

	if ( $compare_billing_and_shipping == 1 && isset( $_POST['billing_last_name'] ) && isset( $_POST['shipping_last_name'] ) && $_POST['shipping_last_name'] !== $_POST['billing_last_name'] ) {
		wc_add_notice(
			__( 'Shipping and billing address must be the same when paying via Klarna.', 'woocommerce-gateway-klarna' ), 
			'error'
		);
	}

	if ( $compare_billing_and_shipping == 1 && isset( $_POST['billing_address_1'] ) && isset( $_POST['shipping_address_1'] ) && $_POST['shipping_address_1'] !== $_POST['billing_address_1'] ) {
		wc_add_notice(
			__( 'Shipping and billing address must be the same when paying via Klarna.', 'woocommerce-gateway-klarna' ), 
			'error'
		);
	}

	if ( $compare_billing_and_shipping == 1 && isset( $_POST['billing_postcode'] ) && isset( $_POST['shipping_postcode'] ) && $_POST['shipping_postcode'] !== $_POST['billing_postcode'] ) {
		wc_add_notice(
			__( 'Shipping and billing address must be the same when paying via Klarna.', 'woocommerce-gateway-klarna' ), 
			'error'
		);
	}

	if ( $compare_billing_and_shipping == 1 && isset( $_POST['billing_city'] ) && isset( $_POST['shipping_city'] ) && $_POST['shipping_city'] !== $_POST['billing_city'] ) {
		wc_add_notice(
			__( 'Shipping and billing address must be the same when paying via Klarna.', 'woocommerce-gateway-klarna' ), 
			'error'
		);
	}
}

/**
 * For Austria and Germany check consent terms.
 */
if ( 
	( $this->shop_country == 'DE' || $this->shop_country == 'AT' ) && 
	$this->de_consent_terms == 'yes'
) {
	// Check if set, if its not set add an error.
	if ( empty( $_POST[$klarna_field_prefix . 'de_consent_terms'] ) ) {
		wc_add_notice(
			__( 'You must accept the Klarna consent terms.', 'woocommerce-gateway-klarna' ), 
			'error'
		);
	}
}
