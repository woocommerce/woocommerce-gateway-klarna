<?php
/*
Plugin Name: WooCommerce Klarna Gateway
Plugin URI: http://woothemes.com/woocommerce
Description: Extends WooCommerce. Provides a <a href="http://www.klarna.se" target="_blank">klarna</a> API gateway for WooCommerce.
Version: 1.5
Author: Niklas Högefjord
Author URI: http://krokedil.com
*/

/*  Copyright 2011-2012  Niklas Högefjord  (email : niklas@krokedil.se)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    
*/

/**
 * Plugin updates
 * */
if (is_admin()) {
	if ( ! class_exists( 'WooThemes_Plugin_Updater' ) ) require_once( 'woo-updater/plugin-updater.class.php' );
	
	$woo_plugin_updater_klarna = new WooThemes_Plugin_Updater( __FILE__ );
	$woo_plugin_updater_klarna->api_key = 'c10ceaa5d5c8c34eadc20ade748e27bc';
	$woo_plugin_updater_klarna->init();
}

// Init Klarna Gateway after WooCommerce has loaded
add_action('plugins_loaded', 'init_klarna_gateway', 0);

function init_klarna_gateway() {

	// If the WooCommerce payment gateway class is not available, do nothing
	if ( !class_exists( 'woocommerce_payment_gateway' ) ) return;
	
	
	/**
	 * Localisation
	 */
	load_plugin_textdomain('klarna', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');


	// Define Klarna root Dir
	define('KLARNA_DIR', dirname(__FILE__) . '/');
	
	// Define Klarna lib
	define('KLARNA_LIB', dirname(__FILE__) . '/library/');
	
	
	class WC_Gateway_Klarna extends WC_Payment_Gateway {
			
		public function __construct() { 
			global $woocommerce;
			
	        
			$this->shop_country	= get_option('woocommerce_default_country');
			
			// Check if woocommerce_default_country includes state as well. If it does, remove state
        	if (strstr($this->shop_country, ':')) :
        		$this->shop_country = current(explode(':', $this->shop_country));
        	else :
        		$this->shop_country = $this->shop_country;
        	endif;
        	
        	add_action( 'wp_enqueue_scripts', array(&$this, 'klarna_load_scripts'), 5 );
        	
	    }
	    
				
		/**
	 	 * Register and Enqueue Klarna scripts
	 	 */
		function klarna_load_scripts() {
			
			wp_enqueue_script( 'jquery' );
			
			// Invoice terms popup
			if ( is_checkout() ) {
				wp_register_script( 'klarna-invoice-js', 'https://static.klarna.com:444/external/js/klarnainvoice.js', array('jquery'), '1.0', false );
				wp_enqueue_script( 'klarna-invoice-js' );
			}
			
			// Account terms popup
			if ( is_checkout() || is_product() || is_shop() || is_product_category() || is_product_tag() ) {	
				// Original file: https://static.klarna.com:444/external/js/klarnapart.js
				wp_register_script( 'klarna-part-js', plugins_url( '/js/klarnapart.js', __FILE__ ), array('jquery'), '1.0', false );
				wp_enqueue_script( 'klarna-part-js' );
			}

		}
	
	
	} // End class WC_Gateway_Klarna
	
	
	// Include our Klarna Invoice class
	require_once 'class-klarna-invoice.php';
	
	// Include our Klarna Account class
	require_once 'class-klarna-account.php';
	
	// Include our Klarna Special campaign class
	require_once 'class-klarna-campaign.php';


} // End init_klarna_gateway

/**
 * Add the gateway to WooCommerce
 **/
function add_klarna_gateway( $methods ) { 
	$methods[] = 'WC_Gateway_Klarna_Invoice';
	$methods[] = 'WC_Gateway_Klarna_Account';
	$methods[] = 'WC_Gateway_Klarna_Campaign';
	return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_klarna_gateway' );
