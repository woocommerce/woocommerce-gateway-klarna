<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings for Klarna Part Payment
 */
return apply_filters( 'klarna_invoice_form_fields', array(
	'enabled' => array(
		'title' => __( 'Enable/Disable', 'woocommerce-gateway-klarna' ), 
		'type' => 'checkbox', 
		'label' => __( 'Enable Klarna Part Payment', 'woocommerce-gateway-klarna' ), 
		'default' => 'no'
	), 
	'title' => array(
		'title' => __( 'Title', 'woocommerce-gateway-klarna' ), 
		'type' => 'text', 
		'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-klarna' ), 
		'default' => __( 'Part payments', 'woocommerce-gateway-klarna' )
	),
	'description' => array(
		'title' => __( 'Description', 'woocommerce-gateway-klarna' ), 
		'type' => 'textarea', 
		'description' => __( 'This controls the description which the user sees during checkout. ', 'woocommerce-gateway-klarna' ), 
		'default' => ''
	),
	'push_completion' => array(
		'title' => __( 'On order completion', 'woocommerce-gateway-klarna' ), 
		'type' => 'checkbox', 
		'label' => __( 'Activate Klarna order automatically when WooCommerce order is marked complete.', 'woocommerce-gateway-klarna' ), 
		'default' => 'no'
	), 
	'push_cancellation' => array(
		'title' => __( 'On order cancellation', 'woocommerce-gateway-klarna' ), 
		'type' => 'checkbox', 
		'label' => __( 'Cancel Klarna order automatically when WooCommerce order is cancelled', 'woocommerce-gateway-klarna' ), 
		'default' => 'no'
	), 
	'push_update' => array(
		'title' => __( 'On order update', 'woocommerce-gateway-klarna' ), 
		'type' => 'checkbox', 
		'label' => __( 'Update Klarna order automatically when WooCoommerce line items are updated.', 'woocommerce-gateway-klarna' ), 
		'default' => 'no'
	), 
	
	'eid_se' => array(
		'title' => __( 'Eid - Sweden', 'woocommerce-gateway-klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter your Klarna Eid for Sweden. Leave blank to disable.', 'woocommerce-gateway-klarna' ), 
		'default' => ''
	),
	'secret_se' => array(
		'title' => __( 'Shared Secret - Sweden', 'woocommerce-gateway-klarna' ), 
		'type' => 'password', 
		'description' => __( 'Please enter your Klarna Shared Secret for Sweden.', 'woocommerce-gateway-klarna' ), 
		'default' => ''
	),

	'eid_no' => array(
		'title' => __( 'Eid - Norway', 'woocommerce-gateway-klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter your Klarna Eid for Norway. Leave blank to disable.', 'woocommerce-gateway-klarna' ), 
		'default' => ''
	),
	'secret_no' => array(
		'title' => __( 'Shared Secret - Norway', 'woocommerce-gateway-klarna' ), 
		'type' => 'password', 
		'description' => __( 'Please enter your Klarna Shared Secret for Norway.', 'woocommerce-gateway-klarna' ), 
		'default' => ''
	),

	'eid_fi' => array(
		'title' => __( 'Eid - Finland', 'woocommerce-gateway-klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter your Klarna Eid for Finland. Leave blank to disable.', 'woocommerce-gateway-klarna' ), 
		'default' => ''
	),
	'secret_fi' => array(
		'title' => __( 'Shared Secret - Finland', 'woocommerce-gateway-klarna' ), 
		'type' => 'password', 
		'description' => __( 'Please enter your Klarna Shared Secret for Finland.', 'woocommerce-gateway-klarna' ), 
		'default' => ''
	),

	'eid_dk' => array(
		'title' => __( 'Eid - Denmark', 'woocommerce-gateway-klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter your Klarna Eid for Denmark. Leave blank to disable.', 'woocommerce-gateway-klarna' ), 
		'default' => ''
	),
	'secret_dk' => array(
		'title' => __( 'Shared Secret - Denmark', 'woocommerce-gateway-klarna' ), 
		'type' => 'password', 
		'description' => __( 'Please enter your Klarna Shared Secret for Denmark.', 'woocommerce-gateway-klarna' ), 
		'default' => ''
	),

	'eid_de' => array(
		'title' => __( 'Eid - Germany', 'woocommerce-gateway-klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter your Klarna Eid for Germany. Leave blank to disable.', 'woocommerce-gateway-klarna' ), 
		'default' => ''
	),
	'secret_de' => array(
		'title' => __( 'Shared Secret - Germany', 'woocommerce-gateway-klarna' ), 
		'type' => 'password', 
		'description' => __( 'Please enter your Klarna Shared Secret for Germany.', 'woocommerce-gateway-klarna' ), 
		'default' => ''
	),

	'eid_nl' => array(
		'title' => __( 'Eid - Netherlands', 'woocommerce-gateway-klarna' ), 
		'type' => 'text', 
		'description' => __( 'Please enter your Klarna Eid for Netherlands. Leave blank to disable.', 'woocommerce-gateway-klarna' ), 
		'default' => ''
	),
	'secret_nl' => array(
		'title' => __( 'Shared Secret - Netherlands', 'woocommerce-gateway-klarna' ), 
		'type' => 'password', 
		'description' => __( 'Please enter your Klarna Shared Secret for Netherlands.', 'woocommerce-gateway-klarna' ), 
		'default' => ''
	),

	'lower_threshold' => array(
		'title' => __( 'Lower threshold', 'woocommerce-gateway-klarna' ), 
		'type' => 'text', 
		'description' => __( 'Disable Klarna Part Payment if Cart Total is lower than the specified value. Leave blank to disable this feature.', 'woocommerce-gateway-klarna' ), 
		'default' => ''
	),
	'upper_threshold' => array(
		'title' => __( 'Upper threshold', 'woocommerce-gateway-klarna' ), 
		'type' => 'text', 
		'description' => __( 'Disable Klarna Part Payment if Cart Total is higher than the specified value. Leave blank to disable this feature.', 'woocommerce-gateway-klarna' ), 
		'default' => ''
	),

	'ship_to_billing_address' => array(
		'title' => __( 'Send billing address as shipping address', 'woocommerce-gateway-klarna' ), 
		'type' => 'checkbox', 
		'label' => __( 'Send the entered billing address in WooCommerce checkout as shipping address to Klarna.', 'woocommerce-gateway-klarna' ), 
		'default' => 'no'
	),

	'de_consent_terms' => array(
		'title' => __( 'Klarna consent terms (DE & AT only)', 'woocommerce-gateway-klarna' ), 
		'type' => 'checkbox', 
		'label' => __( 'Enable Klarna consent terms checkbox in checkout. This only apply to German and Austrian merchants.', 'woocommerce-gateway-klarna' ), 
		'default' => 'no'
	),

	'testmode' => array(
		'title' => __( 'Test Mode', 'woocommerce-gateway-klarna' ), 
		'type' => 'checkbox', 
		'label' => __( 'Enable Klarna Test Mode. This will only work if you have a Klarna test account. For test purchases with a live account, <a href="http://integration.klarna.com/en/testing/test-persons" target="_blank">follow these instructions</a>.', 'woocommerce-gateway-klarna' ), 
		'default' => 'no'
	)
) );