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

$rno = '123456';

try {
    $result = $k->activate($rno, null, Flags::RSRV_SEND_BY_EMAIL);

    // For optional arguments, flags, partial activations and so on, refer to the documentation.
    // See Klarna::setActivateInfo

    $risk = $result[0];  // "ok" or "no_risk"
    $invNo = $result[1]; // "9876451"

    echo "OK: invoice number {$invNo} - risk status {$risk}\n";
} catch (\Exception $e) {
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
