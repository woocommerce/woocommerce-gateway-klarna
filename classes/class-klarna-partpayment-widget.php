<?php

/**
 * Class WC_Klarna_Partpayment_Widget
 *
 * The Part Payment Widget class informs consumers which payment methods you offer, and helps increase your conversion.
 * The Part Payment Widget can be displayed on single product pages.
 * Settings for the widget is configured in the Klarna Account settings.
 *
 * @class 		WC_Klarna_Partpayment_Widget
 * @version		1.0
 * @since		1.8.1
 * @category	Class
 * @author 		Krokedil
 *
 */
 
class WC_Klarna_Partpayment_Widget {
	
	public function __construct() {
	
		add_action('woocommerce_single_product_summary', array( $this, 'print_product_monthly_cost'), $this->get_monthly_cost_prio());
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts') );
	}
	
	
	function get_monthly_cost_prio() {
		$data = new WC_Gateway_Klarna_Account;
		return $data->get_monthly_cost_prio();
	}
	
	/**
 	 * Register and Enqueue Klarna scripts
 	 */
	function load_scripts() {
		
		$this->show_monthly_cost = 'yes';
		$this->enabled = 'yes';
		
		// Part Payment Widget js
		if ( is_product() &&  $this->show_monthly_cost == 'yes' && $this->enabled == 'yes' ) {
			wp_register_script( 'klarna-part-payment-widget-js', 'https://cdn.klarna.com/1.0/code/client/all.js', array('jquery'), '1.0', true );
			wp_enqueue_script( 'klarna-part-payment-widget-js' );
		}

	} // End function
	
	
	/**
	 * Calc monthly cost on single Product page and print it out
	 **/
	 
	function print_product_monthly_cost() {
		$data = new WC_Gateway_Klarna_Account;
		$data->print_product_monthly_cost();
		
	} // End function

} // End class

$wc_klarna_partpayment_widget = new WC_Klarna_Partpayment_Widget;