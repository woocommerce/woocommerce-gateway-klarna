<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings for Klarna Checkout
 */

return apply_filters( 'klarna_checkout_form_fields', array(

	'enabled' => array(
		'title' => __( 'Enable/Disable', 'klarna' ), 
		'type' => 'checkbox', 
		'label' => __( 'Enable Klarna Checkout', 'klarna' ), 
		'default' => 'no'
	), 
	'title' => array(
		'title' => __( 'Title', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'This controls the title which the user sees during checkout.', 'klarna' ), 
		'default' => __( 'Klarna Checkout', 'klarna' )
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
	'push_completion' => array(
		'title' => __( 'On order completion', 'klarna' ), 
		'type' => 'checkbox', 
		'label' => __( 'Activate Klarna order automatically when WooCommerce order is marked complete.', 'klarna' ), 
		'default' => 'no'
	), 
	'push_cancellation' => array(
		'title' => __( 'On order cancellation', 'klarna' ), 
		'type' => 'checkbox', 
		'label' => __( 'Cancel Klarna order automatically when WooCommerce order is cancelled', 'klarna' ), 
		'default' => 'no'
	), 
	'push_update' => array(
		'title' => __( 'On order update', 'klarna' ), 
		'type' => 'checkbox', 
		'label' => __( 'Update Klarna order automatically when WooCoommerce line items are updated.', 'klarna' ), 
		'default' => 'no'
	), 

	'eid_se' => array(
		'title' => __( 'Eid - Sweden', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter your Klarna Eid for Sweden. Leave blank to disable.', 'klarna' ), 
		'default' => ''
	),
	'secret_se' => array(
		'title' => __( 'Shared Secret - Sweden', 'klarna' ), 
		'type' => 'single_select_page', 
		'description' => __( 'Please enter your Klarna Shared Secret for Sweden.', 'klarna' ), 
		'default' => ''
	),
	'klarna_checkout_url_se' => array(
		'title' => __( 'Custom Checkout Page - Sweden', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter the URL to the page that acts as Checkout Page for Klarna Checkout Sweden. This page must contain the shortcode [woocommerce_klarna_checkout].', 'klarna' ), 
		'default' => ''
	),
	'klarna_checkout_thanks_url_se' => array(
		'title' => __( 'Custom Thanks Page - Sweden', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Enter the URL to the page that acts as Thanks Page for Klarna Checkout Sweden. This page must contain the shortcode [woocommerce_klarna_checkout]. Leave blank to use the Custom Checkout Page as Thanks Page.', 'klarna' ), 
		'default' => ''
	),

	'eid_no' => array(
		'title' => __( 'Eid - Norway', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter your Klarna Eid for Norway. Leave blank to disable.', 'klarna' ), 
		'default' => ''
	),
	'secret_no' => array(
		'title' => __( 'Shared Secret - Norway', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter your Klarna Shared Secret for Norway.', 'klarna' ), 
		'default' => ''
	),
	'klarna_checkout_url_no' => array(
		'title' => __( 'Custom Checkout Page - Norway', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter the URL to the page that acts as Checkout Page for Klarna Checkout Norway. This page must contain the shortcode [woocommerce_klarna_checkout].', 'klarna' ), 
		'default' => ''
	),
	'klarna_checkout_thanks_url_no' => array(
		'title' => __( 'Custom Thanks Page - Norway', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Enter the URL to the page that acts as Thanks Page for Klarna Checkout Norway. This page must contain the shortcode [woocommerce_klarna_checkout]. Leave blank to use the Custom Checkout Page as Thanks Page.', 'klarna' ), 
		'default' => ''
	),
			
	'eid_fi' => array(
		'title' => __( 'Eid - Finland', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter your Klarna Eid for Finland. Leave blank to disable.', 'klarna' ), 
		'default' => ''
	),
	'secret_fi' => array(
		'title' => __( 'Shared Secret - Finland', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter your Klarna Shared Secret for Finland.', 'klarna' ), 
		'default' => ''
	),
	'klarna_checkout_url_fi' => array(
		'title' => __( 'Custom Checkout Page - Finland', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter the URL to the page that acts as Checkout Page for Klarna Checkout Finland. This page must contain the shortcode [woocommerce_klarna_checkout].', 'klarna' ), 
		'default' => ''
	),
	'klarna_checkout_thanks_url_fi' => array(
		'title' => __( 'Custom Thanks Page - Finland', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Enter the URL to the page that acts as Thanks Page for Klarna Checkout Finland. This page must contain the shortcode [woocommerce_klarna_checkout]. Leave blank to use the Custom Checkout Page as Thanks Page.', 'klarna' ), 
		'default' => ''
	),

	'eid_de' => array(
		'title' => __( 'Eid - Germany', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter your Klarna Eid for Germany. Leave blank to disable.', 'klarna' ), 
		'default' => ''
	),
	'secret_de' => array(
		'title' => __( 'Shared Secret - Germany', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter your Klarna Shared Secret for Germany.', 'klarna' ), 
		'default' => ''
	),
	'klarna_checkout_url_de' => array(
		'title' => __( 'Custom Checkout Page - Germany', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter the URL to the page that acts as Checkout Page for Klarna Checkout Germany. This page must contain the shortcode [woocommerce_klarna_checkout].', 'klarna' ), 
		'default' => ''
	),
	'klarna_checkout_thanks_url_de' => array(
		'title' => __( 'Custom Thanks Page - Germany', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Enter the URL to the page that acts as Thanks Page for Klarna Checkout Germany. This page must contain the shortcode [woocommerce_klarna_checkout]. Leave blank to use the Custom Checkout Page as Thanks Page.', 'klarna' ), 
		'default' => ''
	),
	'phone_mandatory_de' => array(
		'title' => __( 'Phone Number Mandatory - Germany', 'klarna' ), 
		'type' => 'checkbox', 
		'label' => __( 'Phone number is not mandatory for Klarna Checkout in Germany by default. Check this box to make it mandatory.', 'klarna' ), 
		'default' => 'no'
	),
	'dhl_packstation_de' => array(
		'title' => __( 'DHL Packstation Functionality - Germany', 'klarna' ), 
		'type' => 'checkbox', 
		'label' => __( 'Enable DHL packstation functionality for German customers.', 'klarna' ),
		'default' => 'no'
	),

	'eid_at' => array(
		'title' => __( 'Eid - Austria', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter your Klarna Eid for Austria. Leave blank to disable.', 'klarna' ), 
		'default' => ''
	),
	'secret_at' => array(
		'title' => __( 'Shared Secret - Austria', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter your Klarna Shared Secret for Austria.', 'klarna' ), 
		'default' => ''
	),
	'klarna_checkout_url_at' => array(
		'title' => __( 'Custom Checkout Page - Austria', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter the URL to the page that acts as Checkout Page for Klarna Checkout Austria. This page must contain the shortcode [woocommerce_klarna_checkout].', 'klarna' ), 
		'default' => ''
	),
	'klarna_checkout_thanks_url_at' => array(
		'title' => __( 'Custom Thanks Page - Austria', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Enter the URL to the page that acts as Thanks Page for Klarna Checkout Austria. This page must contain the shortcode [woocommerce_klarna_checkout]. Leave blank to use the Custom Checkout Page as Thanks Page.', 'klarna' ), 
		'default' => ''
	),
	'phone_mandatory_at' => array(
		'title' => __( 'Phone Number Mandatory - Austria', 'klarna' ), 
		'type' => 'checkbox', 
		'label' => __( 'Phone number is not mandatory for Klarna Checkout in Austria by default. Check this box to make it mandatory.', 'klarna' ), 
		'default' => 'no'
	),

	'eid_uk' => array(
		'title' => __( 'Eid - UK', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter your Klarna Eid for UK. Leave blank to disable.', 'klarna' ), 
		'default' => ''
	),
	'secret_uk' => array(
		'title' => __( 'Shared Secret - UK', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter your Klarna Shared Secret for UK.', 'klarna' ), 
		'default' => ''
	),
	'klarna_checkout_url_uk' => array(
		'title' => __( 'Custom Checkout Page - UK', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter the URL to the page that acts as Checkout Page for Klarna Checkout UK. This page must contain the shortcode [woocommerce_klarna_checkout].', 'klarna' ), 
		'default' => ''
	),
	'klarna_checkout_thanks_url_uk' => array(
		'title' => __( 'Custom Thanks Page - UK', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Enter the URL to the page that acts as Thanks Page for Klarna Checkout UK. This page must contain the shortcode [woocommerce_klarna_checkout]. Leave blank to use the Custom Checkout Page as Thanks Page.', 'klarna' ), 
		'default' => ''
	),

	'default_eur_contry' => array(
		'title' => __( 'Default Checkout Country', 'klarna' ),
		'type' => 'select',
		'options' => array(
			'DE' => __( 'Germany', 'klarna' ), 
			'FI' => __( 'Finland', 'klarna' ),
			'AT' => __( 'Austria', 'klarna' ) 
		),
		'description' => __( 'Used by the payment gateway to determine which country should be the default Checkout country if Euro is the selected currency, you as a merchant has an agreement with multiple countries that use Euro and the selected language cant be of help for this decision.', 'klarna' ),
		'default' => 'DE'
	),

	'modify_standard_checkout_url' => array(
		'title' => __( 'Modify Standard Checkout', 'klarna' ), 
		'type' => 'checkbox', 
		'label' => __( 'Make the Custom Checkout Page for Klarna Checkout the default checkout page (i.e. changing the url of the checkout buttons in Cart and the Widget mini cart).', 'klarna' ), 
		'default' => 'yes'
	),
	'add_std_checkout_button' => array(
		'title' => __( 'Button to Standard Checkout', 'klarna' ), 
		'type' => 'checkbox', 
		'label' => __( 'Add a button when the Klarna Checkout form is displayed that links to the standard checkout page.', 'klarna' ), 
		'default' => 'no'
	),
	'std_checkout_button_label' => array(
		'title' => __( 'Label for Standard Checkout Button', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter the text for the button that links to the standard checkout page from the Klarna Checkout form.', 'klarna' ), 
		'default' => ''
	),
	'add_klarna_checkout_button' => array(
		'title' => __( 'Button to Klarna Checkout', 'klarna' ), 
		'type' => 'checkbox', 
		'label' => __( 'Add a button in standard checkout page that links to the Klarna checkout page.', 'klarna' ), 
		'default' => 'no'
	),
	'klarna_checkout_button_label' => array(
		'title' => __( 'Label for Standard Checkout Button', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter the text for the button that links to the Klarna checkout page from the standard checkout page.', 'klarna' ), 
		'default' => ''
	),

	'terms_url' => array(
		'title' => __( 'Terms Page', 'klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter the URL to the page that acts as Terms Page for Klarna Checkout. Leave blank to use the defined WooCommerce Terms Page.', 'klarna' ), 
		'default' => ''
	),

	'create_customer_account' => array(
		'title' => __( 'Create customer account', 'klarna' ), 
		'type' => 'checkbox', 
		'label' => __( 'Automatically create an account for new customers.', 'klarna' ), 
		'default' => 'no'
	),
	'send_new_account_email' => array(
		'title' => __( 'Send New account email when creating new accounts.', 'klarna' ), 
		'type' => 'checkbox', 
		'label' => __( 'Send New account email', 'klarna' ), 
		'default' => 'no'
	),
	'account_signup_text' => array(
		'title' => __( 'Account Signup Text', 'klarna' ), 
		'type' => 'textarea', 
		'description' => __( 'Add text above the Account Registration Form. Useful for legal text for German stores. See documentation for more information. Leave blank to disable.', 'klarna' ), 
		'default' => ''
	),
	'account_login_text' => array(
		'title' => __( 'Account Login Text', 'klarna' ), 
		'type' => 'textarea', 
		'description' => __( 'Add text above the Account Login Form. Useful for legal text for German stores. See documentation for more information. Leave blank to disable.', 'klarna' ), 
		'default' => ''
	),

	'testmode' => array(
		'title' => __( 'Test Mode', 'klarna' ), 
		'type' => 'checkbox', 
		'label' => __( 'Enable Klarna Test Mode. This will only work if you have a Klarna test account.', 'klarna' ), 
		'default' => 'no'
	),
	'debug' => array(
		'title' => __( 'Debug', 'klarna' ), 
		'type' => 'checkbox', 
		'label' => __( 'Enable logging (<code>woocommerce/logs/klarna.txt</code>)', 'klarna' ), 
		'default' => 'no'
	),

	'color_button' => array(
		'title' => __( 'Checkout button color', 'klarna' ), 
		'type' => 'color', 
		'desc_tip' => __( 'Checkout page button color', 'klarna' ), 
		'default' => ''
	),
	'color_button_text' => array(
		'title' => __( 'Checkout button text color', 'klarna' ), 
		'type' => 'color', 
		'desc_tip' => __( 'Checkout page button text color', 'klarna' ), 
		'default' => ''
	),
	'color_checkbox' => array(
		'title' => __( 'Checkout checkbox color', 'klarna' ), 
		'type' => 'color', 
		'desc_tip' => __( 'Checkout page checkbox color', 'klarna' ), 
		'default' => ''
	),
	'color_checkbox_checkmark' => array(
		'title' => __( 'Checkout checkbox checkmark color', 'klarna' ), 
		'type' => 'color', 
		'desc_tip' => __( 'Checkout page checkbox checkmark color', 'klarna' ), 
		'default' => ''
	),
	'color_header' => array(
		'title' => __( 'Checkout header color', 'klarna' ), 
		'type' => 'color', 
		'desc_tip' => __( 'Checkout page header color', 'klarna' ), 
		'default' => ''
	),
	'color_link' => array(
		'title' => __( 'Checkout link color', 'klarna' ), 
		'type' => 'color', 
		'desc_tip' => __( 'Checkout page link color', 'klarna' ), 
		'default' => ''
	)

) );