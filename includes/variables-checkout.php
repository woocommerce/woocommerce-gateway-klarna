<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * User set variables for Klarna Checkout
 */

// Define user set variables
$this->enabled = ( isset( $this->settings['enabled'] ) ) ? $this->settings['enabled'] : '';
$this->title   = ( isset( $this->settings['title'] ) ) ? $this->settings['title'] : '';
$this->log     = new WC_Logger();

$this->push_completion   = ( isset( $this->settings['push_completion'] ) ) ? $this->settings['push_completion'] : '';
$this->push_cancellation = ( isset( $this->settings['push_cancellation'] ) ) ? $this->settings['push_cancellation'] : '';
$this->push_update       = ( isset( $this->settings['push_update'] ) ) ? $this->settings['push_update'] : '';

$this->eid_se                        = ( isset( $this->settings['eid_se'] ) ) ? $this->settings['eid_se'] : '';
$this->secret_se                     = ( isset( $this->settings['secret_se'] ) ) ? $this->settings['secret_se'] : '';
$this->klarna_checkout_url_se        = ( isset( $this->settings['klarna_checkout_url_se'] ) ) ? $this->settings['klarna_checkout_url_se'] : '';
$this->klarna_checkout_thanks_url_se = ( isset( $this->settings['klarna_checkout_thanks_url_se'] ) ) ? $this->settings['klarna_checkout_thanks_url_se'] : '';

$this->eid_no                        = ( isset( $this->settings['eid_no'] ) ) ? $this->settings['eid_no'] : '';
$this->secret_no                     = ( isset( $this->settings['secret_no'] ) ) ? $this->settings['secret_no'] : '';
$this->klarna_checkout_url_no        = ( isset( $this->settings['klarna_checkout_url_no'] ) ) ? $this->settings['klarna_checkout_url_no'] : '';
$this->klarna_checkout_thanks_url_no = ( isset( $this->settings['klarna_checkout_thanks_url_no'] ) ) ? $this->settings['klarna_checkout_thanks_url_no'] : '';

$this->eid_fi                        = ( isset( $this->settings['eid_fi'] ) ) ? $this->settings['eid_fi'] : '';
$this->secret_fi                     = ( isset( $this->settings['secret_fi'] ) ) ? $this->settings['secret_fi'] : '';
$this->klarna_checkout_url_fi        = ( isset( $this->settings['klarna_checkout_url_fi'] ) ) ? $this->settings['klarna_checkout_url_fi'] : '';
$this->klarna_checkout_thanks_url_fi = ( isset( $this->settings['klarna_checkout_thanks_url_fi'] ) ) ? $this->settings['klarna_checkout_thanks_url_fi'] : '';

$this->eid_dk                        = ( isset( $this->settings['eid_dk'] ) ) ? $this->settings['eid_dk'] : '';
$this->secret_dk                     = ( isset( $this->settings['secret_dk'] ) ) ? html_entity_decode( $this->settings['secret_dk'] ) : '';
$this->klarna_checkout_url_dk        = ( isset( $this->settings['klarna_checkout_url_dk'] ) ) ? $this->settings['klarna_checkout_url_dk'] : '';
$this->klarna_checkout_thanks_url_dk = ( isset( $this->settings['klarna_checkout_thanks_url_dk'] ) ) ? $this->settings['klarna_checkout_thanks_url_dk'] : '';

$this->eid_nl                        = ( isset( $this->settings['eid_nl'] ) ) ? $this->settings['eid_nl'] : '';
$this->secret_nl                     = ( isset( $this->settings['secret_nl'] ) ) ? html_entity_decode( $this->settings['secret_nl'] ) : '';
$this->klarna_checkout_url_nl        = ( isset( $this->settings['klarna_checkout_url_nl'] ) ) ? $this->settings['klarna_checkout_url_nl'] : '';
$this->klarna_checkout_thanks_url_nl = ( isset( $this->settings['klarna_checkout_thanks_url_nl'] ) ) ? $this->settings['klarna_checkout_thanks_url_nl'] : '';

