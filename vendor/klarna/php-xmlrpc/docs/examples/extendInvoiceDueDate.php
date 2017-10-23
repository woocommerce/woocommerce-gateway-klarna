<?php

require_once dirname(dirname(dirname(__FILE__))) . '/vendor/autoload.php';

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
$numDays = 5; // Numbers of days to extend with

try {
    $result = $k->extendInvoiceDueDate($invNo, $numDays);
    $cost = $result['cost'];
    $newDate = $result['new_date'];

    echo "OK: extended invoice due date to {$newDate} which costs ${cost}\n";
} catch (\Exception $e) {
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
