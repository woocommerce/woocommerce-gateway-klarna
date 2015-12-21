<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Backwards compat, change plugin main file
 */
$active_plugins = get_option( 'active_plugins', array() );
foreach ( $active_plugins as $key => $active_plugin ) {
	if ( strstr( $active_plugin, '/gateway-klarna.php' ) ) {
		$active_plugins[ $key ] = str_replace( '/gateway-klarna.php', '/woocommerce-gateway-klarna.php', $active_plugin );
	}
}
update_option( 'active_plugins', $active_plugins );