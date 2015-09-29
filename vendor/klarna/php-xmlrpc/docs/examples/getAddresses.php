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

$k->setCountry('se'); // Sweden only
try {
    $addrs = $k->getAddresses('410321-9202');

    // $addrs is a list of KlarnaAddr instances.

    echo "OK\n";
} catch(Exception $e) {
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
