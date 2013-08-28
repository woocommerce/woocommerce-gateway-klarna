<?php

require_once dirname(__DIR__) . '/Klarna.php';

// Dependencies from http://phpxmlrpc.sourceforge.net/
require_once dirname(dirname(__FILE__)) .
    '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc';
require_once dirname(dirname(__FILE__)) .
    '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc';

/**
 * 1. Initialize and setup the Klarna instance.
 */

$k = new Klarna();

$k->config(
    123456,               // Merchant ID
    'sharedSecret',       // Shared Secret
    KlarnaCountry::SE,    // Country
    KlarnaLanguage::SV,   // Language
    KlarnaCurrency::SEK,  // Currency
    Klarna::BETA,         // Server
    'json',               // PClass Storage
    '/srv/pclasses.json', // PClass Storage URI path
    true,                 // SSL
    true                  // Remote logging of response times of xmlrpc calls
);

// OR you can set the config to loads from a file, for example /srv/klarna.json:
// $k->setConfig(new KlarnaConfig('/srv/klarna.json'));

/**
 * 2. Change the reservation.
 */

// Here you enter the reservation number you got from reserveAmount():
$rno = '123456';

try {
    // [[changeReservation]]
    $result = $k->changeReservation(
        $rno,                   // Reservation number
        49.99,                  // Amount
        KlarnaFlags::NEW_AMOUNT // Flag deciding if the amount is the new amount
                                // to reserve, or if it is to be added to the
                                // existing amount. (KlarnaFlags::ADD_AMOUNT)
    );
    // [[changeReservation]]

    // [[changeReservation:response]]
    true;
    // [[changeReservation:response]]

    // Reservation changed, proceed accordingly.
    echo "Result: {$result}\n";
} catch (Exception $e) {
    // Something went wrong or the reservation doesn't exist.
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
