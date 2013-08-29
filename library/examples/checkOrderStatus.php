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
 * 2. Check the status on your order (invoice or reservation)
 */

$id = '123456'; // Your reservation or invoice number.

try {
    // [[checkOrderStatus]]
    $result = $k->checkOrderStatus(
        $id,  // Reservation, invoice number or order id.
        0     // Flag specifying number type. 0 = rno or invno. 1 = order id.
    );
    // [[checkOrderStatus]]

    // [[checkOrderStatus:response]]
    "1";
    // [[checkOrderStatus:response]]

    if ($result == KlarnaFlags::ACCEPTED) {
        // Status changed, you can now activate your invoice/reservation.
        echo "Accepted\n";
    } else if ($result == KlarnaFlags::DENIED) {
        echo "Denied\n";
        // Status changed, it is now denied, proceed accordingly.
    } else {
        echo "Pending\n";
        //Order is still pending, try again later.
    }
} catch (Exception $e) {
    //Something went wrong, print the message:
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
