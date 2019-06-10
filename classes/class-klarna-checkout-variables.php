<?php
/**
 * Klarna Checkout variables getters
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class for Klarna shortodes.
 */
class WC_Gateway_Klarna_Checkout_Variables {

	public static function get_klarna_checkout_settings() {
		return get_option( 'woocommerce_klarna_checkout_settings' );
	}

	public static function get_klarna_eid() {
		$settings = self::get_klarna_checkout_settings();
		$country  = self::get_klarna_country();

		switch ( $country ) {
			case 'NO':
			case 'NB':
				$klarna_eid = $settings['eid_no'];
				break;
			case 'FI':
				$klarna_eid = $settings['eid_fi'];
				break;
			case 'SE':
			case 'SV':
				$klarna_eid = $settings['eid_se'];
				break;
			case 'DK':
				$klarna_eid = $settings['eid_dk'];
				break;
			case 'nl':
			case 'NL':
				$klarna_eid = $settings['eid_nl'];
				break;
			case 'DE':
				$klarna_eid = $settings['eid_de'];
				break;
			case 'AT':
				$klarna_eid = $settings['eid_at'];
				break;
			case 'GB':
			case 'gb':
				$klarna_eid = $settings['eid_uk'];
				break;
			case 'US':
			case 'us':
				$klarna_eid = $settings['eid_us'];
				break;
			default:
				$klarna_eid = '';
		}

		return apply_filters( 'klarna_eid', $klarna_eid );
	}

	public static function get_klarna_secret() {
		$settings = self::get_klarna_checkout_settings();
		$country  = self::get_klarna_country();

		switch ( $country ) {
			case 'NO':
			case 'NB':
				$klarna_secret = $settings['secret_no'];
				break;
			case 'FI':
				$klarna_secret = $settings['secret_fi'];
				break;
			case 'SE':
			case 'SV':
				$klarna_secret = $settings['secret_se'];
				break;
			case 'DK':
				$klarna_secret = $settings['secret_dk'];
				break;
			case 'nl':
			case 'NL':
				$klarna_secret = $settings['secret_nl'];
				break;
			case 'DE':
				$klarna_secret = $settings['secret_de'];
				break;
			case 'AT':
				$klarna_secret = $settings['secret_at'];
				break;
			case 'GB':
			case 'gb':
				$klarna_secret = $settings['secret_uk'];
				break;
			case 'US':
			case 'us':
				$klarna_secret = $settings['secret_us'];
				break;
			default:
				$klarna_secret = '';
		}

		return apply_filters( 'klarna_secret', $klarna_secret );
	}

	public static function get_klarna_country() {
		$settings = self::get_klarna_checkout_settings();

		// We need to check if WPML is active
		if ( method_exists( WC()->session, 'get' ) && WC()->session->get( 'client_currency' ) ) {
			$customer_selected_currency = WC()->session->get( 'client_currency' );
		} else {
			$customer_selected_currency = get_woocommerce_currency();
		}

		switch ( $customer_selected_currency ) {
			case 'NOK':
				$klarna_country = 'NO';
				break;
			case 'EUR':
				// Check if Ajax country switcher set session value
				if ( null !== WC()->session && ! is_admin() && WC()->session->get( 'klarna_euro_country' ) ) {
					$klarna_country = WC()->session->get( 'klarna_euro_country' );
				} else {
					if ( '' !== $settings['eid_de'] && '' !== $settings['secret_de'] && ( get_locale() === 'de_DE' || get_locale() === 'de_DE_formal' ) ) {
						$klarna_country = 'DE';
					} elseif ( '' !== $settings['eid_fi'] && '' !== $settings['secret_fi'] && get_locale() === 'fi' ) {
						$klarna_country = 'FI';
					} elseif ( '' !== $settings['eid_at'] && '' !== $settings['secret_at'] && get_locale() === 'de_AT' ) {
						$klarna_country = 'AT';
					} elseif ( '' !== $settings['eid_nl'] && '' !== $settings['secret_nl'] && get_locale() === 'nl_NL' ) {
						$klarna_country = 'NL';
					} else {
						$klarna_country = $settings['default_eur_country'];
					}
				}
				break;
			case 'SEK':
				$klarna_country = 'SE';
				break;
			case 'DKK':
				$klarna_country = 'DK';
				break;
			case 'GBP':
				$klarna_country = 'GB';
				break;
			case 'USD':
				$klarna_country = 'US';
				break;
			default:
				$klarna_country = '';
		}

		WC()->session->set( 'klarna_country', apply_filters( 'klarna_country', $klarna_country ) );

		return apply_filters( 'klarna_country', $klarna_country );
	}

