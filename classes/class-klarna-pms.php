<?php

/**
 * Class WC_Klarna_PMS
 *
 * The payment method service is a new API call, created to provide you with all the information
 * you need to render your checkout when using Klarna's invoice and part payment products - both logotypes,
 * descriptions and pricing details. It simplifies the integration process and provide our recommendations
 * on how our products should be presented, and your customers will enjoy a frictionless buying experience.
 *
 * @class 		WC_Klarna_PMS
 * @version		1.0
 * @since		1.9.5
 * @category	Class
 * @author 		Krokedil
 *
 */


class WC_Klarna_PMS {

	public function __construct() {
	
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts') );

	}

	/**
 	 * Register and Enqueue Klarna scripts
 	 */
	function load_scripts() {
		
		wp_register_script( 'klarna-pms-js', plugins_url( '/js/klarnapms.js' , __FILE__ ), array('jquery'), '1.0', false );
		wp_enqueue_script( 'klarna-pms-js' );

	} // End function


	function get_data( $eid, $secret, $shop_currency, $shop_country ) {

		$klarna = new Klarna();
		$config = new KlarnaConfig();

		// Default required options
		$config['mode'] = Klarna::BETA;
		$config['pcStorage'] = 'json';
		$config['pcURI'] = './pclasses.json';

		// Configuration needed for the checkout service
		$config['eid'] = $this->get_eid();
		$config['secret'] = $this->get_secret();

		$klarna->setConfig($config);

		// Get Klarna locale based on shop country
		switch ( $this->shop_country ) {
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

		try {
		    $response = $klarna->checkoutService(
		        $woocommerce->cart->total, // Total price of the checkout including VAT
		        $this->selected_currency, // Currency used by the checkout
		        $klarna_pms_locale // Locale used by the checkout
		    );
		} catch (KlarnaException $e) {
		    // cURL exception
		    throw $e;
		}

		$data = $response->getData();

		return $data;

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

/**
 * Begin Klarna PMS
 */
$klarna = new Klarna();
$config = new KlarnaConfig();

// Default required options
$config['mode'] = Klarna::BETA;
$config['pcStorage'] = 'json';
$config['pcURI'] = './pclasses.json';

// Configuration needed for the checkout service
$config['eid'] = $this->get_eid();
$config['secret'] = $this->get_secret();

$klarna->setConfig($config);

// Get Klarna locale based on shop country
switch ( $this->shop_country ) {
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

try {
    $response = $klarna->checkoutService(
        $woocommerce->cart->total, // Total price of the checkout including VAT
        $this->selected_currency, // Currency used by the checkout
        $klarna_pms_locale // Locale used by the checkout
    );
} catch (KlarnaException $e) {
    // cURL exception
    throw $e;
}

$data = $response->getData();

if ($response->getStatus() >= 400) {
    // server responded with error
    echo '<pre>';
    throw new Exception(print_r($data, true));
    echo '</pre>';
}

$payment_methods = $data['payment_methods'];
// echo '<pre>';
// print_r( $payment_methods );
// echo '</pre>';

echo '<select id="klarna_account_pclass" name="klarna_account_pclass" class="woocommerce-select">';
echo '<option disabled="disabled" selected="true">---</option>';
foreach ( $payment_methods as $payment_method ) {

	if ( 'part_payment' == $payment_method['group']['code'] ) {

		$payment_data_attr = array();

		foreach ( $payment_method['details'] as $pd_k => $pd_v ) {
			$payment_data_attr[] = 'data-details_' . $pd_k . '="' . implode( ' ', $pd_v ) . '"';
		}

		if ( isset( $payment_method['use_case'] ) && '' != $payment_method['use_case'] ) {
			$payment_data_attr[] = 'data-use_case="' . $payment_method['use_case'] . '"';
		}

		if ( isset( $payment_method['terms']['uri'] ) && '' != $payment_method['terms']['uri'] ) {
			$payment_data_attr[] = 'data-terms_uri="' . $payment_method['terms']['uri'] . '"';
		}

		if ( isset( $payment_method['logo']['uri'] ) && '' != $payment_method['logo']['uri'] ) {
			$payment_data_attr[] = 'data-logo_uri="' . $payment_method['logo']['uri'] . '"';
		}

		$payment_data_attr = array_reverse( $payment_data_attr );

		echo '<option value="' . $payment_method['pclass_id'] . '"' . implode( ' ', $payment_data_attr ) . '>';
		echo $payment_method['title'];
		echo '</option>';
	}

}
echo '</select>';
echo '<div id="klarna-pms-details" style="clear:both;font-size:11px;"></div>';
/**
 * End Klarna PMS
 */
