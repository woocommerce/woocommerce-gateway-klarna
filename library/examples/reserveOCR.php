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
 * 2. Reserve the OCR numbers.
 */

try {
    // Reserve the OCR number(s):
    // [[reserveOCR]]
    $result = $k->reserveOCR(
        1 // Number of OCR numbers you wish to reserve.
    );
    // [[reserveOCR]]

    // [[reserveOCR:response]]
    array(
        "41789461815156"
    );
    // [[reserveOCR:response]]

    echo "Result: \n";
    foreach ($result as $r) {
        echo "{$r}\n";
    }
    /* $result now contains an array of OCR numbers, proceed accordingly. */
} catch(Exception $e) {
    // Something went wrong, print the message:
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
