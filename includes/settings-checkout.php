<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Settings for Klarna Checkout
 */

return apply_filters( 'klarna_checkout_form_fields', array(

	'enabled' => array(
		'title'   => __( 'Enable/Disable', 'woocommerce-gateway-klarna' ),
		'type'    => 'checkbox',
		'label'   => __( 'Enable Klarna Checkout', 'woocommerce-gateway-klarna' ),
		'default' => 'no'
	),
	'title'   => array(
		'title'       => __( 'Title', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-klarna' ),
		'default'     => __( 'Klarna Checkout', 'woocommerce-gateway-klarna' )
	),
	/*
	'paymentaction' => array(
		'title'       => __( 'Payment Action', 'woocommerce' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'description' => __( 'Choose whether you wish to capture funds immediately or authorize payment only.', 'woocommerce' ),
		'default'     => 'sale',
		'desc_tip'    => true,
		'options'     => array(
			'sale'          => __( 'Capture', 'woocommerce' ),
			'authorization' => __( 'Authorize', 'woocommerce' )
		)
	),
	*/

	'order_settings_title' => array(
		'title' => __( 'Order management settings', 'woocommerce-gateway-klarna' ),
		'type'  => 'title',
	),
	'push_completion'      => array(
		'title'   => __( 'On order completion', 'woocommerce-gateway-klarna' ),
		'type'    => 'checkbox',
		'label'   => __( 'Activate Klarna order automatically when WooCommerce order is marked complete.', 'woocommerce-gateway-klarna' ),
		'default' => 'no'
	),
	'push_cancellation'    => array(
		'title'   => __( 'On order cancellation', 'woocommerce-gateway-klarna' ),
		'type'    => 'checkbox',
		'label'   => __( 'Cancel Klarna order automatically when WooCommerce order is cancelled', 'woocommerce-gateway-klarna' ),
		'default' => 'no'
	),
	'push_update'          => array(
		'title'   => __( 'On order update', 'woocommerce-gateway-klarna' ),
		'type'    => 'checkbox',
		'label'   => __( 'Update Klarna order automatically when WooCoommerce line items are updated.', 'woocommerce-gateway-klarna' ),
		'default' => 'no'
	),

	'sweden_settings_title'         => array(
		'title' => __( 'Sweden', 'woocommerce-gateway-klarna' ),
		'type'  => 'title',
	),
	'eid_se'                        => array(
		'title'       => __( 'Eid - Sweden', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter your Klarna Eid for Sweden. Leave blank to disable.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'secret_se'                     => array(
		'title'       => __( 'Shared Secret - Sweden', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter your Klarna Shared Secret for Sweden.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'klarna_checkout_url_se'        => array(
		'title'       => __( 'Custom Checkout Page - Sweden', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter the URL to the page that acts as Checkout Page for Klarna Checkout Sweden. This page must contain the shortcode [woocommerce_klarna_checkout].', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'klarna_checkout_thanks_url_se' => array(
		'title'       => __( 'Custom Thanks Page - Sweden', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Enter the URL to the page that acts as Thanks Page for Klarna Checkout Sweden. This page must contain the shortcode [woocommerce_klarna_checkout]. Leave blank to use the Custom Checkout Page as Thanks Page.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),

	'norway_settings_title'         => array(
		'title' => __( 'Norway', 'woocommerce-gateway-klarna' ),
		'type'  => 'title',
	),
	'eid_no'                        => array(
		'title'       => __( 'Eid - Norway', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter your Klarna Eid for Norway. Leave blank to disable.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'secret_no'                     => array(
		'title'       => __( 'Shared Secret - Norway', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter your Klarna Shared Secret for Norway.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'klarna_checkout_url_no'        => array(
		'title'       => __( 'Custom Checkout Page - Norway', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter the URL to the page that acts as Checkout Page for Klarna Checkout Norway. This page must contain the shortcode [woocommerce_klarna_checkout].', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'klarna_checkout_thanks_url_no' => array(
		'title'       => __( 'Custom Thanks Page - Norway', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Enter the URL to the page that acts as Thanks Page for Klarna Checkout Norway. This page must contain the shortcode [woocommerce_klarna_checkout]. Leave blank to use the Custom Checkout Page as Thanks Page.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),

	'finland_settings_title'        => array(
		'title' => __( 'Finland', 'woocommerce-gateway-klarna' ),
		'type'  => 'title',
	),
	'eid_fi'                        => array(
		'title'       => __( 'Eid - Finland', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter your Klarna Eid for Finland. Leave blank to disable.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'secret_fi'                     => array(
		'title'       => __( 'Shared Secret - Finland', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter your Klarna Shared Secret for Finland.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'klarna_checkout_url_fi'        => array(
		'title'       => __( 'Custom Checkout Page - Finland', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter the URL to the page that acts as Checkout Page for Klarna Checkout Finland. This page must contain the shortcode [woocommerce_klarna_checkout].', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'klarna_checkout_thanks_url_fi' => array(
		'title'       => __( 'Custom Thanks Page - Finland', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Enter the URL to the page that acts as Thanks Page for Klarna Checkout Finland. This page must contain the shortcode [woocommerce_klarna_checkout]. Leave blank to use the Custom Checkout Page as Thanks Page.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),

	'germany_settings_title'        => array(
		'title' => __( 'Germany', 'woocommerce-gateway-klarna' ),
		'type'  => 'title',
	),
	'eid_de'                        => array(
		'title'       => __( 'Eid - Germany', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter your Klarna Eid for Germany. Leave blank to disable.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'secret_de'                     => array(
		'title'       => __( 'Shared Secret - Germany', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter your Klarna Shared Secret for Germany.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'klarna_checkout_url_de'        => array(
		'title'       => __( 'Custom Checkout Page - Germany', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter the URL to the page that acts as Checkout Page for Klarna Checkout Germany. This page must contain the shortcode [woocommerce_klarna_checkout].', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'klarna_checkout_thanks_url_de' => array(
		'title'       => __( 'Custom Thanks Page - Germany', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Enter the URL to the page that acts as Thanks Page for Klarna Checkout Germany. This page must contain the shortcode [woocommerce_klarna_checkout]. Leave blank to use the Custom Checkout Page as Thanks Page.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'phone_mandatory_de'            => array(
		'title'    => __( 'Phone Number Mandatory - Germany', 'woocommerce-gateway-klarna' ),
		'type'     => 'checkbox',
		'label'    => __( 'Phone number is not mandatory for Klarna Checkout in Germany by default. Check this box to make it mandatory.', 'woocommerce-gateway-klarna' ),
		'default'  => 'no',
		'desc_tip' => true
	),
	'dhl_packstation_de'            => array(
		'title'    => __( 'DHL Packstation Functionality - Germany', 'woocommerce-gateway-klarna' ),
		'type'     => 'checkbox',
		'label'    => __( 'Enable DHL packstation functionality for German customers.', 'woocommerce-gateway-klarna' ),
		'default'  => 'no',
		'desc_tip' => true
	),

	'austria_settings_title'        => array(
		'title' => __( 'Austria', 'woocommerce-gateway-klarna' ),
		'type'  => 'title',
	),
	'eid_at'                        => array(
		'title'       => __( 'Eid - Austria', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter your Klarna Eid for Austria. Leave blank to disable.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'secret_at'                     => array(
		'title'       => __( 'Shared Secret - Austria', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter your Klarna Shared Secret for Austria.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'klarna_checkout_url_at'        => array(
		'title'       => __( 'Custom Checkout Page - Austria', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter the URL to the page that acts as Checkout Page for Klarna Checkout Austria. This page must contain the shortcode [woocommerce_klarna_checkout].', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'klarna_checkout_thanks_url_at' => array(
		'title'       => __( 'Custom Thanks Page - Austria', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Enter the URL to the page that acts as Thanks Page for Klarna Checkout Austria. This page must contain the shortcode [woocommerce_klarna_checkout]. Leave blank to use the Custom Checkout Page as Thanks Page.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'phone_mandatory_at'            => array(
		'title'    => __( 'Phone Number Mandatory - Austria', 'woocommerce-gateway-klarna' ),
		'type'     => 'checkbox',
		'label'    => __( 'Phone number is not mandatory for Klarna Checkout in Austria by default. Check this box to make it mandatory.', 'woocommerce-gateway-klarna' ),
		'default'  => 'no',
		'desc_tip' => true
	),

	'uk_settings_title'             => array(
		'title' => __( 'UK', 'woocommerce-gateway-klarna' ),
		'type'  => 'title',
	),
	'eid_uk'                        => array(
		'title'       => __( 'Eid - UK', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter your Klarna Eid for UK. Leave blank to disable.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'secret_uk'                     => array(
		'title'       => __( 'Shared Secret - UK', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter your Klarna Shared Secret for UK.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'klarna_checkout_url_uk'        => array(
		'title'       => __( 'Custom Checkout Page - UK', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter the URL to the page that acts as Checkout Page for Klarna Checkout UK. This page must contain the shortcode [woocommerce_klarna_checkout].', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'klarna_checkout_thanks_url_uk' => array(
		'title'       => __( 'Custom Thanks Page - UK', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Enter the URL to the page that acts as Thanks Page for Klarna Checkout UK. This page must contain the shortcode [woocommerce_klarna_checkout]. Leave blank to use the Custom Checkout Page as Thanks Page.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'uk_ship_only_to_base'          => array(
		'title'   => __( 'Only ship to UK', 'woocommerce-gateway-klarna' ),
		'type'    => 'checkbox',
		'label'   => __( 'Only allow shipping to UK addresses. You need an agreement with Klarna to allow shipping to other countries.', 'woocommerce-gateway-klarna' ),
		'default' => 'yes'
	),

	'us_settings_title'             => array(
		'title' => __( 'USA', 'woocommerce-gateway-klarna' ),
		'type'  => 'title',
	),
	'eid_us'                        => array(
		'title'       => __( 'Eid - USA', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter your Klarna Eid for USA. Leave blank to disable.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'secret_us'                     => array(
		'title'       => __( 'Shared Secret - USA', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter your Klarna Shared Secret for USA.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'klarna_checkout_url_us'        => array(
		'title'       => __( 'Custom Checkout Page - USA', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter the URL to the page that acts as Checkout Page for Klarna Checkout USA. This page must contain the shortcode [woocommerce_klarna_checkout].', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'klarna_checkout_thanks_url_us' => array(
		'title'       => __( 'Custom Thanks Page - USA', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Enter the URL to the page that acts as Thanks Page for Klarna Checkout USA. This page must contain the shortcode [woocommerce_klarna_checkout]. Leave blank to use the Custom Checkout Page as Thanks Page.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),

	'checkout_settings_title'      => array(
		'title' => __( 'Checkout settings', 'woocommerce-gateway-klarna' ),
		'type'  => 'title',
	),
	'default_eur_contry'           => array(
		'title'       => __( 'Default Euro Checkout Country', 'woocommerce-gateway-klarna' ),
		'type'        => 'select',
		'options'     => array(
			'DE' => __( 'Germany', 'woocommerce-gateway-klarna' ),
			'FI' => __( 'Finland', 'woocommerce-gateway-klarna' ),
			'AT' => __( 'Austria', 'woocommerce-gateway-klarna' )
		),
		'description' => __( 'Used by the payment gateway to determine which country should be the default Checkout country if Euro is the selected currency, you as a merchant has an agreement with multiple countries that use Euro and the selected language cant be of help for this decision.', 'woocommerce-gateway-klarna' ),
		'default'     => 'DE',
		'desc_tip'    => true
	),
	'modify_standard_checkout_url' => array(
		'title'   => __( 'Modify Standard Checkout', 'woocommerce-gateway-klarna' ),
		'type'    => 'checkbox',
		'label'   => __( 'Make the Custom Checkout Page for Klarna Checkout the default checkout page (i.e. changing the url of the checkout buttons in Cart and the Widget mini cart).', 'woocommerce-gateway-klarna' ),
		'default' => 'yes'
	),
	'add_std_checkout_button'      => array(
		'title'   => __( 'Button to Standard Checkout', 'woocommerce-gateway-klarna' ),
		'type'    => 'checkbox',
		'label'   => __( 'Add a button when the Klarna Checkout form is displayed that links to the standard checkout page.', 'woocommerce-gateway-klarna' ),
		'default' => 'no'
	),
	'std_checkout_button_label'    => array(
		'title'       => __( 'Label for Standard Checkout Button', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter the text for the button that links to the standard checkout page from the Klarna Checkout form.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
	),
	'add_klarna_checkout_button'   => array(
		'title'   => __( 'Button to Klarna Checkout', 'woocommerce-gateway-klarna' ),
		'type'    => 'checkbox',
		'label'   => __( 'Add a button in standard checkout page that links to the Klarna checkout page.', 'woocommerce-gateway-klarna' ),
		'default' => 'no'
	),
	'klarna_checkout_button_label' => array(
		'title'       => __( 'Label for Standard Checkout Button', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter the text for the button that links to the Klarna checkout page from the standard checkout page.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'terms_url'                    => array(
		'title'       => __( 'Terms Page', 'woocommerce-gateway-klarna' ),
		'type'        => 'text',
		'description' => __( 'Please enter the URL to the page that acts as Terms Page for Klarna Checkout. Leave blank to use the defined WooCommerce Terms Page.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),

	'create_customer_account'   => array(
		'title'   => __( 'Create customer account', 'woocommerce-gateway-klarna' ),
		'type'    => 'checkbox',
		'label'   => __( 'Automatically create an account for new customers.', 'woocommerce-gateway-klarna' ),
		'default' => 'no'
	),
	'send_new_account_email'    => array(
		'title'   => __( 'Send New account email when creating new accounts', 'woocommerce-gateway-klarna' ),
		'type'    => 'checkbox',
		'label'   => __( 'Send New account email', 'woocommerce-gateway-klarna' ),
		'default' => 'no'
	),
	'account_signup_text'       => array(
		'title'       => __( 'Account Signup Text', 'woocommerce-gateway-klarna' ),
		'type'        => 'textarea',
		'description' => __( 'Add text above the Account Registration Form. Useful for legal text for German stores. See documentation for more information. Leave blank to disable.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'account_login_text'        => array(
		'title'       => __( 'Account Login Text', 'woocommerce-gateway-klarna' ),
		'type'        => 'textarea',
		'description' => __( 'Add text above the Account Login Form. Useful for legal text for German stores. See documentation for more information. Leave blank to disable.', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'validate_stock'            => array(
		'title'    => __( 'Check items stock and valid shipping method during checkout', 'woocommerce-gateway-klarna' ),
		'type'     => 'checkbox',
		'label'    => __( 'If this option is checked, stock status will be checked again for all items in the cart
		 while Klarna Checkout request is being processed. Useful for high-volume stores, HTTPS is required. In addition to stock check, a check for valid shipping option will be performed.', 'woocommerce-gateway-klarna' ),
		'default'  => '',
		'desc_tip' => true
	),
	'send_discounts_separately' => array(
		'title'       => __( 'Send discounts as separate items', 'woocommerce-gateway-klarna' ),
		'type'        => 'checkbox',
		'label'       => __( 'If you enable this option discounts will be sent to Klarna as separate cart items instead of being applied to regular cart items.', 'woocommerce-gateway-klarna' ),
		'description' => __( 'Use this if you encounter rounding issues that cause WooCommerce order total not to match Klarna order total.', 'klarna' ),
		'default'     => 'no'
	),

	'color_settings_title'     => array(
		'title' => __( 'Color Settings', 'woocommerce-gateway-klarna' ),
		'type'  => 'title',
	),
	'color_button'             => array(
		'title'       => __( 'Checkout button color', 'woocommerce-gateway-klarna' ),
		'type'        => 'color',
		'description' => __( 'Checkout page button color', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'color_button_text'        => array(
		'title'       => __( 'Checkout button text color', 'woocommerce-gateway-klarna' ),
		'type'        => 'color',
		'description' => __( 'Checkout page button text color', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'color_checkbox'           => array(
		'title'       => __( 'Checkout checkbox color', 'woocommerce-gateway-klarna' ),
		'type'        => 'color',
		'description' => __( 'Checkout page checkbox color', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'color_checkbox_checkmark' => array(
		'title'       => __( 'Checkout checkbox checkmark color', 'woocommerce-gateway-klarna' ),
		'type'        => 'color',
		'description' => __( 'Checkout page checkbox checkmark color', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'color_header'             => array(
		'title'       => __( 'Checkout header color', 'woocommerce-gateway-klarna' ),
		'type'        => 'color',
		'description' => __( 'Checkout page header color', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'color_link'               => array(
		'title'       => __( 'Checkout link color', 'woocommerce-gateway-klarna' ),
		'type'        => 'color',
		'description' => __( 'Checkout page link color', 'woocommerce-gateway-klarna' ),
		'default'     => '',
		'desc_tip'    => true
	),


	'test_mode_settings_title' => array(
		'title' => __( 'Test Mode Settings', 'woocommerce-gateway-klarna' ),
		'type'  => 'title',
	),
	'testmode'                 => array(
		'title'   => __( 'Test Mode', 'woocommerce-gateway-klarna' ),
		'type'    => 'checkbox',
		'label'   => __( 'Enable Klarna Test Mode. This will only work if you have a Klarna test account.', 'woocommerce-gateway-klarna' ),
		'default' => 'no'
	),
	'debug'                    => array(
		'title'       => __( 'Debug', 'woocommerce-gateway-klarna' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable logging.', 'woocommerce-gateway-klarna' ),
		'description' => sprintf( __( 'Log Klarna events, in <code>%s</code>', 'woocommerce' ), wc_get_log_file_path( 'klarna' ) ),
		'default'     => 'no'
	),

) );