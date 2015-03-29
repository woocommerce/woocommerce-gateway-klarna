<?php
/**
 * WooCommerce Shipping Settings
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Gateway_Klarna_Settings_Page' ) ) :

/**
 * WC_Settings_Shipping
 */
class WC_Gateway_Klarna_Settings_Page extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'klarna';
		$this->label = __( 'Klarna', 'klarna' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_admin_field_shipping_methods', array( $this, 'klarna_setting' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	/**
	 * Get sections
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			'' => __( 'Klarna Invoice and Part Payment', 'klarna' ),
			'checkout' => __( 'Klarna Checkout', 'klarna' ),
			'payment_method_widget' => __( 'Klarna Payment Method (Monthly Cost) Widget', 'klarna' ),
		);

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {

		if ( 'checkout' == $current_section ) {

			$settings = apply_filters( 'woocommerce_klarna_invoice_partpayment_settings', array(

				// Start partpayment and invoice section
				array( 
					'title' => __( 'Klarna Checkout Settings', 'klarna' ), 
					'type' => 'title', 
					'id' => 'klarna_checkout_settings' 
				),

				array(
					'title'    => __( 'Eid Sweden', 'klarna' ),
					'desc'     => __( 'Your Klarna Checkout Eid for Sweden', 'klarna' ),
					'id'       => 'klarna_eid_invoice_partpayment_sweden',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' =>  __( 'Please enter your Klarna Checkout Eid for Sweden. Leave blank to disable.', 'klarna' ),
					'autoload' => false
				),
				array(
					'title'    => __( 'Shared Secret Sweden', 'klarna' ),
					'desc'     => __( 'Your Klarna shared secret for Sweden', 'klarna' ),
					'id'       => 'klarna_secret_invoice_partpayment_sweden',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' =>  __( 'Please enter your Klarna shared secret for Sweden. Leave blank to disable.', 'klarna' ),
					'autoload' => false
				),
				array(
					'title'    => __( 'Klarna Checkout Page Sweden', 'klarna' ),
					'desc'     => '<div style="clear:both">' . __( 'Please enter the URL to the page that acts as Checkout Page for Klarna Checkout Sweden. This page must contain the shortcode [woocommerce_klarna_checkout].', 'klarna' ) . '</div>',
					'id'       => 'klarna_checkout_page_sweden',
					'type'     => 'single_select_page',
					'default'  => '',
					'class'    => 'wc-enhanced-select-nostd',
					'css'      => 'min-width:300px;',
					'desc_tip' => __( 'This page is where Klarna checkout will be displayed.', 'klarna' ),
				),
				array(
					'title'    => __( 'Klarna Checkout Thank You Page Sweden', 'woocommerce' ),
					'desc'     => '<div style="clear:both">' . __( 'Enter the URL to the page that acts as Thanks Page for Klarna Checkout Sweden. This page must contain the shortcode [woocommerce_klarna_checkout]. Leave blank to use the Custom Checkout Page as Thanks Page.', 'woocommerce' ) . '</div>',
					'id'       => 'klarna_thank_you_page_sweden',
					'type'     => 'single_select_page',
					'default'  => '',
					'class'    => 'wc-enhanced-select-nostd',
					'css'      => 'min-width:300px;',
					'desc_tip' => __( 'This page is where customers will be redirected after purchase via Klarna Checkout.', 'klarna' ),
				),

				array(
					'title'    => __( 'Eid Norway', 'klarna' ),
					'desc'     => __( 'Your Klarna Checkout Eid for Norway', 'klarna' ),
					'id'       => 'klarna_eid_invoice_partpayment_norway',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' =>  __( 'Please enter your Klarna Checkout Eid for Norway. Leave blank to disable.', 'klarna' ),
					'autoload' => false
				),
				array(
					'title'    => __( 'Shared Secret Norway', 'klarna' ),
					'desc'     => __( 'Your Klarna shared secret for Norway', 'klarna' ),
					'id'       => 'klarna_secret_invoice_partpayment_norway',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' =>  __( 'Please enter your Klarna shared secret for Norway. Leave blank to disable.', 'klarna' ),
					'autoload' => false
				),
				array(
					'title'    => __( 'Klarna Checkout Page Norway', 'klarna' ),
					'desc'     => '<div style="clear:both">' . __( 'Please enter the URL to the page that acts as Checkout Page for Klarna Checkout Norway. This page must contain the shortcode [woocommerce_klarna_checkout].', 'klarna' ) . '</div>',
					'id'       => 'klarna_checkout_page_norway',
					'type'     => 'single_select_page',
					'default'  => '',
					'class'    => 'wc-enhanced-select-nostd',
					'css'      => 'min-width:300px;',
					'desc_tip' => __( 'This page is where Klarna checkout will be displayed.', 'klarna' ),
				),
				array(
					'title'    => __( 'Klarna Checkout Thank You Page Norway', 'woocommerce' ),
					'desc'     => '<div style="clear:both">' . __( 'Enter the URL to the page that acts as Thanks Page for Klarna Checkout Norway. This page must contain the shortcode [woocommerce_klarna_checkout]. Leave blank to use the Custom Checkout Page as Thanks Page.', 'woocommerce' ) . '</div>',
					'id'       => 'klarna_thank_you_page_norway',
					'type'     => 'single_select_page',
					'default'  => '',
					'class'    => 'wc-enhanced-select-nostd',
					'css'      => 'min-width:300px;',
					'desc_tip' => __( 'This page is where customers will be redirected after purchase via Klarna Checkout.', 'klarna' ),
				),

				array(
					'title'    => __( 'Eid Finland', 'klarna' ),
					'desc'     => __( 'Your Klarna Checkout Eid for Finland', 'klarna' ),
					'id'       => 'klarna_eid_invoice_partpayment_finland',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' =>  __( 'Please enter your Klarna Checkout Eid for Finland. Leave blank to disable.', 'klarna' ),
					'autoload' => false
				),
				array(
					'title'    => __( 'Shared Secret Finland', 'klarna' ),
					'desc'     => __( 'Your Klarna shared secret for Finland', 'klarna' ),
					'id'       => 'klarna_secret_invoice_partpayment_finland',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' =>  __( 'Please enter your Klarna shared secret for Finland. Leave blank to disable.', 'klarna' ),
					'autoload' => false
				),
				array(
					'title'    => __( 'Klarna Checkout Page Finland', 'klarna' ),
					'desc'     => '<div style="clear:both">' . __( 'Please enter the URL to the page that acts as Checkout Page for Klarna Checkout Finland. This page must contain the shortcode [woocommerce_klarna_checkout].', 'klarna' ) . '</div>',
					'id'       => 'klarna_checkout_page_finland',
					'type'     => 'single_select_page',
					'default'  => '',
					'class'    => 'wc-enhanced-select-nostd',
					'css'      => 'min-width:300px;',
					'desc_tip' => __( 'This page is where Klarna checkout will be displayed.', 'klarna' ),
				),
				array(
					'title'    => __( 'Klarna Checkout Thank You Page Finland', 'woocommerce' ),
					'desc'     => '<div style="clear:both">' . __( 'Enter the URL to the page that acts as Thanks Page for Klarna Checkout Finland. This page must contain the shortcode [woocommerce_klarna_checkout]. Leave blank to use the Custom Checkout Page as Thanks Page.', 'woocommerce' ) . '</div>',
					'id'       => 'klarna_thank_you_page_finland',
					'type'     => 'single_select_page',
					'default'  => '',
					'class'    => 'wc-enhanced-select-nostd',
					'css'      => 'min-width:300px;',
					'desc_tip' => __( 'This page is where customers will be redirected after purchase via Klarna Checkout.', 'klarna' ),
				),

				array(
					'title'    => __( 'Eid Germany', 'klarna' ),
					'desc'     => __( 'Your Klarna Checkout Eid for Germany', 'klarna' ),
					'id'       => 'klarna_eid_invoice_partpayment_germany',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' =>  __( 'Please enter your Klarna Checkout Eid for Germany. Leave blank to disable.', 'klarna' ),
					'autoload' => false
				),
				array(
					'title'    => __( 'Shared Secret Germany', 'klarna' ),
					'desc'     => __( 'Your Klarna shared secret for Germany', 'klarna' ),
					'id'       => 'klarna_secret_invoice_partpayment_germany',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' =>  __( 'Please enter your Klarna shared secret for Germany. Leave blank to disable.', 'klarna' ),
					'autoload' => false
				),
				array(
					'title'    => __( 'Klarna Checkout Page Germany', 'klarna' ),
					'desc'     => '<div style="clear:both">' . __( 'Please enter the URL to the page that acts as Checkout Page for Klarna Checkout Germany. This page must contain the shortcode [woocommerce_klarna_checkout].', 'klarna' ) . '</div>',
					'id'       => 'klarna_checkout_page_germany',
					'type'     => 'single_select_page',
					'default'  => '',
					'class'    => 'wc-enhanced-select-nostd',
					'css'      => 'min-width:300px;',
					'desc_tip' => __( 'This page is where Klarna checkout will be displayed.', 'klarna' ),
				),
				array(
					'title'    => __( 'Klarna Checkout Thank You Page Germany', 'woocommerce' ),
					'desc'     => '<div style="clear:both">' . __( 'Enter the URL to the page that acts as Thanks Page for Klarna Checkout Germany. This page must contain the shortcode [woocommerce_klarna_checkout]. Leave blank to use the Custom Checkout Page as Thanks Page.', 'woocommerce' ) . '</div>',
					'id'       => 'klarna_thank_you_page_germany',
					'type'     => 'single_select_page',
					'default'  => '',
					'class'    => 'wc-enhanced-select-nostd',
					'css'      => 'min-width:300px;',
					'desc_tip' => __( 'This page is where customers will be redirected after purchase via Klarna Checkout.', 'klarna' ),
				),

				array( 
					'type' => 'sectionend', 
					'id' => 'klarna_checkout_settings_end' 
				),
				// End checkout section

			) );


		} elseif ( 'payment_method_widget' == $current_section ) {

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
					'desc'          => __( 'Redirect to the cart page after successful addition', 'klarna' ),
					'id'            => 'klarna_display_monthly_price_prio',
					'css'           => 'min-width:150px;',
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
					'type'     => 'text',
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

		} else {

			$settings = apply_filters( 'woocommerce_klarna_invoice_partpayment_settings', array(

				// Start partpayment and invoice section
				array( 
					'title' => __( 'Klarna Invoice and Payment Method Settings', 'klarna' ), 
					'type' => 'title', 
					'id' => 'klarna_invoice_partpayment_settings' 
				),

				array(
					'title'    => __( 'Eid Sweden', 'klarna' ),
					'desc'     => __( 'Your Klarna Checkout Eid for Sweden', 'klarna' ),
					'id'       => 'klarna_eid_checkout_sweden',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' =>  __( 'Please enter your Klarna Checkout Eid for Sweden. Leave blank to disable.', 'klarna' ),
					'autoload' => false
				),
				array(
					'title'    => __( 'Shared Secret Sweden', 'klarna' ),
					'desc'     => __( 'Your Klarna Checkout shared secret for Sweden', 'klarna' ),
					'id'       => 'klarna_secret_checkout_sweden',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' =>  __( 'Please enter your Klarna Checkout shared secret for Sweden. Leave blank to disable.', 'klarna' ),
					'autoload' => false
				),

				array(
					'title'    => __( 'Eid Norway', 'klarna' ),
					'desc'     => __( 'Your Klarna Checkout Eid for Norway', 'klarna' ),
					'id'       => 'klarna_eid_checkout_norway',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' =>  __( 'Please enter your Klarna Checkout Eid for Norway. Leave blank to disable.', 'klarna' ),
					'autoload' => false
				),
				array(
					'title'    => __( 'Shared Secret Norway', 'klarna' ),
					'desc'     => __( 'Your Klarna Checkout shared secret for Norway', 'klarna' ),
					'id'       => 'klarna_secret_checkout_norway',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' =>  __( 'Please enter your Klarna Checkout shared secret for Norway. Leave blank to disable.', 'klarna' ),
					'autoload' => false
				),

				array(
					'title'    => __( 'Eid Finland', 'klarna' ),
					'desc'     => __( 'Your Klarna Checkout Eid for Finland', 'klarna' ),
					'id'       => 'klarna_eid_checkout_finland',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' =>  __( 'Please enter your Klarna Checkout Eid for Finland. Leave blank to disable.', 'klarna' ),
					'autoload' => false
				),
				array(
					'title'    => __( 'Shared Secret Finland', 'klarna' ),
					'desc'     => __( 'Your Klarna Checkout shared secret for Finland', 'klarna' ),
					'id'       => 'klarna_secret_checkout_finland',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' =>  __( 'Please enter your Klarna Checkout shared secret for Finland. Leave blank to disable.', 'klarna' ),
					'autoload' => false
				),

				array(
					'title'    => __( 'Eid Denmark', 'klarna' ),
					'desc'     => __( 'Your Klarna Checkout Eid for Denmark', 'klarna' ),
					'id'       => 'klarna_eid_checkout_denmark',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' =>  __( 'Please enter your Klarna Checkout Eid for Denmark. Leave blank to disable.', 'klarna' ),
					'autoload' => false
				),
				array(
					'title'    => __( 'Shared Secret Denmark', 'klarna' ),
					'desc'     => __( 'Your Klarna Checkout shared secret for Denmark', 'klarna' ),
					'id'       => 'klarna_secret_checkout_denmark',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' =>  __( 'Please enter your Klarna Checkout shared secret for Denmark. Leave blank to disable.', 'klarna' ),
					'autoload' => false
				),

				array(
					'title'    => __( 'Eid Germany', 'klarna' ),
					'desc'     => __( 'Your Klarna Checkout Eid for Germany', 'klarna' ),
					'id'       => 'klarna_eid_checkout_germany',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' =>  __( 'Please enter your Klarna Checkout Eid for Germany. Leave blank to disable.', 'klarna' ),
					'autoload' => false
				),
				array(
					'title'    => __( 'Shared Secret Germany', 'klarna' ),
					'desc'     => __( 'Your Klarna Checkout shared secret for Germany', 'klarna' ),
					'id'       => 'klarna_secret_checkout_germany',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' =>  __( 'Please enter your Klarna Checkout shared secret for Germany. Leave blank to disable.', 'klarna' ),
					'autoload' => false
				),

				array(
					'title'    => __( 'Eid Netherlands', 'klarna' ),
					'desc'     => __( 'Your Klarna Checkout Eid for Netherlands', 'klarna' ),
					'id'       => 'klarna_eid_checkout_netherlands',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' =>  __( 'Please enter your Klarna Checkout Eid for Netherlands. Leave blank to disable.', 'klarna' ),
					'autoload' => false
				),
				array(
					'title'    => __( 'Shared Secret Netherlands', 'klarna' ),
					'desc'     => __( 'Your Klarna Checkout shared secret for Netherlands', 'klarna' ),
					'id'       => 'klarna_secret_checkout_netherlands',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' =>  __( 'Please enter your Klarna Checkout shared secret for Netherlands. Leave blank to disable.', 'klarna' ),
					'autoload' => false
				),

				array( 
					'type' => 'sectionend', 
					'id' => 'klarna_invoice_partpayment_settings_end' 
				),
				// End partpayment and invoice section

			) );

		}

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
	}

	/**
	 * Output the settings
	 */
	public function output() {
		global $current_section;

		$settings = $this->get_settings( $current_section );

		WC_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Output shipping method settings.
	 */
	public function klarna_setting() {


	}

	/**
	 * Save settings
	 */
	public function save() {
		global $current_section;

		if ( ! $current_section ) {

			$settings = $this->get_settings();

			WC_Admin_Settings::save_fields( $settings );
			WC()->shipping->process_admin_options();

		} elseif ( class_exists( $current_section ) ) {

			$current_section_class = new $current_section();

			do_action( 'woocommerce_update_options_' . $this->id . '_' . $current_section_class->id );
		}

		// Increments the transient version to invalidate cache
		WC_Cache_Helper::get_transient_version( 'klarna', true );
	}
}

endif;

return new WC_Gateway_Klarna_Settings_Page();