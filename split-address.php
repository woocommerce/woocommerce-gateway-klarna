<?php
/**
 * Split a string into an array consisting of Street, House Number and
 * House extension.
 *
 * @param string $address Address string to split
 *
 * @return array
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function splitAddress( $address ) {

	// Get everything up to the first number with a regex
	$hasMatch = preg_match( '/^[^0-9]*/', $address, $match );

	// If no matching is possible, return the supplied string as the street
	if ( ! $hasMatch ) {
		return array( $address, '', '' );
	}

	// Remove the street from the address.
	$address = str_replace( $match[0], '', $address );
	$street  = trim( $match[0] );

	// Nothing left to split, return
	if ( strlen( $address == 0 ) ) {
		return array( $street, '', '' );
	}
	// Explode address to an array
	$addrArray = explode( ' ', $address );

	// Shift the first element off the array, that is the house number
	$housenumber = array_shift( $addrArray );

	// If the array is empty now, there is no extension.
	if ( count( $addrArray ) == 0 ) {
		return array( $street, $housenumber, '' );
	}

	// Join together the remaining pieces as the extension.
	$extension = implode( ' ', $addrArray );

	return array( $street, $housenumber, $extension );

}