$this->eid_de                        = ( isset( $this->settings['eid_de'] ) ) ? $this->settings['eid_de'] : '';
$this->secret_de                     = ( isset( $this->settings['secret_de'] ) ) ? $this->settings['secret_de'] : '';
$this->klarna_checkout_url_de        = ( isset( $this->settings['klarna_checkout_url_de'] ) ) ? $this->settings['klarna_checkout_url_de'] : '';
$this->klarna_checkout_thanks_url_de = ( isset( $this->settings['klarna_checkout_thanks_url_de'] ) ) ? $this->settings['klarna_checkout_thanks_url_de'] : '';
$this->phone_mandatory_de            = ( isset( $this->settings['phone_mandatory_de'] ) ) ? $this->settings['phone_mandatory_de'] : '';
$this->dhl_packstation_de            = ( isset( $this->settings['dhl_packstation_de'] ) ) ? $this->settings['dhl_packstation_de'] : '';

$this->eid_at                        = ( isset( $this->settings['eid_at'] ) ) ? $this->settings['eid_at'] : '';
$this->secret_at                     = ( isset( $this->settings['secret_at'] ) ) ? $this->settings['secret_at'] : '';
$this->klarna_checkout_url_at        = ( isset( $this->settings['klarna_checkout_url_at'] ) ) ? $this->settings['klarna_checkout_url_at'] : '';
$this->klarna_checkout_thanks_url_at = ( isset( $this->settings['klarna_checkout_thanks_url_at'] ) ) ? $this->settings['klarna_checkout_thanks_url_at'] : '';
$this->phone_mandatory_at            = ( isset( $this->settings['phone_mandatory_at'] ) ) ? $this->settings['phone_mandatory_at'] : '';

$this->eid_uk                        = ( isset( $this->settings['eid_uk'] ) ) ? $this->settings['eid_uk'] : '';
$this->secret_uk                     = ( isset( $this->settings['secret_uk'] ) ) ? html_entity_decode( $this->settings['secret_uk'] ) : '';
$this->klarna_checkout_url_uk        = ( isset( $this->settings['klarna_checkout_url_uk'] ) ) ? $this->settings['klarna_checkout_url_uk'] : '';
$this->klarna_checkout_thanks_url_uk = ( isset( $this->settings['klarna_checkout_thanks_url_uk'] ) ) ? $this->settings['klarna_checkout_thanks_url_uk'] : '';

$this->eid_us                        = ( isset( $this->settings['eid_us'] ) ) ? $this->settings['eid_us'] : '';
$this->secret_us                     = ( isset( $this->settings['secret_us'] ) ) ? html_entity_decode( $this->settings['secret_us'] ) : '';
$this->klarna_checkout_url_us        = ( isset( $this->settings['klarna_checkout_url_us'] ) ) ? $this->settings['klarna_checkout_url_us'] : '';
$this->klarna_checkout_thanks_url_us = ( isset( $this->settings['klarna_checkout_thanks_url_us'] ) ) ? $this->settings['klarna_checkout_thanks_url_us'] : '';

$this->default_eur_contry = ( isset( $this->settings['default_eur_contry'] ) ) ? $this->settings['default_eur_contry'] : '';

$this->terms_url = ( isset( $this->settings['terms_url'] ) ) ? $this->settings['terms_url'] : '';
$this->testmode  = ( isset( $this->settings['testmode'] ) ) ? $this->settings['testmode'] : '';
$this->debug     = ( isset( $this->settings['debug'] ) ) ? $this->settings['debug'] : '';

$this->modify_standard_checkout_url = ( isset( $this->settings['modify_standard_checkout_url'] ) ) ? $this->settings['modify_standard_checkout_url'] : '';
$this->add_std_checkout_button      = ( isset( $this->settings['add_std_checkout_button'] ) ) ? $this->settings['add_std_checkout_button'] : '';
$this->std_checkout_button_label    = ( isset( $this->settings['std_checkout_button_label'] ) ) ? $this->settings['std_checkout_button_label'] : '';

$this->create_customer_account = ( isset( $this->settings['create_customer_account'] ) ) ? $this->settings['create_customer_account'] : '';
$this->send_new_account_email  = ( isset( $this->settings['send_new_account_email'] ) ) ? $this->settings['send_new_account_email'] : '';

