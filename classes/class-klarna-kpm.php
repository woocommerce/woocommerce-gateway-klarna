<?php

/**
 * Class WC_Klarna_KPM
 *
 * The payment method merges invoice, account and special campaign payment methods.
 *
 * @class     WC_Klarna_PMS
 * @version   1.0
 * @since     2.0
 * @category  Class
 * @author    Krokedil
 * @package WC_Gateway_Klarna
 */

/**
 * Class for Klarna Account payment.
 */
class WC_Gateway_Klarna_KPM extends WC_Gateway_Klarna {

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		global $woocommerce;
		
		parent::__construct();
		
		$this->id                 = 'klarna_kpm';
		$this->method_title       = __( 'Klarna Payment Methods (KPM)', 'klarna' );
		$this->has_fields         = true;
		$this->order_button_text  = apply_filters( 'klarna_order_button_text', __( 'Place order', 'woocommerce' ) );
				
		// Klarna warning banner - used for NL only
		$klarna_wb_img_checkout = 'http://www.afm.nl/~/media/Images/wetten-regels/kredietwaarschuwing/balk_afm1-jpg.ashx';
		$klarna_wb_img_single_product = 'http://www.afm.nl/~/media/Images/wetten-regels/kredietwaarschuwing/balk_afm2-jpg.ashx';
		$klarna_wb_img_product_list = 'http://www.afm.nl/~/media/Images/wetten-regels/kredietwaarschuwing/balk_afm2-jpg.ashx';
		
		// Load the form fields.
		$this->init_form_fields();
		
		// Load the settings.
		$this->init_settings();
		
		// Load shortcodes. 
		// This is used so that the merchant easily can modify the displayed monthly 
		// cost text (on single product and shop page) via the settings page.
		include_once( KLARNA_DIR . 'classes/class-klarna-shortcodes.php' );

		// Define user set variables
		$this->enabled =
			( isset( $this->settings['enabled'] ) ) ? $this->settings['enabled'] : '';
		$this->title =
			( isset( $this->settings['title'] ) ) ? $this->settings['title'] : '';
		$this->description =
			( isset( $this->settings['description'] ) ) ? $this->settings['description'] : '';

		$this->eid_se =
			( isset( $this->settings['eid_se'] ) ) ? $this->settings['eid_se'] : '';
		$this->secret_se = 
			( isset( $this->settings['secret_se'] ) ) ? $this->settings['secret_se'] : '';

		$this->eid_no = 
			( isset( $this->settings['eid_no'] ) ) ? $this->settings['eid_no'] : '';
		$this->secret_no = 
			( isset( $this->settings['secret_no'] ) ) ? $this->settings['secret_no'] : '';

		$this->eid_fi = 
			( isset( $this->settings['eid_fi'] ) ) ? $this->settings['eid_fi'] : '';
		$this->secret_fi = 
			( isset( $this->settings['secret_fi'] ) ) ? $this->settings['secret_fi'] : '';

		$this->eid_dk = 
			( isset( $this->settings['eid_dk'] ) ) ? $this->settings['eid_dk'] : '';
		$this->secret_dk = 
			( isset( $this->settings['secret_dk'] ) ) ? $this->settings['secret_dk'] : '';

		$this->eid_de = 
			( isset( $this->settings['eid_de'] ) ) ? $this->settings['eid_de'] : '';
		$this->secret_de = 
			( isset( $this->settings['secret_de'] ) ) ? $this->settings['secret_de'] : '';

		$this->eid_nl = 
			( isset( $this->settings['eid_nl'] ) ) ? $this->settings['eid_nl'] : '';
		$this->secret_nl = 
			( isset( $this->settings['secret_nl'] ) ) ? $this->settings['secret_nl'] : '';

		$this->eid_at = 
			( isset( $this->settings['eid_at'] ) ) ? $this->settings['eid_at'] : '';
		$this->secret_at = 
			( isset( $this->settings['secret_at'] ) ) ? $this->settings['secret_at'] : '';


