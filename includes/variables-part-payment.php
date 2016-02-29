<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * User set variables for Klarna Part Payment
 */
$this->enabled     = $this->get_option( 'enabled' );
$this->testmode    = $this->get_option( 'testmode' );
$this->debug       = $this->get_option( 'debug' );
$this->title       = $this->get_option( 'title' );
$this->description = $this->get_option( 'description' );
$this->log         = new WC_Logger();

$this->push_completion   = ( isset( $this->settings['push_completion'] ) ) ? $this->settings['push_completion'] : '';
$this->push_cancellation = ( isset( $this->settings['push_cancellation'] ) ) ? $this->settings['push_cancellation'] : '';
$this->push_update       = ( isset( $this->settings['push_update'] ) ) ? $this->settings['push_update'] : '';

// Sweden
$this->eid_se    = $this->get_option( 'eid_se' );
$this->secret_se = $this->get_option( 'secret_se' );

// Norway
$this->eid_no    = $this->get_option( 'eid_no' );
$this->secret_no = $this->get_option( 'secret_no' );

// Finland
$this->eid_fi    = $this->get_option( 'eid_fi' );
$this->secret_fi = $this->get_option( 'secret_fi' );

// Denmark
$this->eid_dk    = $this->get_option( 'eid_dk' );
$this->secret_dk = $this->get_option( 'secret_dk' );

// Germany
$this->eid_de    = $this->get_option( 'eid_de' );
$this->secret_de = $this->get_option( 'secret_de' );

// Netherlands
$this->eid_nl    = $this->get_option( 'eid_nl' );
$this->secret_nl = $this->get_option( 'secret_nl' );

// Austria
$this->eid_at    = $this->get_option( 'eid_at' );
$this->secret_at = $this->get_option( 'secret_at' );

// Lower and upper treshold
$this->lower_threshold = $this->get_option( 'lower_threshold' );
$this->upper_threshold = $this->get_option( 'upper_threshold' );

$this->de_consent_terms        = $this->get_option( 'de_consent_terms' );
$this->ship_to_billing_address = $this->get_option( 'ship_to_billing_address' );

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