	public static function get_klarna_language() {
		$country = self::get_klarna_country();

		switch ( $country ) {
			case 'NO':
			case 'NB':
				$klarna_language = 'nb-no';
				break;
			case 'FI':
				if ( class_exists( 'woocommerce_wpml' ) && defined( 'ICL_LANGUAGE_CODE' ) && strtoupper( ICL_LANGUAGE_CODE ) == 'SV' ) {
					$klarna_language = 'sv-fi';
				} else {
					$klarna_language = 'fi-fi';
				}
				break;
			case 'SE':
			case 'SV':
				$klarna_language = 'sv-se';
				break;
			case 'DK':
				$klarna_language = 'da-dk';
				break;
			case 'nl':
			case 'NL':
				$klarna_language = 'nl-NL';
				break;
			case 'DE':
				$klarna_language = 'de-de';
				break;
			case 'AT':
				$klarna_language = 'de-at';
				break;
			case 'GB':
			case 'gb':
				$klarna_language = 'en-gb';
				break;
			case 'US':
			case 'us':
				$klarna_language = 'en-us';
				break;
			default:
				$klarna_language = '';
		}

		return apply_filters( 'klarna_language', $klarna_language );
	}

	public static function get_klarna_server() {
		if ( self::get_klarna_checkout_testmode() == 'yes' ) {
			return 'https://checkout.testdrive.klarna.com';
		} else {
			return 'https://checkout.klarna.com';
		}
	}

	public static function get_klarna_checkout_url() {
		$settings = self::get_klarna_checkout_settings();
		$country  = self::get_klarna_country();

		switch ( $country ) {
			case 'NO':
			case 'NB':
				$klarna_checkout_url = $settings['klarna_checkout_url_no'];
				break;
			case 'FI':
				$klarna_checkout_url = $settings['klarna_checkout_url_fi'];
				break;
			case 'SE':
			case 'SV':
				$klarna_checkout_url = $settings['klarna_checkout_url_se'];
				break;
			case 'DK':
				$klarna_checkout_url = $settings['klarna_checkout_url_dk'];
				break;
			case 'nl':
			case 'NL':
				$klarna_checkout_url = $settings['klarna_checkout_url_nl'];
				break;
			case 'DE':
				$klarna_checkout_url = $settings['klarna_checkout_url_de'];
				break;
			case 'AT':
				$klarna_checkout_url = $settings['klarna_checkout_url_at'];
				break;
			case 'GB':
			case 'gb':
				$klarna_checkout_url = $settings['klarna_checkout_url_uk'];
				break;
			case 'US':
			case 'us':
				$klarna_checkout_url = $settings['klarna_checkout_url_us'];
				break;
			default:
				$klarna_checkout_url = '';
		}

		return apply_filters( 'klarna_checkout_url', $klarna_checkout_url );
	}

