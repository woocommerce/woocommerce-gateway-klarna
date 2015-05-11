<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User set variables for Klarna Checkout
 */

// Define user set variables
$this->enabled = ( isset( $this->settings['enabled'] ) ) ? $this->settings['enabled'] : '';
$this->title = ( isset( $this->settings['title'] ) ) ? $this->settings['title'] : '';
$this->log = new WC_Logger();

$this->eid_se = ( isset( $this->settings['eid_se'] ) ) ? $this->settings['eid_se'] : '';
$this->secret_se = ( isset( $this->settings['secret_se'] ) ) ? $this->settings['secret_se'] : '';
$this->klarna_checkout_url_se = ( isset( $this->settings['klarna_checkout_url_se'] ) ) ? $this->settings['klarna_checkout_url_se'] : '';
$this->klarna_checkout_thanks_url_se = ( isset( $this->settings['klarna_checkout_thanks_url_se'] ) ) ? $this->settings['klarna_checkout_thanks_url_se'] : '';

$this->eid_no = ( isset( $this->settings['eid_no'] ) ) ? $this->settings['eid_no'] : '';
$this->secret_no = ( isset( $this->settings['secret_no'] ) ) ? $this->settings['secret_no'] : '';
$this->klarna_checkout_url_no = ( isset( $this->settings['klarna_checkout_url_no'] ) ) ? $this->settings['klarna_checkout_url_no'] : '';
$this->klarna_checkout_thanks_url_no = ( isset( $this->settings['klarna_checkout_thanks_url_no'] ) ) ? $this->settings['klarna_checkout_thanks_url_no'] : '';

$this->eid_fi = ( isset( $this->settings['eid_fi'] ) ) ? $this->settings['eid_fi'] : '';
$this->secret_fi = ( isset( $this->settings['secret_fi'] ) ) ? $this->settings['secret_fi'] : '';
$this->klarna_checkout_url_fi = ( isset( $this->settings['klarna_checkout_url_fi'] ) ) ? $this->settings['klarna_checkout_url_fi'] : '';
$this->klarna_checkout_thanks_url_fi = ( isset( $this->settings['klarna_checkout_thanks_url_fi'] ) ) ? $this->settings['klarna_checkout_thanks_url_fi'] : '';

$this->eid_de = ( isset( $this->settings['eid_de'] ) ) ? $this->settings['eid_de'] : '';
$this->secret_de = ( isset( $this->settings['secret_de'] ) ) ? $this->settings['secret_de'] : '';
$this->klarna_checkout_url_de = ( isset( $this->settings['klarna_checkout_url_de'] ) ) ? $this->settings['klarna_checkout_url_de'] : '';
$this->klarna_checkout_thanks_url_de = ( isset( $this->settings['klarna_checkout_thanks_url_de'] ) ) ? $this->settings['klarna_checkout_thanks_url_de'] : '';
$this->phone_mandatory_de = ( isset( $this->settings['phone_mandatory_de'] ) ) ? $this->settings['phone_mandatory_de'] : '';
$this->dhl_packstation_de = ( isset( $this->settings['dhl_packstation_de'] ) ) ? $this->settings['dhl_packstation_de'] : '';

$this->default_eur_contry = ( isset( $this->settings['default_eur_contry'] ) ) ? $this->settings['default_eur_contry'] : '';

$this->eid_uk = ( isset( $this->settings['eid_uk'] ) ) ? $this->settings['eid_uk'] : '';
$this->secret_uk = ( isset( $this->settings['secret_uk'] ) ) ? $this->settings['secret_uk'] : '';
$this->klarna_checkout_url_uk = ( isset( $this->settings['klarna_checkout_url_uk'] ) ) ? $this->settings['klarna_checkout_url_uk'] : '';
$this->klarna_checkout_thanks_url_uk = ( isset( $this->settings['klarna_checkout_thanks_url_uk'] ) ) ? $this->settings['klarna_checkout_thanks_url_uk'] : '';

$this->terms_url = ( isset( $this->settings['terms_url'] ) ) ? $this->settings['terms_url'] : '';
$this->testmode = ( isset( $this->settings['testmode'] ) ) ? $this->settings['testmode'] : '';
$this->debug = ( isset( $this->settings['debug'] ) ) ? $this->settings['debug'] : '';

