<?php
/**
 * Klarna Template Hooks
 *
 * Action/filter hooks used for Klarna functions/templates.
 *
 * @author 		Krokedil
 * @package 	Klarna/Templates
 * @since     	2.4.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Cart contents.
 *
 * @see klarna_checkout_template_get_cart_contents_html()
 */
add_action( 'klarna_checkout_get_cart_contents_html', 'klarna_checkout_template_get_cart_contents_html', 10 );