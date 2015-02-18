<?php
/**
 * Get PClasses
 *
 * @link http://www.woothemes.com/products/klarna/
 * @since 1.0.0
 *
 * @package WC_Gateway_Klarna
 */

class WC_Gateway_Klarna_Get_PClasses {

	public function __construct(
		$klarna,
		$country,
		$eid,
		$secret,
		$klarna_language,
		$currency_for_country,
		$klarna_mode
	) {

		$this->fetch_pclasses(
			$klarna,
			$country,
			$eid,
			$secret,
			$klarna_language,
			$currency_for_country,
			$klarna_mode
		);

	}

	/**
	 * Retrieve the PClasses from Klarna
	 *
	 * @since 1.0.0
	 */
	function fetch_pclasses(
		$klarna,
		$country,
		$eid,
		$secret,
		$klarna_language,
		$currency_for_country,
		$klarna_mode
	) {
		
		// Get PClasses so that the customer can chose between different payment plans.
		require_once( KLARNA_LIB . 'Klarna.php' );
		require_once( KLARNA_LIB . 'pclasses/storage.intf.php' );
		
		if ( ! function_exists( 'xmlrpc_encode_entitites' ) && ! class_exists( 'xmlrpcresp' ) ) {
			require_once( KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc' );
			require_once( KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc' );
		}

		$klarna->config(
			$eid, // EID
			$secret, // Secret
			$country, // Country
			$klarna_language, // Language
			$currency_for_country, // Currency
			$klarna_mode, // Live or test
			'jsondb', // PClass storage
			'klarna_pclasses_' . $country // PClass storage URI path
		);
		
		if ( $klarna->getPClasses() ) {
			return $klarna->getPClasses();
		} else {
			try {
				// You can specify country (and language, currency if you wish) if you don't want 
				// to use the configured country.
				$klarna->fetchPClasses( $country ); 
				return $klarna->getPClasses();
			}
			catch( Exception $e ) {
				return false;
			}
		}

	}


}