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
 * 2. Calculate the monthly cost for the product page.
 */

$pclass = $k->getCheapestPClass($sum, $flag);

// Did we get a PClass? (it is false if we didn't)
if ($pclass) {
    // Here we reuse the same values as above:
    // [[calc_monthly_cost]]
    $value = KlarnaCalc::calc_monthly_cost(
        149.99,
        $pclass,
        KlarnaFlags::PRODUCT_PAGE // or KlarnaFlags::CHECKOUT_PAGE
    );
    // [[calc_monthly_cost]]

    // [[calc_monthly_cost_response]]
    45.50;
    // [[calc_monthly_cost_response]]

    echo "Value: {$value}\n";
    /*
      $value is now a rounded monthly cost amount to be displayed to the
      customer.
     */
}