	public static function get_klarna_checkout_thank_you_url() {
		$settings = self::get_klarna_checkout_settings();
		$country  = self::get_klarna_country();

		switch ( $country ) {
			case 'NO':
			case 'NB':
				if ( $settings['klarna_checkout_thanks_url_no'] === '' ) {
					$klarna_checkout_thanks_url = $settings['klarna_checkout_url_no'];
				} else {
					$klarna_checkout_thanks_url = $settings['klarna_checkout_thanks_url_no'];
				}
				break;
			case 'FI':
				if ( $settings['klarna_checkout_thanks_url_fi'] === '' ) {
					$klarna_checkout_thanks_url = $settings['klarna_checkout_url_fi'];
				} else {
					$klarna_checkout_thanks_url = $settings['klarna_checkout_thanks_url_fi'];
				}
				break;
			case 'SE':
			case 'SV':
				if ( $settings['klarna_checkout_thanks_url_se'] === '' ) {
					$klarna_checkout_thanks_url = $settings['klarna_checkout_url_se'];
				} else {
					$klarna_checkout_thanks_url = $settings['klarna_checkout_thanks_url_se'];
				}
				break;
			case 'DK':
				if ( $settings['klarna_checkout_thanks_url_dk'] === '' ) {
					$klarna_checkout_thanks_url = $settings['klarna_checkout_url_dk'];
				} else {
					$klarna_checkout_thanks_url = $settings['klarna_checkout_thanks_url_dk'];
				}
				break;
			case 'nl':
			case 'NL':
				if ( $settings['klarna_checkout_thanks_url_nl'] === '' ) {
					$klarna_checkout_thanks_url = $settings['klarna_checkout_url_nl'];
				} else {
					$klarna_checkout_thanks_url = $settings['klarna_checkout_thanks_url_nl'];
				}
				break;
			case 'DE':
				if ( $settings['klarna_checkout_thanks_url_de'] === '' ) {
					$klarna_checkout_thanks_url = $settings['klarna_checkout_url_de'];
				} else {
					$klarna_checkout_thanks_url = $settings['klarna_checkout_thanks_url_de'];
				}
				break;
			case 'AT':
				if ( $settings['klarna_checkout_thanks_url_at'] === '' ) {
					$klarna_checkout_thanks_url = $settings['klarna_checkout_url_at'];
				} else {
					$klarna_checkout_thanks_url = $settings['klarna_checkout_thanks_url_at'];
				}
				break;
			case 'GB':
			case 'gb':
				if ( $settings['klarna_checkout_thanks_url_uk'] === '' ) {
					$klarna_checkout_thanks_url = $settings['klarna_checkout_url_uk'];
				} else {
					$klarna_checkout_thanks_url = $settings['klarna_checkout_thanks_url_uk'];
				}
				break;
			case 'US':
			case 'us':
				if ( $settings['klarna_checkout_thanks_url_us'] === '' ) {
					$klarna_checkout_thanks_url = $settings['klarna_checkout_url_us'];
				} else {
					$klarna_checkout_thanks_url = $settings['klarna_checkout_thanks_url_us'];
				}
				break;
			default:
				$klarna_checkout_thanks_url = '';
		}

		return apply_filters( 'klarna_checkout_thanks_url', $klarna_checkout_thanks_url );
	}

	public static function get_terms_url() {
		$settings = self::get_klarna_checkout_settings();

		if ( isset( $settings['terms_url'] ) && '' !== $settings['terms_url'] ) {
			return $settings['terms_url'];
		} else {
			return esc_url( get_permalink( wc_get_page_id( 'terms' ) ) );
		}
	}

	public static function get_cancellation_terms_url() {
		$settings = self::get_klarna_checkout_settings();
		$country  = self::get_klarna_country();
		switch ( $country ) {
			case 'DE':
				if ( $settings['klarna_checkout_cancellation_terms_url_de'] ) {
					$klarna_checkout_cancellation_terms_url = $settings['klarna_checkout_cancellation_terms_url_de'];
				}
				break;
			case 'at':
			case 'AT':
				if ( $settings['klarna_checkout_cancellation_terms_url_at'] ) {
					$klarna_checkout_cancellation_terms_url = $settings['klarna_checkout_cancellation_terms_url_at'];
				}
				break;
			default:
				$klarna_checkout_cancellation_terms_url = '';
		}

		return apply_filters( 'klarna_checkout_cancellation_terms_url', $klarna_checkout_cancellation_terms_url );

		if ( isset( $settings['cancellation_terms_url'] ) && '' !== $settings['cancellation_terms_url'] ) {
			return $settings['cancellation_terms_url'];
		} else {
			return false;
		}
	}

	public static function get_klarna_checkout_testmode() {
		$settings = self::get_klarna_checkout_settings();
		$testmode = isset( $settings['testmode'] ) ? $settings['testmode'] : '';

		return $testmode;
	}

	public static function get_klarna_checkout_debug() {
		$settings = self::get_klarna_checkout_settings();
		$debug    = isset( $settings['debug'] ) ? $settings['debug'] : '';

		return $debug;
	}

	public static function get_klarna_checkout_log() {
		return new WC_Logger();
	}

	public static function is_rest() {
		$country = self::get_klarna_country();

		if ( in_array(
			strtoupper( $country ),
			apply_filters(
				'klarna_is_rest_countries',
				array(
					'US',
					'DK',
					'GB',
					'NL',
				)
			),
			true
		) ) {
			// Set it in session as well, to be used in Shortcodes class
			WC()->session->set( 'klarna_is_rest', true );

			return true;
		}

		// Set it in session as well, to be used in Shortcodes class
		WC()->session->set( 'klarna_is_rest', false );

		return false;
	}

