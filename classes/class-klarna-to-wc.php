<?php
/**
 * Formats Klarna order data for WC order
 *
 * @link  http://www.woothemes.com/products/klarna/
 * @since 2.0.0
 *
 * @package WC_Gateway_Klarna
 */

/**
 * This class grabs WC cart contents and formats them so they can
 * be sent to Klarna when a KCO order is being created or updated.
 * 
 * Needs Klarna order object passed as parameter
 * Checks if Rest API is in use
 * WC log class needs to be instantiated
 * 
 * Get customer data
 * Create WC order
 * Add order items
 * Add order note
 * Add order fees
 * Add order shipping
 * Add order addresses
 * Add order tax rows - ?
 * Add order coupons
 * Add order payment method
 * EITHER Store customer (user) ID as post meta
 * OR     Maybe create customer account
 * Empty WooCommerce cart
 * 
 */
class WC_Gateway_Klarna_K2WC {

	/**
	 * Klarna order.
	 *
	 * @since  2.0.0
	 * @access public
	 * @var    array
	 */
	public $klarna_order;

	/**
	 * Is this for Rest API.
	 *
	 * @since  2.0.0
	 * @access public
	 * @var    boolean
	 */
	public $is_rest;

	/**
	 * Constructor
	 *
	 * @since 2.0.0
	 */
	public function __construct( $klarna_order = null, $is_rest = false ) {
		$this->klarna_order = $klarna_order;
		$this->is_rest = $is_rest;
	}

	/**
	 * Check if Rest API is used.
	 * 
	 * Checks if KCO UK or US.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @return boolean
	 */
	public function is_rest() {
		if ( $this->is_rest() ) {
			return true;
		} else {
			return false;
		}
	}

}