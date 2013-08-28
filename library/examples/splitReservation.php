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
 * 2. Split the reservation.
 */

// Here you enter the reservation number you got from reserveAmount():
$rno = '123456';

try {
    // Transmit all the specified data, from the steps above, to Klarna.
    // [[splitReservation]]
    $result = $k->splitReservation(
        $rno,                 // Reservation number
        99.5,                 // Amount to be subtracted from the reservation.
        KlarnaFlags::NO_FLAG  // No specific behaviour.
    );
    // [[splitReservation]]

    // [[splitReservation:response]]
    array(
        '12345',
        1
    );
    // [[splitReservation:response]]


    // Split successful, proceed accordingly.
    $newRno = $result[0]; // New reservation number
    $status = $result[1]; // Status of the new reservation (1 or 2)

    echo "New RNO: {$result[0]}\nStatus: {$result[1]}\n";
} catch(Exception $e) {
    // Something went wrong, print the message:
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
