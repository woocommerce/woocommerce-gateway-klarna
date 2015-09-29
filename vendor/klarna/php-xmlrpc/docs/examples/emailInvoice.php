<?php

require_once dirname(dirname(dirname(__FILE__))) . '/vendor/autoload.php';

$k = new Klarna();

$k->config(
    0,                    // Merchant ID
    'sharedSecret',       // Shared secret
    KlarnaCountry::SE,    // Purchase country
    KlarnaLanguage::SV,   // Purchase language
    KlarnaCurrency::SEK,  // Purchase currency
    Klarna::BETA,         // Server
    'json',               // PClass storage
    './pclasses.json'     // PClass storage URI path
);

$invNo = '123456';

try {
    $k->emailInvoice($invNo);

    echo "OK\n";
} catch(Exception $e) {
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
