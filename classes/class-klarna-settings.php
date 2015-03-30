<?php
/**
 * Adds Klarna payment method widget settings to WooCommerce > Products settings page
 * 
 * @link http://www.woothemes.com/products/klarna/
 * @since 2.0.0
 *
 * @package WC_Gateway_Klarna
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Gateway_Klarna_Settings_Page {

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_filter( 'woocommerce_get_sections_products', array( $this, 'add_section' ) );
		add_filter( 'woocommerce_get_settings_products', array( $this, 'add_settings' ), 10, 2 );
		// add_filter( 'woocommerce_screen_ids', array( $this, 'add_screen_id' ) );

	}


	/**
	 * Get sections
	 *
	 * @return array
	 */
	public function add_section( $sections ) {

		$sections['klarna'] = __( 'Klarna Payment Method (Monthly Cost) Widget', 'klarna' );

		return $sections;

	}


	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function add_settings( $settings, $current_section ) {

		if ( 'klarna' == $current_section ) {

			$settings = apply_filters( 'woocommerce_klarna_payment_method_widget_settings', array(

				// Start partpayment widget section
				array( 
					'title' => __( 'Klarna Payment Method Widget Settings', 'klarna' ), 
					'type' => 'title', 
					'id' => 'klarna_payment_method_widget_settings' 
				),

				array(
					'title'         => __( 'Monthly cost', 'klarna' ),
					'desc'          => __( 'Display monthly cost in product pages', 'klarna' ),
					'desc_tip'      => __( 'If enabled, this option will display Klarna partpayment widget in product pages', 'klarna' ),
					'id'            => 'klarna_display_monthly_price',
					'default'       => 'no',
					'type'          => 'checkbox',
				),
				array(
					'title'         => __( 'Monthly cost placement', 'klarna' ),
					'desc'          => __( 'Select where to display the widget in your product pages', 'klarna' ),
					'id'            => 'klarna_display_monthly_price_prio',
					'class'         => 'wc-enhanced-select',
					'default'       => '15',
					'type'          => 'select',
					'options'  => array(
						'4'  => __( 'Above Title', 'klarna' ),
						'7'  => __( 'Between Title and Price', 'klarna' ),
						'15' => __( 'Between Price and Excerpt', 'klarna' ),
						'25' => __( 'Between Excerpt and Add to cart button', 'klarna' ),
						'35' => __( 'Between Add to cart button and Product meta', 'klarna' ),
						'45' => __( 'Between Product meta and Product sharing buttons', 'klarna' ),
						'55' => __( 'After Product sharing-buttons', 'klarna' ),
					),
				),
				array(
					'title'    => __( 'Lower thershold', 'klarna' ),
					'desc'     => __( 'Lower threshold for monthly cost', 'klarna' ),
					'id'       => 'klarna_display_monthly_price_lower_threshold',
					'default'  => '',
					'type'     => 'number',
					'desc_tip' =>  __( 'Monthly cost widget will not be displayed in product pages if product costs less than this value.', 'klarna' ),
					'autoload' => false
				),
				array(
					'title'    => __( 'Upper thershold', 'klarna' ),
					'desc'     => __( 'Upper threshold for monthly cost', 'klarna' ),
					'id'       => 'klarna_display_monthly_price_upper_threshold',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' =>  __( 'Monthly cost widget will not be displayed in product pages if product costs more than this value.', 'klarna' ),
					'autoload' => false
				),

				array( 
					'type' => 'sectionend', 
					'id' => 'klarna_payment_method_widget_settings_end' 
				),
				// End partpayment widget section
				
			) );			

		}

		return $settings;

	}


	/**
	 * Add screen id
	 * Helps load enhanced page select scripts
	 *
	 * @return array
	 */
	public function add_screen_id( $screen_ids ) {

		$sections['klarna'] = __( 'Klarna Payment Method (Monthly Cost) Widget', 'klarna' );

		return $screen_ids;

	}

}

return new WC_Gateway_Klarna_Settings_Page();