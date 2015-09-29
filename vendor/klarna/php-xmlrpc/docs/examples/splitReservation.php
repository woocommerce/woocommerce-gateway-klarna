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


try {
    $rno = '123456';
    $amount = 99.5; // Amount to be subtracted from the reservation.
    $result = $k->splitReservation($rno, $amount);

    $newRno = $result[0];
    $status = $result[1];

    // $status is KlarnaFlags::PENDING or KlarnaFlags::ACCEPTED.

    echo "OK: new reservation {$newRno} - order status {$status}\n";
} catch(Exception $e) {
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
