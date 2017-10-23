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
    $rno = '123456';
    $amount = 99.5; // Amount to be subtracted from the reservation.
    $result = $k->splitReservation($rno, $amount);

    $newRno = $result[0];
    $status = $result[1];

    // $status is Flags::PENDING or Flags::ACCEPTED.

    echo "OK: new reservation {$newRno} - order status {$status}\n";
} catch (\Exception $e) {
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