$this->modify_standard_checkout_url = ( isset( $this->settings['modify_standard_checkout_url'] ) ) ? $this->settings['modify_standard_checkout_url'] : '';
$this->add_std_checkout_button = ( isset( $this->settings['add_std_checkout_button'] ) ) ? $this->settings['add_std_checkout_button'] : '';
$this->std_checkout_button_label = ( isset( $this->settings['std_checkout_button_label'] ) ) ? $this->settings['std_checkout_button_label'] : '';

$this->create_customer_account = ( isset( $this->settings['create_customer_account'] ) ) ? $this->settings['create_customer_account'] : '';
$this->send_new_account_email = ( isset( $this->settings['send_new_account_email'] ) ) ? $this->settings['send_new_account_email'] : '';

$this->account_signup_text = ( isset( $this->settings['account_signup_text'] ) ) ? $this->settings['account_signup_text'] : '';
$this->account_login_text = ( isset( $this->settings['account_login_text'] ) ) ? $this->settings['account_login_text'] : '';

// Color options
$this->color_button = ( isset( $this->settings['color_button'] ) ) ? $this->settings['color_button'] : '';
$this->color_button_text = ( isset( $this->settings['color_button_text'] ) ) ? $this->settings['color_button_text'] : '';
$this->color_checkbox = ( isset( $this->settings['color_checkbox'] ) ) ? $this->settings['color_checkbox'] : '';
$this->color_checkbox_checkmark = ( isset( $this->settings['color_checkbox_checkmark'] ) ) ? $this->settings['color_checkbox_checkmark'] : '';
$this->color_header = ( isset( $this->settings['color_header'] ) ) ? $this->settings['color_header'] : '';
$this->color_link = ( isset( $this->settings['color_link'] ) ) ? $this->settings['color_link'] : '';

if ( empty($this->terms_url) ) 
	$this->terms_url = esc_url( get_permalink(woocommerce_get_page_id('terms')) );
	
	// Check if this is test mode or not
if ( $this->testmode == 'yes' ):
	$this->klarna_server = 'https://checkout.testdrive.klarna.com/checkout/orders';	
else :
	$this->klarna_server = 'https://checkout.klarna.com/checkout/orders';
endif;

// Set current country based on used currency
switch ( get_woocommerce_currency() ) {
	
	case 'NOK' :
		$klarna_country = 'NO';
		break;
	case 'EUR' :
		if( get_locale() == 'de_DE' ) {
			$klarna_country = 'DE';
		} elseif( get_locale() == 'fi' ) {
			$klarna_country = 'FI';
		} else {
			$klarna_country = $this->default_eur_contry;
		}
		break;
	case 'SEK' :
		$klarna_country = 'SE';
		break;
	case 'GBP' :
		$klarna_country = 'GB';
		break;
	default:
		$klarna_country = '';
}

$this->shop_country	= $klarna_country;