$this->account_signup_text = ( isset( $this->settings['account_signup_text'] ) ) ? $this->settings['account_signup_text'] : '';
$this->account_login_text  = ( isset( $this->settings['account_login_text'] ) ) ? $this->settings['account_login_text'] : '';

$this->validate_stock = $this->get_option( 'validate_stock' );
$this->allowed_customer_types  = ( isset( $this->settings['allowed_customer_types'] ) ) ? $this->settings['allowed_customer_types'] : '';


// Helper function to make sure colors start with '#' character
if ( ! function_exists( 'wc_klarna_add_hash_to_color' ) ) {
	function wc_klarna_add_hash_to_color( $hex ) {
		if ( '' != $hex ) {
			$hex = str_replace( '#', '', $hex );
			$hex = '#' . $hex;
		}

		return $hex;
	}
}

// Color options
$this->color_button             = ( isset( $this->settings['color_button'] ) ) ? wc_klarna_add_hash_to_color( $this->settings['color_button'] ) : '';
$this->color_button_text        = ( isset( $this->settings['color_button_text'] ) ) ? wc_klarna_add_hash_to_color( $this->settings['color_button_text'] ) : '';
$this->color_checkbox           = ( isset( $this->settings['color_checkbox'] ) ) ? wc_klarna_add_hash_to_color( $this->settings['color_checkbox'] ) : '';
$this->color_checkbox_checkmark = ( isset( $this->settings['color_checkbox_checkmark'] ) ) ? wc_klarna_add_hash_to_color( $this->settings['color_checkbox_checkmark'] ) : '';
$this->color_header             = ( isset( $this->settings['color_header'] ) ) ? wc_klarna_add_hash_to_color( $this->settings['color_header'] ) : '';
$this->color_link               = ( isset( $this->settings['color_link'] ) ) ? wc_klarna_add_hash_to_color( $this->settings['color_link'] ) : '';

$this->activate_recurring = ( isset( $this->settings['activate_recurring'] ) ) ? $this->settings['activate_recurring'] : '';

if ( empty( $this->terms_url ) ) {
	$this->terms_url = esc_url( get_permalink( wc_get_page_id( 'terms' ) ) );
}

// Check if this is test mode or not
if ( $this->testmode == 'yes' ):
	$this->klarna_server = 'https://checkout.testdrive.klarna.com';
else :
	$this->klarna_server = 'https://checkout.klarna.com';
endif;

// Set current country based on used currency

// We need to check if WPML is active
if(!is_admin()) {
	if( WC()->session->get('client_currency') ) {
		$customer_selected_currency = WC()->session->get('client_currency');
	} else {
		$customer_selected_currency = get_woocommerce_currency();
	}
} else {
	$customer_selected_currency = get_woocommerce_currency();
}
switch ( $customer_selected_currency ) {
	case 'NOK' :
		$klarna_country = 'NO';
		break;
	case 'EUR' :
		// Check if Ajax country switcher set session value
		if ( null !== WC()->session && ! is_admin() && WC()->session->get( 'klarna_euro_country' ) ) {
			$klarna_country = WC()->session->get( 'klarna_euro_country' );
		} else {
			if ( ( get_locale() == 'de_DE' || get_locale() == 'de_DE_formal' ) && '' != $this->eid_de && '' != $this->secret_de ) {
				$klarna_country = 'DE';
			} elseif ( get_locale() == 'fi' && '' != $this->eid_fi && '' != $this->secret_fi ) {
				$klarna_country = 'FI';
			} elseif ( get_locale() == 'de_AT' && '' != $this->eid_at && '' != $this->secret_at ) {
				$klarna_country = 'AT';
			}  elseif ( get_locale() == 'nl_NL' && '' != $this->eid_nl && '' != $this->secret_nl ) {
				$klarna_country = 'NL';
			} else {
				$klarna_country = $this->default_eur_contry;
			}
		}
		break;
	case 'SEK' :
		$klarna_country = 'SE';
		break;
	case 'DKK' :
		$klarna_country = 'DK';
		break;
	case 'GBP' :
		$klarna_country = 'GB';
		break;
	case 'USD' :
		$klarna_country = 'US';
		break;
	default:
		$klarna_country = '';
}

