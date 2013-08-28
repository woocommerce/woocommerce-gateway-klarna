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
 * 2. Get the address(es) from Klarna. (Sweden only!)
 */
$k->setCountry('se');
try {
    //Attempt to get the address(es) associated with the SSN/PNO.
    // [[getAddresses]]
    $addrs = $k->getAddresses('410321-9202');
    // [[getAddresses]]

    // [[getAddresses:response]]
    array(
         new KlarnaAddr(
             '',
             '',
             '',
             'Testperson-se',
             'Approved',
             '',
             'Stårgatan 1',
             '12345',
             'Ankeborg',
             KlarnaCountry::SE,
             null,
             null
         )
    );
    // [[getAddresses:response]]
    /* If there exists several addresses you would want to output a list in
       which the customer could choose the address which suits him/her.
     */

    // Print them if available:
    foreach ($addrs as $key => $addr) {
        echo "<table>\n";

        // This only works if the right getAddresses type is used.
        if ($addr->isCompany) {
            echo "\t<tr><td>Company</td><td>{$addr->getCompanyName()}</td></tr>\n";
        } else {
            echo "\t<tr><td>First name</td><td>{$addr->getFirstName()}</td></tr>\n";
            echo "\t<tr><td>Last name</td><td>{$addr->getLastName()}</td></tr>\n";
        }

        echo "\t<tr><td>Street</td><td>{$addr->getStreet()}</td></tr>\n";
        echo "\t<tr><td>Zip code</td><td>{$addr->getZipCode()}</td></tr>\n";
        echo "\t<tr><td>City</td><td>{$addr->getCity()}</td></tr>\n";
        echo "\t<tr><td>Country</td><td>{$addr->getCountryCode()}</td></tr>\n";
        echo "</table>\n";
    }
} catch(Exception $e) {
    //Something went wrong
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
