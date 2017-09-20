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

// Replace cart with new items

$k->addArticle(
    4,                 // Quantity
    'MG200MMS',        // Article number
    'Matrox G200 MMS', // Article name/title
    299.99,            // Price
    25,                // 25% VAT
    0,                 // Discount
    Flags::INC_VAT     // Price is including VAT.
);

$k->addArticle(1, '', 'Shipping fee', 14.5, 25, 0, Flags::INC_VAT | Flags::IS_SHIPMENT);
$k->addArticle(1, '', 'Handling fee', 11.5, 25, 0, Flags::INC_VAT | Flags::IS_HANDLING);

// For information on what else you can update, refer to the documentation

$rno = '123456';

try {
    $k->update($rno);

    echo "OK\n";
} catch (\Exception $e) {
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
