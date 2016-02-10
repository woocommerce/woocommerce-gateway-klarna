<?php
/**
 * Helper class for Klarna KPM
 *
 * @link http://www.woothemes.com/products/klarna/
 * @since 1.0.0
 *
 * @package WC_Gateway_Klarna
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Gateway_Klarna_Helper {

	public function __construct( $parent ) {
		$this->parent = $parent;
	}

	/**
	 * Helper function, gets Klarna payment method testmode.
	 *
	 * @since 1.0.0
	 **/
	function get_test_mode() {
		return $this->parent->testmode;
	}

	/**
	 * Checks if method is enabled.
	 *
	 * @since 1.0.0
	 **/
	function get_enabled() {
		return $this->parent->enabled;
	}


	/**
	 * Helper function, gets Klarna locale based on current locale.
	 *
	 * @since 1.0.0
	 *
	 * @param string $locale
	 *
	 * @return string $klarna_locale
	 **/
	function get_klarna_locale( $locale ) {
		switch ( $locale ) {
			case 'da_DK':
				$klarna_locale = 'da_dk';
				break;
			case 'de_DE' :
				$klarna_locale = 'de_de';
				break;
			case 'no_NO' :
			case 'nb_NO' :
			case 'nn_NO' :
				$klarna_locale = 'nb_no';
				break;
			case 'nl_NL' :
				$klarna_locale = 'nl_nl';
				break;
			case 'fi_FI' :
			case 'fi' :
				$klarna_locale = 'fi_fi';
				break;
			case 'sv_SE' :
				$klarna_locale = 'sv_se';
				break;
			case 'de_AT' :
				$klarna_locale = 'de_at';
				break;
			case 'en_GB' :
				$klarna_locale = 'en_gb';
				break;
			case 'en_US' :
				$klarna_locale = 'en_se';
				break;
			default:
				$klarna_locale = '';
		}

		return $klarna_locale;
	}

	/**
	 * Helper function, gets Klarna secret based on country.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $country
	 *
	 * @return string $current_secret
	 **/
	function get_secret( $country = '' ) {
		global $woocommerce;

		if ( empty( $country ) ) {
			$country = ( isset( $woocommerce->customer->country ) ) ? $woocommerce->customer->country : $this->parent->shop_country;
		}

		switch ( $country ) {
			case 'DK' :
				$current_secret = $this->parent->secret_dk;
				break;
			case 'DE' :
				$current_secret = $this->parent->secret_de;
				break;
			case 'NL' :
				$current_secret = $this->parent->secret_nl;
				break;
			case 'NO' :
				$current_secret = $this->parent->secret_no;
				break;
			case 'FI' :
				$current_secret = $this->parent->secret_fi;
				break;
			case 'SE' :
				$current_secret = $this->parent->secret_se;
				break;
			case 'AT' :
				$current_secret = $this->parent->secret_at;
				break;
			default:
				$current_secret = '';
		}

		return $current_secret;
	}

	/**
	 * Helper function, gets currency for selected country.
	 *
	 * @since 1.0.0
	 *
	 * @param string $country
	 *
	 * @return string $currency
	 **/
	function get_currency_for_country( $country ) {
		switch ( $country ) {
			case 'DK' :
				$currency = 'DKK';
				break;
			case 'DE' :
				$currency = 'EUR';
				break;
			case 'NL' :
				$currency = 'EUR';
				break;
			case 'NO' :
				$currency = 'NOK';
				break;
			case 'FI' :
				$currency = 'EUR';
				break;
			case 'SE' :
				$currency = 'SEK';
				break;
			case 'AT' :
				$currency = 'EUR';
				break;
			default:
				$currency = '';
		}

		return $currency;
	}

	/**
	 * Helper function, gets Klarna language for selected country.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $country
	 *
	 * @return string $language
	 **/
	function get_klarna_language( $country ) {
		switch ( $country ) {
			case 'DK' :
				$language = 'DA';
				break;
			case 'DE' :
				$language = 'DE';
				break;
			case 'NL' :
				$language = 'NL';
				break;
			case 'NO' :
				$language = 'NB';
				break;
			case 'FI' :
				$language = 'FI';
				break;
			case 'SE' :
				$language = 'SV';
				break;
			case 'AT' :
				$language = 'DE';
				break;
			default:
				$language = '';
		}

		return $language;
	}

	/**
	 * Helper function, gets Klarna country.
	 *
	 * @since 1.0.0
	 *
	 * @return string $klarna_country
	 **/
	function get_klarna_country() {
		global $woocommerce;

		if ( $woocommerce->customer->get_country() ) {
			$klarna_country = $woocommerce->customer->get_country();
		} else {
			$klarna_country = $this->parent->shop_language;
			switch ( $this->parent->shop_country ) {
				case 'NB' :
					$klarna_country = 'NO';
					break;
				case 'SV' :
					$klarna_country = 'SE';
					break;
			}
		}

		// Check if $klarna_country exists among the authorized countries
		if ( ! in_array( $klarna_country, $this->parent->authorized_countries ) ) {
			return $this->parent->shop_country;
		} else {
			return $klarna_country;
		}
	}

	/**
	 * Helper function, gets invoice icon.
	 *
	 * @since 1.0.0
	 **/
	function get_account_icon() {
		global $woocommerce;

		$country = ( isset( $woocommerce->customer->country ) ) ? $woocommerce->customer->country : '';

		if ( empty( $country ) ) {
			$country = $this->parent->shop_country;
		}

		switch ( $country ) {
			case 'DK':
				$klarna_part_payment_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/da_dk/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			case 'DE':
				$klarna_part_payment_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/de_de/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			case 'NL':
				$klarna_part_payment_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/nl_nl/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			case 'NO':
				$klarna_part_payment_icon = false;
				break;
			case 'FI':
				$klarna_part_payment_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/fi_fi/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			case 'SE':
				$klarna_part_payment_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/sv_se/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			case 'AT':
				$klarna_part_payment_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/de_at/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			default:
				$klarna_part_payment_icon = '';
		}

		return $klarna_part_payment_icon;
	}

	/**
	 * Helper function, gets Klarna eid based on country.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $country
	 *
	 * @return integer $current_eid
	 **/
	function get_eid( $country = '' ) {
		global $woocommerce;

		if ( empty( $country ) ) {
			$country = ( isset( $woocommerce->customer->country ) ) ? $woocommerce->customer->country : $this->parent->shop_country;
		}

		switch ( $country ) {
			case 'DK' :
				$current_eid = $this->parent->eid_dk;
				break;
			case 'DE' :
				$current_eid = $this->parent->eid_de;
				break;
			case 'NL' :
				$current_eid = $this->parent->eid_nl;
				break;
			case 'NO' :
				$current_eid = $this->parent->eid_no;
				break;
			case 'FI' :
				$current_eid = $this->parent->eid_fi;
				break;
			case 'SE' :
				$current_eid = $this->parent->eid_se;
				break;
			case 'AT' :
				$current_eid = $this->parent->eid_at;
				break;
			default:
				$current_eid = '';
		}

		return $current_eid;
	}

}