<?php
/*
Plugin Name: WooCommerce Klarna Gateway
Plugin URI: http://woothemes.com/woocommerce
Description: Extends WooCommerce. Provides a <a href="http://www.klarna.se" target="_blank">klarna</a> API gateway for WooCommerce.<br /> Email <a href="mailto:niklas@krokedil.se">niklas@krokedil.se</a> with any questions.
Version: 1.3
Author: Niklas Högefjord
Author URI: http://krokedil.com
*/

/*  Copyright 2011  Niklas Högefjord  (email : niklas@krokedil.se)

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
	
	    } 
       
		
	
	} // End class WC_Gateway_Klarna
	
	
	// Include our Klarna Invoice class
	require_once 'class-klarna-invoice.php';
	
	// Include our Klarna Account class
	require_once 'class-klarna-account.php';


} // End init_klarna_gateway

/**
 * Add the gateway to WooCommerce
 **/
function add_klarna_gateway( $methods ) { 
	$methods[] = 'WC_Gateway_Klarna_Invoice';
	$methods[] = 'WC_Gateway_Klarna_Account';
	return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_klarna_gateway' );