		$this->lower_threshold = 
			( isset( $this->settings['lower_threshold'] ) ) ? $this->settings['lower_threshold'] : '';
		$this->upper_threshold = 
			( isset( $this->settings['upper_threshold'] ) ) ? $this->settings['upper_threshold'] : '';
		$this->show_monthly_cost = 
			( isset( $this->settings['show_monthly_cost'] ) ) ? $this->settings['show_monthly_cost'] : '';
		$this->show_monthly_cost_prio = 
			( isset( $this->settings['show_monthly_cost_prio'] ) ) ? $this->settings['show_monthly_cost_prio'] : '15';

		$this->testmode = 
			( isset( $this->settings['testmode'] ) ) ? $this->settings['testmode'] : '';
		$this->de_consent_terms = 
			( isset( $this->settings['de_consent_terms'] ) ) ? $this->settings['de_consent_terms'] : '';
		$this->lower_threshold_monthly_cost = 
			( isset( $this->settings['lower_threshold_monthly_cost'] ) ) ? $this->settings['lower_threshold_monthly_cost'] : '';
		$this->upper_threshold_monthly_cost = 
			( isset( $this->settings['upper_threshold_monthly_cost'] ) ) ? $this->settings['upper_threshold_monthly_cost'] : '';
		$this->ship_to_billing_address = 
			( isset( $this->settings['ship_to_billing_address'] ) ) ? $this->settings['ship_to_billing_address'] : '';

		if ( $this->lower_threshold_monthly_cost == '' ) $this->lower_threshold_monthly_cost = 0;
		if ( $this->upper_threshold_monthly_cost == '' ) $this->upper_threshold_monthly_cost = 10000000;	

		// Authorized countries
		$this->authorized_countries = array();
		if ( ! empty( $this->eid_se ) ) {
			$this->authorized_countries[] = 'SE';
		}
		if ( ! empty( $this->eid_no ) ) {
			$this->authorized_countries[] = 'NO';
		}
		if ( ! empty( $this->eid_fi ) ) {
			$this->authorized_countries[] = 'FI';
		}
		if ( ! empty( $this->eid_dk ) ) {
			$this->authorized_countries[] = 'DK';
		}
		if ( ! empty( $this->eid_de ) ) {
			$this->authorized_countries[] = 'DE';
		}
		if ( ! empty( $this->eid_nl ) ) {
			$this->authorized_countries[] = 'NL';
		}
		
		$klarna_basic_icon = '';
		$klarna_account_info = '';

		// Define Klarna object
		require_once( KLARNA_LIB . 'Klarna.php' );
		$this->klarna = new Klarna();

		// Test mode or Live mode		
		if ( $this->testmode == 'yes' ) {
			// Disable SSL if in testmode
			$this->klarna_ssl = 'false';
			$this->klarna_mode = Klarna::BETA;
		} else {
			// Set SSL if used in webshop
			if ( is_ssl() ) {
				$this->klarna_ssl = 'true';
			} else {
				$this->klarna_ssl = 'false';
			}
			$this->klarna_mode = Klarna::LIVE;
		}

		// Apply filters to Country and language
		$this->klarna_account_info = apply_filters( 'klarna_account_info', $klarna_account_info );
		$this->icon = apply_filters( 'klarna_account_icon', $this->get_account_icon() );	
		$this->icon_basic = apply_filters( 'klarna_basic_icon', $klarna_basic_icon );
		$this->klarna_wb_img_checkout = apply_filters( 'klarna_wb_img_checkout', $klarna_wb_img_checkout );
		$this->klarna_wb_img_single_product = apply_filters( 'klarna_wb_img_single_product', $klarna_wb_img_single_product );
		$this->klarna_wb_img_product_list = apply_filters( 'klarna_wb_img_product_list', $klarna_wb_img_product_list );
				
		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_klarna_account', array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_checkout_process', array( $this, 'klarna_account_checkout_field_process' ) );
		add_action( 'wp_print_footer_scripts', array(  $this, 'footer_scripts' ) );
		
	}
}