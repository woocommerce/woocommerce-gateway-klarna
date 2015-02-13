<?php
/**
 * Displays Klarna PClasses
 * 
 * Get PClasses so that the we can see what classes are active for the merchant.
 *
 * @link http://www.woothemes.com/products/klarna/
 * @since 1.0.0
 *
 * @package WC_Gateway_Klarna
 */

require_once( KLARNA_LIB . 'Klarna.php' );
require_once( KLARNA_LIB . 'pclasses/storage.intf.php' );

if ( ! function_exists( 'xmlrpc_encode_entitites' ) && ! class_exists( 'xmlrpcresp' ) ) {
	require_once( KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc' );
	require_once( KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc' );
}

if ( ! empty( $this->authorized_countries ) && $this->enabled == 'yes' ) {
	echo '<h4>' . __( 'Active PClasses', 'klarna' ) . '</h4>';
	foreach ( $this->authorized_countries as $key => $country ) {
		$pclasses = $this->fetch_pclasses( $country );
		if ( $pclasses ) {
			echo '<p>' . $country . '</p>';
			foreach( $pclasses as $pclass ) {
				if ( $pclass->getType() == 0 || $pclass->getType() == 1 ) {
					echo $pclass->getDescription() . ', ';
				}
			}
			echo '<br/>';
		}
	}
}