// Country and language
switch ( $this->shop_country ) {
	case 'NO' :
	case 'NB' :
	//case 'NOK' :
		$klarna_country 			= 'NO';
		$klarna_language 			= 'nb-no';
		$klarna_currency 			= 'NOK';
		$klarna_eid 				= $this->eid_no;
		$klarna_secret 				= $this->secret_no;
		$klarna_checkout_url 		= $this->klarna_checkout_url_no;
		if ($this->klarna_checkout_thanks_url_no == '' ) {
			$klarna_checkout_thanks_url 	= $this->klarna_checkout_url_no;
		} else {
			$klarna_checkout_thanks_url 	= $this->klarna_checkout_thanks_url_no;
		}
		break;
	case 'FI' :
	//case 'EUR' :
		$klarna_country 			= 'FI';
		
		// Check if WPML is used and determine if Finnish or Swedish is used as language
		if ( class_exists( 'woocommerce_wpml' ) && defined('ICL_LANGUAGE_CODE') && strtoupper(ICL_LANGUAGE_CODE) == 'SV') {
			// Swedish
			$klarna_language 			= 'sv-fi';
		} else {
			// Finnish
			$klarna_language 			= 'fi-fi';
		}
		
		$klarna_currency 			= 'EUR';
		$klarna_eid 				= $this->eid_fi;
		$klarna_secret 				= $this->secret_fi;
		$klarna_checkout_url 		= $this->klarna_checkout_url_fi;
		if ($this->klarna_checkout_thanks_url_fi == '' ) {
			$klarna_checkout_thanks_url 	= $this->klarna_checkout_url_fi;
		} else {
			$klarna_checkout_thanks_url 	= $this->klarna_checkout_thanks_url_fi;
		}
		break;
	case 'SE' :
	case 'SV' :
	//case 'SEK' :
		$klarna_country 			= 'SE';
		
		
		$klarna_language 			= 'sv-se';
		$klarna_currency 			= 'SEK';
		$klarna_eid 				= $this->eid_se;
		$klarna_secret 				= $this->secret_se;
		$klarna_checkout_url 		= $this->klarna_checkout_url_se;
		if ($this->klarna_checkout_thanks_url_se == '' ) {
			$klarna_checkout_thanks_url 	= $this->klarna_checkout_url_se;
		} else {
			$klarna_checkout_thanks_url 	= $this->klarna_checkout_thanks_url_se;
		}
		break;
	case 'DE' :
		$klarna_country 			= 'DE';
		$klarna_language 			= 'de-de';
		$klarna_currency 			= 'EUR';
		$klarna_eid 				= $this->eid_de;
		$klarna_secret 				= $this->secret_de;
		$klarna_checkout_url 		= $this->klarna_checkout_url_de;
		if ($this->klarna_checkout_thanks_url_de == '' ) {
			$klarna_checkout_thanks_url 	= $this->klarna_checkout_url_de;
		} else {
			$klarna_checkout_thanks_url 	= $this->klarna_checkout_thanks_url_de;
		}
		break;
	case 'GB' :
		$klarna_country 			= 'gb';
		$klarna_language 			= 'en-gb';
		$klarna_currency 			= 'gbp';
		$klarna_eid 				= $this->eid_uk;
		$klarna_secret 				= $this->secret_uk;
		$klarna_checkout_url 		= $this->klarna_checkout_url_uk;
		if ($this->klarna_checkout_thanks_url_uk == '' ) {
			$klarna_checkout_thanks_url 	= $this->klarna_checkout_url_uk;
		} else {
			$klarna_checkout_thanks_url 	= $this->klarna_checkout_thanks_url_uk;
		}
		break;
	default:
		$klarna_country = '';
		$klarna_language = '';
		$klarna_currency = '';
		$klarna_eid = '';
		$klarna_secret = '';
		$klarna_checkout_url = '';
		$klarna_invoice_terms = '';
		$klarna_invoice_icon = '';
		$klarna_checkout_thanks_url = '';
}

$this->authorized_countries	= array();
if ( ! empty( $this->eid_se ) ) {
	$this->authorized_countries[] = 'SE';
}
if ( ! empty( $this->eid_no ) ) {
	$this->authorized_countries[] = 'NO';
}
if ( ! empty( $this->eid_fi ) ) {
	$this->authorized_countries[] = 'FI';
}
if ( ! empty( $this->eid_de ) ) {
	$this->authorized_countries[] = 'DE';
}
if ( ! empty( $this->eid_de ) ) {
	$this->authorized_countries[] = 'UK';
}


// Apply filters to Country and language
$this->klarna_country 				= apply_filters( 'klarna_country', $klarna_country );
$this->klarna_language 				= apply_filters( 'klarna_language', $klarna_language );
$this->klarna_currency 				= apply_filters( 'klarna_currency', $klarna_currency );
$this->klarna_eid					= apply_filters( 'klarna_eid', $klarna_eid );
$this->klarna_secret				= apply_filters( 'klarna_secret', $klarna_secret );
$this->klarna_checkout_url			= apply_filters( 'klarna_checkout_url', $klarna_checkout_url );
$this->klarna_checkout_thanks_url	= apply_filters( 'klarna_checkout_thanks_url', $klarna_checkout_thanks_url );

global $klarna_checkout_thanks_url;
$klarna_checkout_thanks_url = $this->klarna_checkout_thanks_url;