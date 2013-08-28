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
 * 2. Retrieve the amount for specific article(s) from an invoice.
 */

// Here you enter the invoice number:
$invNo = '123456';

// Specify for which article(s) you want the amount.
// artNo must be the same as the one you used in addArticle() when you made the
// addTransaction() call.
// [[addArtNo]]
$k->addArtNo(
    1,          // Quantity
    'MG200MMS'  // Article number
);
// [[addArtNo]]

// [[addArtNo:response]]
null;
// [[addArtNo:response]]

try {
    // [[invoicePartAmount]]
    $result = $k->invoicePartAmount($invNo);
    // [[invoicePartAmount]]

    // [[invoicePartAmount:response]]
    45.50;
    // [[invoicePartAmount:response]]

    echo "Result: {$result}\n";
    /* Partial invoice amount successfully retrieved, proceed accordingly.
       $result contains the sum of specified article(s).
     */
} catch(Exception $e) {
    // Something went wrong, print the message:
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
