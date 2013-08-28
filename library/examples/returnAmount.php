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
 * 2. Give a discount on the invoice.
 */

// Here you enter the invoice number:
$invNo = '123456';

try {
    // [[returnAmount]]
    $result = $k->returnAmount(
        $invNo,               // Invoice number
        19.99,                // Amount given as a discount.
        25,                   // 25% VAT
        KlarnaFlags::INC_VAT, // Amount including VAT.
        "Family discount"     // Description
    );
    // [[returnAmount]]

    // [[returnAmountResult]]
    "123456";
    // [[returnAmountResult]]

    echo "Result: {$result}\n";

    /* Discount given, proceed accordingly.
       $result contains the invoice number of the discounted invoice.
     */
} catch(Exception $e) {
    // Something went wrong, print the message:
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
