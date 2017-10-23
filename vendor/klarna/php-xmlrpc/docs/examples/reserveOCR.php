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

try {
    $ocrs = $k->reserveOCR(1);

    // $ocrs is a list of string OCR numbers.

    echo "OK\n";
} catch (\Exception $e) {
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
