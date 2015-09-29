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

$rno = '2347666470';

try {
    $status = $k->checkOrderStatus($rno);

    // $status is KlarnaFlags::ACCEPTED, KlarnaFlags::DENIED or KlarnaFlags::PENDING

    echo "OK: order status {$status}\n";
} catch (Exception $e) {
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