$this->shop_country = $klarna_country;

// Country and language
switch ( $this->shop_country ) {
	case 'NO' :
	case 'NB' :
		//case 'NOK' :
		$klarna_country      = 'NO';
		$klarna_language     = 'nb-no';
		$klarna_eid          = $this->eid_no;
		$klarna_secret       = $this->secret_no;
		$klarna_checkout_url = $this->klarna_checkout_url_no;
		if ( $this->klarna_checkout_thanks_url_no == '' ) {
			$klarna_checkout_thanks_url = $this->klarna_checkout_url_no;
		} else {
			$klarna_checkout_thanks_url = $this->klarna_checkout_thanks_url_no;
		}
		break;
	case 'FI' :
		//case 'EUR' :
		$klarna_country = 'FI';

		// Check if WPML is used and determine if Finnish or Swedish is used as language
		if ( class_exists( 'woocommerce_wpml' ) && defined( 'ICL_LANGUAGE_CODE' ) && strtoupper( ICL_LANGUAGE_CODE ) == 'SV' ) {
			// Swedish
			$klarna_language = 'sv-fi';
		} else {
			// Finnish
			$klarna_language = 'fi-fi';
		}

		$klarna_eid          = $this->eid_fi;
		$klarna_secret       = $this->secret_fi;
		$klarna_checkout_url = $this->klarna_checkout_url_fi;
		if ( $this->klarna_checkout_thanks_url_fi == '' ) {
			$klarna_checkout_thanks_url = $this->klarna_checkout_url_fi;
		} else {
			$klarna_checkout_thanks_url = $this->klarna_checkout_thanks_url_fi;
		}
		break;
	case 'SE' :
	case 'SV' :
		//case 'SEK' :
		$klarna_country = 'SE';

		$klarna_language     = 'sv-se';
		$klarna_eid          = $this->eid_se;
		$klarna_secret       = $this->secret_se;
		$klarna_checkout_url = $this->klarna_checkout_url_se;
		if ( $this->klarna_checkout_thanks_url_se == '' ) {
			$klarna_checkout_thanks_url = $this->klarna_checkout_url_se;
		} else {
			$klarna_checkout_thanks_url = $this->klarna_checkout_thanks_url_se;
		}
		break;
	case 'DK' :
		$klarna_country = 'dk';

		$klarna_language     = 'da-dk';
		$klarna_eid          = $this->eid_dk;
		$klarna_secret       = $this->secret_dk;
		$klarna_checkout_url = $this->klarna_checkout_url_dk;
		if ( $this->klarna_checkout_thanks_url_dk == '' ) {
			$klarna_checkout_thanks_url = $this->klarna_checkout_url_dk;
		} else {
			$klarna_checkout_thanks_url = $this->klarna_checkout_thanks_url_dk;
		}
		break;
	case 'nl' :
	case 'NL' :
		$klarna_country = 'nl';

		$klarna_language     = 'nl-NL';
		$klarna_eid          = $this->eid_nl;
		$klarna_secret       = $this->secret_nl;
		$klarna_checkout_url = $this->klarna_checkout_url_nl;
		if ( $this->klarna_checkout_thanks_url_nl == '' ) {
			$klarna_checkout_thanks_url = $this->klarna_checkout_url_nl;
		} else {
			$klarna_checkout_thanks_url = $this->klarna_checkout_thanks_url_nl;
		}
		break;
	case 'DE' :
		$klarna_country      = 'DE';
		$klarna_language     = 'de-de';
		$klarna_eid          = $this->eid_de;
		$klarna_secret       = $this->secret_de;
		$klarna_checkout_url = $this->klarna_checkout_url_de;
		if ( $this->klarna_checkout_thanks_url_de == '' ) {
			$klarna_checkout_thanks_url = $this->klarna_checkout_url_de;
		} else {
			$klarna_checkout_thanks_url = $this->klarna_checkout_thanks_url_de;
		}
		break;
	case 'AT' :
		$klarna_country      = 'AT';
		$klarna_language     = 'de-at';
		$klarna_eid          = $this->eid_at;
		$klarna_secret       = $this->secret_at;
		$klarna_checkout_url = $this->klarna_checkout_url_at;
		if ( $this->klarna_checkout_thanks_url_at == '' ) {
			$klarna_checkout_thanks_url = $this->klarna_checkout_url_at;
		} else {
			$klarna_checkout_thanks_url = $this->klarna_checkout_thanks_url_at;
		}
		break;
	case 'GB' :
	case 'gb' :
		$klarna_country      = 'gb';
		$klarna_language     = 'en-gb';
		$klarna_eid          = $this->eid_uk;
		$klarna_secret       = $this->secret_uk;
		$klarna_checkout_url = $this->klarna_checkout_url_uk;
		if ( $this->klarna_checkout_thanks_url_uk == '' ) {
			$klarna_checkout_thanks_url = $this->klarna_checkout_url_uk;
		} else {
			$klarna_checkout_thanks_url = $this->klarna_checkout_thanks_url_uk;
		}
		break;
	case 'US' :
	case 'us' :
		$klarna_country      = 'us';
		$klarna_language     = 'en-us';
		$klarna_eid          = $this->eid_us;
		$klarna_secret       = $this->secret_us;
		$klarna_checkout_url = $this->klarna_checkout_url_us;
		if ( $this->klarna_checkout_thanks_url_us == '' ) {
			$klarna_checkout_thanks_url = $this->klarna_checkout_url_us;
		} else {
			$klarna_checkout_thanks_url = $this->klarna_checkout_thanks_url_us;
		}
		break;
	default:
		$klarna_country             = '';
		$klarna_language            = '';
		$klarna_eid                 = '';
		$klarna_secret              = '';
		$klarna_checkout_url        = '';
		$klarna_invoice_terms       = '';
		$klarna_invoice_icon        = '';
		$klarna_checkout_thanks_url = '';
}

