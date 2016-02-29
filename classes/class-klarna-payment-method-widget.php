<?php

/**
 * Class WC_Klarna_Payment_Method_Widget
 *
 * The Part Payment Widget class informs consumers which payment methods you offer, and helps increase your conversion.
 * The Part Payment Widget can be displayed on single product pages.
 * Settings for the widget is configured in the Klarna Account settings.
 *
 * @class        WC_Klarna_Payment_Method_Widget
 * @version        1.0
 * @since        1.8.1
 * @category    Class
 * @author        Krokedil
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Klarna_Payment_Method_Widget {

	public function __construct() {

		add_action( 'woocommerce_single_product_summary', array( $this, 'display_widget' ), $this->get_priority() );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_filter( 'woocommerce_get_sections_products', array( $this, 'add_section' ) );
		add_filter( 'woocommerce_get_settings_products', array( $this, 'add_settings' ), 10, 2 );

	}


	function get_customer_country() {

		global $woocommerce;

		if ( $woocommerce->customer->get_country() ) {
			$klarna_country = $woocommerce->customer->get_country();
		} else {
			// Get current customers selected language if this is a multi language site
			$iso_code       = explode( '_', get_locale() );
			$shop_language  = strtoupper( $iso_code[0] ); // Country ISO code (SE)
			$klarna_country = $shop_language;
			switch ( $this->parent->shop_country ) {
				case 'NB' :
					$klarna_country = 'NO';
					break;
				case 'SV' :
					$klarna_country = 'SE';
					break;
			}
		}

		return strtolower( $klarna_country );

	}


	function get_klarna_locale() {

		$locale = get_locale();

		switch ( $locale ) {
			case 'da_DK':
				$klarna_locale = 'da_dk';
				break;
			case 'de_DE' :
				$klarna_locale = 'de_de';
				break;
			case 'no_NO' :
			case 'nb_NO' :
			case 'nn_NO' :
				$klarna_locale = 'nb_no';
				break;
			case 'nl_NL' :
				$klarna_locale = 'nl_nl';
				break;
			case 'fi_FI' :
			case 'fi' :
				$klarna_locale = 'fi_fi';
				break;
			case 'sv_SE' :
				$klarna_locale = 'sv_se';
				break;
			case 'de_AT' :
				$klarna_locale = 'de_at';
				break;
			case 'en_US' :
			case 'en_GB' :
				$klarna_locale = 'en_se';
				break;
			default:
				$klarna_locale = '';
		}

		return $klarna_locale;

	}


	function get_klarna_eid() {

		$customer_country = $this->get_customer_country();

		$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
		if ( isset( $checkout_settings[ 'eid_' . $customer_country ] ) ) {
			return $checkout_settings[ 'eid_' . $customer_country ];
		}

		$part_payment_settings = get_option( 'woocommerce_klarna_part_payment_settings' );
		if ( isset( $part_payment_settings[ 'eid_' . $customer_country ] ) ) {
			return $part_payment_settings[ 'eid_' . $customer_country ];
		}

		$invoice_settings = get_option( 'woocommerce_klarna_invoice_settings' );
		if ( isset( $invoice_settings[ 'eid_' . $customer_country ] ) ) {
			return $invoice_settings[ 'eid_' . $customer_country ];
		}

		return false;

	}


	function get_lower_threshold() {
		$lower_threshold = get_option( 'klarna_display_monthly_price_lower_threshold' );
		if ( is_numeric( $lower_threshold ) ) {
			return $lower_threshold;
		}

		return false;
	}


	function get_upper_threshold() {
		$upper_threshold = get_option( 'klarna_display_monthly_price_upper_threshold' );
		if ( is_numeric( $upper_threshold ) ) {
			return $upper_threshold;
		}

		return false;
	}

	function get_enabled() {
		$enabled = get_option( 'klarna_display_monthly_price' );
		if ( 'yes' == $enabled ) {
			return true;
		}

		return false;
	}


	function get_priority() {
		$priority = get_option( 'klarna_display_monthly_price_prio' );

		return $priority;
	}


	function display_widget() {
		if ( ! $this->get_enabled() ) {
			return false;
		}

		global $product;

		$klarna_product_total = $product->get_display_price();
		// Product with no price - do nothing
		if ( empty( $klarna_product_total ) ) {
			return;
		}

		$sum = apply_filters( 'klarna_product_total', $klarna_product_total ); // Product price.

		if ( $this->get_lower_threshold() ) {
			if ( $sum < $this->get_lower_threshold() ) {
				return false;
			}
		}

		if ( $this->get_upper_threshold() ) {
			if ( $sum > $this->get_upper_threshold() ) {
				return false;
			}
		}

		$locale = $this->get_klarna_locale();
		if ( empty( $locale ) ) {
			return;
		}

		$eid = $this->get_klarna_eid();
		if ( empty( $eid ) ) {
			return;
		}

		?>
		<div style="width:100%; height:70px"
		     class="klarna-widget klarna-part-payment"
		     data-eid="<?php echo $eid; ?>"
		     data-locale="<?php echo $locale; ?>"
		     data-price="<?php echo $sum; ?>"
		     data-layout="pale">
		</div>
		<?php
	}


	function get_customer_locale() {
		$locale = get_locale();

		switch ( $locale ) {

			case 'da_DK':
				$klarna_locale = 'da_dk';
				break;
			case 'de_DE' :
				$klarna_locale = 'de_de';
				break;
			case 'no_NO' :
			case 'nb_NO' :
			case 'nn_NO' :
				$klarna_locale = 'nb_no';
				break;
			case 'nl_NL' :
				$klarna_locale = 'nl_nl';
				break;
			case 'fi_FI' :
			case 'fi' :
				$klarna_locale = 'fi_fi';
				break;
			case 'sv_SE' :
				$klarna_locale = 'sv_se';
				break;
			case 'de_AT' :
				$klarna_locale = 'de_at';
				break;
			case 'en_US' :
			case 'en_GB' :
				$klarna_locale = 'en_se';
				break;
			default:
				$klarna_locale = '';

		}

		return $klarna_locale;
	}


	/**
	 * Register and Enqueue Klarna scripts
	 */
	function enqueue_scripts() {
		//$this->show_monthly_cost = 'yes';
		//$this->enabled = 'yes';

		// Part Payment Widget js
		//if ( is_product() && $this->show_monthly_cost == 'yes' && $this->enabled == 'yes' ) {
		wp_register_script( 'klarna-part-payment-widget-js', 'https://cdn.klarna.com/1.0/code/client/all.js', array( 'jquery' ), '1.0', true );
		wp_enqueue_script( 'klarna-part-payment-widget-js' );
		//}

	} // End function


	/**
	 * Get sections
	 *
	 * @return array
	 */
	public function add_section( $sections ) {
		$sections['klarna'] = __( 'Klarna Payment Method (Monthly Cost) Widget', 'woocommerce-gateway-klarna' );

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
					'title' => __( 'Klarna Payment Method Widget Settings', 'woocommerce-gateway-klarna' ),
					'type'  => 'title',
					'id'    => 'klarna_payment_method_widget_settings'
				),

				array(
					'title'    => __( 'Monthly cost', 'woocommerce-gateway-klarna' ),
					'desc'     => __( 'Display monthly cost in product pages', 'woocommerce-gateway-klarna' ),
					'desc_tip' => __( 'If enabled, this option will display Klarna partpayment widget in product pages', 'woocommerce-gateway-klarna' ),
					'id'       => 'klarna_display_monthly_price',
					'default'  => 'no',
					'type'     => 'checkbox',
				),
				array(
					'title'   => __( 'Monthly cost placement', 'woocommerce-gateway-klarna' ),
					'desc'    => __( 'Select where to display the widget in your product pages', 'woocommerce-gateway-klarna' ),
					'id'      => 'klarna_display_monthly_price_prio',
					'class'   => 'wc-enhanced-select',
					'default' => '15',
					'type'    => 'select',
					'options' => array(
						'4'  => __( 'Above Title', 'woocommerce-gateway-klarna' ),
						'7'  => __( 'Between Title and Price', 'woocommerce-gateway-klarna' ),
						'15' => __( 'Between Price and Excerpt', 'woocommerce-gateway-klarna' ),
						'25' => __( 'Between Excerpt and Add to cart button', 'woocommerce-gateway-klarna' ),
						'35' => __( 'Between Add to cart button and Product meta', 'woocommerce-gateway-klarna' ),
						'45' => __( 'Between Product meta and Product sharing buttons', 'woocommerce-gateway-klarna' ),
						'55' => __( 'After Product sharing-buttons', 'woocommerce-gateway-klarna' ),
					),
				),
				array(
					'title'    => __( 'Lower threshold', 'woocommerce-gateway-klarna' ),
					'desc'     => __( 'Lower threshold for monthly cost', 'woocommerce-gateway-klarna' ),
					'id'       => 'klarna_display_monthly_price_lower_threshold',
					'default'  => '',
					'type'     => 'number',
					'desc_tip' => __( 'Monthly cost widget will not be displayed in product pages if product price is less than this value.', 'woocommerce-gateway-klarna' ),
					'autoload' => false
				),
				array(
					'title'    => __( 'Upper threshold', 'woocommerce-gateway-klarna' ),
					'desc'     => __( 'Upper threshold for monthly cost', 'woocommerce-gateway-klarna' ),
					'id'       => 'klarna_display_monthly_price_upper_threshold',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' => __( 'Monthly cost widget will not be displayed in product pages if product price is more than this value.', 'woocommerce-gateway-klarna' ),
					'autoload' => false
				),

				array(
					'type' => 'sectionend',
					'id'   => 'klarna_payment_method_widget_settings_end'
				),
				// End partpayment widget section

			) );

		}

		return $settings;
	}

} // End class
$wc_klarna_partpayment_widget = new WC_Klarna_Payment_Method_Widget;