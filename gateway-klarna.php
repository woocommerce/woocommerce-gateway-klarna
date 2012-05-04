<?php
/*
Plugin Name: WooCommerce Klarna Gateway
Plugin URI: http://woothemes.com/woocommerce
Description: Extends WooCommerce. Provides a <a href="http://www.klarna.se" target="_blank">klarna</a> API gateway for WooCommerce.<br /> Email <a href="mailto:niklas@krokedil.se">niklas@krokedil.se</a> with any questions.
Version: 1.1
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

// Init Google Checkout Gateway after WooCommerce has loaded
add_action('plugins_loaded', 'init_klarna_gateway', 0);

function init_klarna_gateway() {

	// If the WooCommerce payment gateway class is not available, do nothing
	if ( !class_exists( 'woocommerce_payment_gateway' ) ) return;
	
	
	/**
	 * Localisation
	 */
	load_plugin_textdomain('klarna', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');


	// Define Klarna lib
	define('KLARNA_LIB', dirname(__FILE__) . '/library/');
	
	class WC_Gateway_Klarna extends WC_Payment_Gateway {
			
		public function __construct() { 
			global $woocommerce;
			
	        
			$this->shop_country	= get_option('woocommerce_default_country');
	
		
			
			// Country and language
			switch ( $this->shop_country )
			{
			case 'DK':
				$this->klarna_country = 'DK';
				$this->klarna_language = 'DA';
				$this->klarna_currency = 'DKK';
				$this->klarna_invoice_terms = 'https://online.klarna.com/villkor_dk.yaws?eid=' . $this->eid . '&charge=' . $this->handlingfee;
				break;
			case 'DE' :
				$this->klarna_country = 'DE';
				$this->klarna_language = 'DE';
				$this->klarna_currency = 'EUR';
				$this->klarna_invoice_terms = 'https://online.klarna.com/villkor_de.yaws?eid=' . $this->eid . '&charge=' . $this->handlingfee;
				break;
			case 'NL' :
				$this->klarna_country = 'NL';
				$this->klarna_language = 'NL';
				$this->klarna_currency = 'EUR';
				$this->klarna_invoice_terms = 'https://online.klarna.com/villkor_nl.yaws?eid=' . $this->eid . '&charge=' . $this->handlingfee;
				break;
			case 'NO' :
				$this->klarna_country = 'NO';
				$this->klarna_language = 'NB';
				$this->klarna_currency = 'NOK';
				$this->klarna_invoice_terms = 'https://online.klarna.com/villkor_no.yaws?eid=' . $this->eid . '&charge=' . $this->handlingfee;
				break;
			case 'FI' :
				$this->klarna_country = 'FI';
				$this->klarna_language = 'FI';
				$this->klarna_currency = 'EUR';
				$this->klarna_invoice_terms = 'https://online.klarna.com/villkor_fi.yaws?eid=' . $this->eid . '&charge=' . $this->handlingfee;
				break;
			case 'SE' :
				$this->klarna_country = 'SE';
				$this->klarna_language = 'SV';
				$this->klarna_currency = 'SEK';
				$this->klarna_invoice_terms = 'https://online.klarna.com/villkor.yaws?eid=' . $this->eid . '&charge=' . $this->handlingfee;
				break;
			default:
				// The sound of one hand clapping
			}
	
			
	    } 
    
    	    

	    
		/**
		 * Admin Panel Options 
		 * - Options for bits like 'title' and availability on a country-by-country basis
		 *
		 * @since 1.0.0
		 */
		public function admin_options() {
	
	    	?>
	    	<h3><?php _e('Klarna', 'klarna'); ?></h3>
		    	<p><?php _e('With Klarna your customers can pay by invoice. Klarna works by adding extra personal information fields and then sending the details to Klarna for verification.', 'klarna'); ?></p>
	    	<table class="form-table">
	    	<?php
	    		// Generate the HTML For the settings form.
	    		$this->generate_settings_html();
	    	?>
			</table><!--/.form-table-->
	    	<?php
	    } // End admin_options()
	    
		
	
	} // End class WC_Gateway_Klarna
	
	
	// Include our Klarna Invoice class
	require_once 'class-klarna-invoice.php';


} // End init_klarna_gateway

/**
 * Add the gateway to WooCommerce
 **/
function add_klarna_gateway( $methods ) { 
	$methods[] = 'WC_Gateway_Klarna_Invoice';
	return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_klarna_gateway' );
