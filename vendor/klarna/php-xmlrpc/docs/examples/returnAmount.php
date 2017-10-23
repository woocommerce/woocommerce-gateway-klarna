<?php

require_once dirname(dirname(dirname(__FILE__))).'/vendor/autoload.php';

use Klarna\XMLRPC\Klarna;
use Klarna\XMLRPC\Country;
use Klarna\XMLRPC\Language;
use Klarna\XMLRPC\Currency;
use Klarna\XMLRPC\Flags;

$k = new Klarna();

$k->config(
    0,              // Merchant ID
    'sharedSecret', // Shared secret
    Country::SE,    // Purchase country
    Language::SV,   // Purchase language
    Currency::SEK,  // Purchase currency
    Klarna::BETA    // Server
);

$invNo = '123456';

try {
    $k->returnAmount(
        $invNo,           // Invoice number
        19.99,            // Amount given as a discount.
        25,               // 25% VAT
        Flags::INC_VAT,   // Amount including VAT.
        'Family discount' // Description
    );

    echo "OK\n";
} catch (\Exception $e) {
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
