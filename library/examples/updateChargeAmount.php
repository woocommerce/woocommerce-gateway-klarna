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
 * 2. Update charge amount (shipping fee or handling fee).
 */

// Here you enter the invoice number you got from addTransaction():
$invNo = '123456';

try {
    // [[updateChargeAmount]]
    $result = $k->updateChargeAmount(
        $invNo,                   // Invoice number
        KlarnaFlags::IS_SHIPMENT, // IS_SHIPMENT or IS_HANDLING
        16.7                      // Set the shipping fee to 16.7
    );
    // [[updateChargeAmount]]

    // [[updateChargeAmount:response]]
    "123456";
    // [[updateChargeAmount:response]]

    echo "Result: {$result}\n";
    /* Charge type updated successfully, proceed accordingly.
       $result contains the same invoice number.
     */
} catch(Exception $e) {
    // Something went wrong, print the message:
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