	public static function show_kco() {
		$settings = self::get_klarna_checkout_settings();

		$enabled = isset( $settings['enabled'] ) ? $settings['enabled'] : '';
		if ( $enabled !== 'yes' ) {
			// Set it in session as well, to be used in Shortcodes class
			WC()->session->set( 'klarna_show_kco', false );

			return false;
		}

		// If checkout registration is disabled and not logged in, the user cannot checkout
		if ( ! WC()->checkout()->enable_guest_checkout && ! is_user_logged_in() ) {
			echo '<div>';
			echo apply_filters( 'klarna_checkout_must_be_logged_in_message', sprintf( __( 'You must be logged in to checkout. %1$s or %2$s.', 'woocommerce-gateway-klarna' ), '<a href="' . wp_login_url() . '" title="' . __( 'Login', 'woocommerce-gateway-klarna' ) . '">' . __( 'Login', 'woocommerce-gateway-klarna' ) . '</a>', '<a href="' . wp_registration_url() . '" title="' . __( 'create an account', 'woocommerce-gateway-klarna' ) . '">' . __( 'create an account', 'woocommerce-gateway-klarna' ) . '</a>' ) );
			echo '</div>';
			WC()->session->set( 'klarna_show_kco', false );

			return false;
		}

		// If no Klarna country is set - return.
		if ( empty( self::get_klarna_country() ) || empty( self::get_klarna_eid() ) || empty( self::get_klarna_secret() ) ) {
			echo apply_filters( 'klarna_checkout_wrong_country_message', sprintf( __( 'Sorry, you can not buy via Klarna Checkout from your country or currency. Please <a href="%s">use another payment method</a>. ', 'woocommerce-gateway-klarna' ), get_permalink( get_option( 'woocommerce_checkout_page_id' ) ) ) );
			WC()->session->set( 'klarna_show_kco', false );

			return false;
		}

		// If the WooCommerce terms page or the Klarna Checkout settings field
		// Terms Page isn't set, do nothing.
		if ( '' === self::get_terms_url() ) {
			WC()->session->set( 'klarna_show_kco', false );

			return false;
		}

		WC()->session->set( 'klarna_show_kco', true );

		return true;
	}

	public static function add_std_checkout_button() {
		$settings = self::get_klarna_checkout_settings();

		return isset( $settings['add_std_checkout_button'] ) ? $settings['add_std_checkout_button'] : '';
	}

	public static function add_std_checkout_button_label() {
		$settings = self::get_klarna_checkout_settings();

		return isset( $settings['std_checkout_button_label'] ) ? $settings['std_checkout_button_label'] : '';
	}

	public static function validate_stock() {
		$settings = self::get_klarna_checkout_settings();

		return isset( $settings['validate_stock'] ) ? $settings['validate_stock'] : '';
	}

	public static function get_color_options() {
		$settings      = self::get_klarna_checkout_settings();
		$color_options = array();

		$color_options['color_button']             = isset( $settings['color_button'] ) ? self::add_hash_to_color( $settings['color_button'] ) : '';
		$color_options['color_button_text']        = isset( $settings['color_button_text'] ) ? self::add_hash_to_color( $settings['color_button_text'] ) : '';
		$color_options['color_checkbox']           = isset( $settings['color_checkbox'] ) ? self::add_hash_to_color( $settings['color_checkbox'] ) : '';
		$color_options['color_checkbox_checkmark'] = isset( $settings['color_checkbox_checkmark'] ) ? self::add_hash_to_color( $settings['color_checkbox_checkmark'] ) : '';
		$color_options['color_header']             = isset( $settings['color_header'] ) ? self::add_hash_to_color( $settings['color_header'] ) : '';
		$color_options['color_link']               = isset( $settings['color_link'] ) ? self::add_hash_to_color( $settings['color_link'] ) : '';

		return $color_options;
	}

	public static function get_allowed_customer_types() {
		$settings = self::get_klarna_checkout_settings();

		return isset( $settings['allowed_customer_types'] ) ? $settings['allowed_customer_types'] : '';
	}

	public static function get_allow_separate_shipping() {
		$settings = self::get_klarna_checkout_settings();

		return isset( $settings['allow_separate_shipping_address'] ) ? $settings['allow_separate_shipping_address'] : '';
	}

	public static function get_phone_mandatory_de() {
		$settings = self::get_klarna_checkout_settings();

		return isset( $settings['phone_mandatory_de'] ) ? $settings['phone_mandatory_de'] : '';

	}

	public static function get_dhl_packstation_de() {
		$settings = self::get_klarna_checkout_settings();

		return isset( $settings['dhl_packstation_de'] ) ? $settings['dhl_packstation_de'] : '';

	}

	public static function add_hash_to_color( $hex ) {
		if ( '' != $hex ) {
			$hex = str_replace( '#', '', $hex );
			$hex = '#' . $hex;
		}

		return $hex;
	}
}
