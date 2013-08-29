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

/**
 * 2. Activate the reservation
 */
$rno = '123456';

// Optional fields should be set using
// [[setActivateInfo]]
$k->setActivateInfo('key', 'value');
// [[setActivateInfo]]

// [[setActivateInfo:response]]
null;
// [[setActivateInfo:response]]

try {
    // [[activate]]
    $result = $k->activate($rno);
    // [[activate]]

    // [[activate:response]]
    array(
        "ok",
        "1234567890"
    );
    // [[activate:response]]


    $risk = $result[0]; // ok or no_risk
    $invno = $result[1];


    echo "risk: {$risk}\ninvno: {$invno}\n";
    // Reservation is activated, proceed accordingly.
} catch(Exception $e) {
    // Something went wrong, print the message:
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