$this->authorized_countries = array();
if ( ! empty( $this->eid_se ) ) {
	$this->authorized_countries[] = 'SE';
}
if ( ! empty( $this->eid_no ) ) {
	$this->authorized_countries[] = 'NO';
}
if ( ! empty( $this->eid_fi ) ) {
	$this->authorized_countries[] = 'FI';
}
if ( ! empty( $this->eid_dk ) ) {
	$this->authorized_countries[] = 'DK';
}
if ( ! empty( $this->eid_nl ) ) {
	$this->authorized_countries[] = 'NL';
}
if ( ! empty( $this->eid_de ) ) {
	$this->authorized_countries[] = 'DE';
}
if ( ! empty( $this->eid_at ) ) {
	$this->authorized_countries[] = 'AT';
}
if ( ! empty( $this->eid_uk ) ) {
	$this->authorized_countries[] = 'GB';
}
if ( ! empty( $this->eid_us ) ) {
	$this->authorized_countries[] = 'US';
}

// Set Klarna Country session
if ( ! is_admin() && ! empty( $klarna_country ) ) {
	WC()->session->set( 'klarna_country', apply_filters( 'klarna_country', $klarna_country ) );
}


// Apply filters to Country and language
$this->klarna_country             = apply_filters( 'klarna_country', $klarna_country );
$this->klarna_credentials_country = apply_filters( 'klarna_credentials_country', $klarna_country );
$this->klarna_language            = apply_filters( 'klarna_language', $klarna_language );
$this->klarna_eid                 = apply_filters( 'klarna_eid', $klarna_eid );
$this->klarna_secret              = apply_filters( 'klarna_secret', $klarna_secret );
$this->klarna_checkout_url        = apply_filters( 'klarna_checkout_url', $klarna_checkout_url );
$this->klarna_checkout_thanks_url = apply_filters( 'klarna_checkout_thanks_url', $klarna_checkout_thanks_url );

global $klarna_checkout_url;
$klarna_checkout_url = $this->klarna_checkout_url;
global $klarna_checkout_thanks_url;
$klarna_checkout_thanks_url = $this->klarna_checkout_thanks_url;