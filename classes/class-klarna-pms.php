<?php

/**
 * Class WC_Klarna_PMS
 *
 * The payment method service is a new API call, created to provide you with all the information
 * you need to render your checkout when using Klarna's invoice and part payment products - both logotypes,
 * descriptions and pricing details. It simplifies the integration process and provide our recommendations
 * on how our products should be presented, and your customers will enjoy a frictionless buying experience.
 *
 * @class     WC_Klarna_PMS
 * @version   1.0
 * @since     1.9.5
 * @category  Class
 * @author    Krokedil
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Klarna_PMS {

	public function __construct() {

		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_action( 'wp_enqueue_scripts', 'add_thickbox' );

	}

	/**
	 * Register and Enqueue Klarna scripts
	 */
	function load_scripts() {

		if ( is_checkout() ) {
			wp_register_script( 'klarna-pms-js', KLARNA_URL . 'assets/js/klarnapms.js', array( 'jquery' ), '1.0', false );
			wp_enqueue_script( 'klarna-pms-js' );
		}

	} // End function


	/**
	 * Gets response from Klarna
	 */
	function get_data(
		$eid, $secret, $selected_currency, $shop_country, $cart_total, $payment_method_group, $select_id, $mode, $invoice_fee = false
	) {

		$klarna = new Klarna();
		$config = new KlarnaConfig();

		// Default required options
		if ( $mode == 'test' ) {
			$klarna_ssl      = 'false';
			$klarna_endpoint = 'https://api-test.klarna.com/touchpoint/checkout/';
			$klarna_mode     = Klarna::BETA;
		} else {
			// Set SSL if used in webshop
			if ( is_ssl() ) {
				$klarna_ssl = 'true';
			} else {
				$klarna_ssl = 'false';
			}
			$klarna_endpoint = 'https://api.klarna.com/touchpoint/checkout/';
			$klarna_mode     = Klarna::LIVE;
		}

		// Configuration needed for the checkout service
		$config['mode']                 = $klarna_mode;
		$config['ssl']                  = $klarna_ssl;
		$config['checkout_service_uri'] = $klarna_endpoint;
		$config['pcStorage']            = 'json';
		$config['pcURI']                = './pclasses.json';
		$config['eid']                  = $eid;
		$config['secret']               = $secret;

		$klarna->setConfig( $config );

		$klarna_pms_locale = $this->get_locale( $shop_country );

		try {
			$response = $klarna->checkoutService( $cart_total,        // Total price of the checkout including VAT
				$selected_currency, // Currency used by the checkout
				$klarna_pms_locale  // Locale used by the checkout
			);
		} catch ( KlarnaException $e ) {
			// cURL exception
			return false;
		}

		$data = $response->getData();

		if ( $response->getStatus() >= 400 ) {
			// server responded with error
			echo '<pre>';
			throw new Exception( print_r( $data, true ) );
			echo '</pre>';

			return false;
		}

		// return options and their descriptions

		$payment_methods = $data['payment_methods'];

		$payment_options         = array();
		$payment_options_details = array();

		$i = 0;
		foreach ( $payment_methods as $payment_method ) {

			// Check if payment group we're looking for
			if ( $payment_method_group == $payment_method['group']['code'] ) {
				$i ++;

				// Create option element output
				$payment_options[] = '<option value="' . $payment_method['pclass_id'] . '">' . $payment_method['title'] . '</option>';

				// Create payment option details output
				if ( $i < 2 ) {
					$inline_style = 'style="clear:both;position:relative"';
					$extra_class  = 'visible-pms';
				} else {
					$inline_style = 'style="clear:both;display:none;position:relative"';
					$extra_class  = '';
				}

				$payment_options_details_output = '<div class="klarna-pms-details ' . $extra_class . '" data-pclass="' . $payment_method['pclass_id'] . '" ' . $inline_style . '>';

				if ( isset( $payment_method['logo']['uri'] ) && '' != $payment_method['logo']['uri'] ) {
					$payment_options_details_output .= '<img class="klarna-pms-logo" style="display:none" src="' . $payment_method['logo']['uri'] . '?width=100" />';
				}

				$payment_options_details_output .= '<div>';

				$payment_options_details_output .= '<strong style="font-size:1.2em;display:block;margin-bottom:0.5em;">' . $payment_method['group']['title'] . '</strong>';

				if ( ! empty( $payment_method['details'] ) ) {
					$payment_options_details_output .= '<ul style="list-style:none;margin-bottom:0.75em;margin-left:0">';
					foreach ( $payment_method['details'] as $pd_k => $pd_v ) {
						$payment_options_details_output .= '<li style="padding:0.5em 0 !important" id="pms-details-' . $pd_k . '">' . implode( ' ', $pd_v ) . '</li>';
					}
					$payment_options_details_output .= '</ul>';
				}

				if ( isset( $payment_method['use_case'] ) && '' != $payment_method['use_case'] ) {
					$payment_options_details_output .= '<div class="klarna-pms-use-case" style="margin-bottom:0.75em">' . $payment_method['use_case'] . '</div>';
				}

				if ( isset( $payment_method['terms']['uri'] ) && '' != $payment_method['terms']['uri'] ) {
					$klarna_terms_uri = $payment_method['terms']['uri'];

					// Check if invoice fee needs to be added
					// Invoice terms links ends with ?fee=
					if ( strpos( $klarna_terms_uri, '?fee=' ) ) {
						if ( $invoice_fee ) {
							$klarna_terms_uri = $klarna_terms_uri . $invoice_fee . '&TB_iframe=true&width=600&height=550';
						} else {
							$klarna_terms_uri = $klarna_terms_uri . '0&TB_iframe=true&width=600&height=550';
						}
					} else {
						$klarna_terms_uri .= '?TB_iframe=true&width=600&height=550';
					}

					if ( 'SE' == $shop_country ) {
						$read_more_text = 'Läs mer';
					} elseif ( 'NO' == $shop_country ) {
						$read_more_text = 'Les mer';
					} else {
						$read_more_text = 'Read more';
					}
					add_thickbox();
					$payment_options_details_output .= '<div class="klarna-pms-terms-uri" style="margin-bottom:1em;"><a class="thickbox" href="' . $klarna_terms_uri . '" target="_blank">' . $read_more_text . '</a></div>';
				}

				$payment_options_details_output .= '</div>';

				$payment_options_details_output .= '</div>';

				$payment_options_details[] = $payment_options_details_output;

			}

		}

		// Check if anything was returned
		if ( ! empty( $payment_options ) ) {
			$payment_methods_output = '<p class="form-row">';
			$payment_methods_output .= '<label for="' . esc_attr( $select_id ) . '">' . __( 'Payment plan', 'woocommerce-gateway-klarna' ) . ' <span class="required">*</span></label>';
			$payment_methods_output .= '<select id="' . esc_attr( $select_id ) . '" name="' . esc_attr( $select_id ) . '" class="woocommerce-select klarna_pms_select" style="max-width:100%;width:100% !important;">';

			$payment_methods_output .= implode( '', $payment_options );

			$payment_methods_output .= '</select>';
			$payment_methods_output .= '</p>';

			if ( ! empty( $payment_options_details ) ) {
				$payment_methods_output .= implode( '', $payment_options_details );
			}

		} else {
			$payment_methods_output = false;
		}

		return $payment_methods_output;

	}

	function get_locale( $shop_country ) {

		switch ( $shop_country ) {
			case 'SE' :
				$klarna_pms_locale = 'sv_SE';
				break;
			case 'NO' :
				$klarna_pms_locale = 'nb_NO';
				break;
			case 'DK' :
				$klarna_pms_locale = 'da_DK';
				break;
			case 'FI' :
				$klarna_pms_locale = 'fi_FI';
				break;
			case 'DE' :
				$klarna_pms_locale = 'de_DE';
				break;
			case 'NL' :
				$klarna_pms_locale = 'nl_NL';
				break;
			case 'AT' :
				$klarna_pms_locale = 'de_AT';
				break;
		}

		return $klarna_pms_locale;

	}

}

$wc_klarna_pms = new WC_Klarna_PMS;