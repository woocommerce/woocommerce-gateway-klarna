<?php

require_once dirname(dirname(dirname(__FILE__))).'/vendor/autoload.php';

use Klarna\XMLRPC\Klarna;
use Klarna\XMLRPC\Country;
use Klarna\XMLRPC\Language;
use Klarna\XMLRPC\Currency;

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

// Specify for which article(s) you want to refund.
$k->addArtNo(1, 'MG200MMS');

// Adding a return fee is possible. If you are interested in this
// functionality, make sure to always be in contact with Klarna before
// integrating return fees.

// $k->addArticle(
//     1,
//     "",
//     "Restocking fee",
//     11.5,
//     25,
//     0,
//     Flags::NO_FLAG
// );

try {
    $k->creditPart($invNo);

    echo "OK\n";
} catch (\Exception $e) {
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
