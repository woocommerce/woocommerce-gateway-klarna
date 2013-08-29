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
 * 2. Partially activate the invoice
 */

// Here you specify the quantity of an article you wish to partially activate.
// artNo must be the same as the one you used in addArticle() when you made the
// addTransaction() call.
$k->addArtNo(
    1,          // Quantity
    'MG200MMS'  // Article number
);

// Here you enter the invoice number you got from addTransaction():
$invNo = '123456';

try {
    $result = $k->activatePart(
        $invNo,               // Invoice number
        KlarnaPClass::INVOICE // Or the PClass ID used to make the order.
    );
    $url = $result['url'];
    echo "url: ${url}\n";
    if (isset($result['invno'])) {
        $invno = $result['invno'];
        echo "invno: ${invno}\n";
    }
    // The url points to a PDF file for the invoice.
    // The invno field is only present if the invoice was not entirely activated,
    // and in that case it contains the new invoice number.

    // Invoice activated, proceed accordingly.
} catch(Exception $e) {
    // Something went wrong or the invoice doesn't exist.
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
