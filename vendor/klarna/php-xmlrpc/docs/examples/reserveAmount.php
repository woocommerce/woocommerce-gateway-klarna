<?php

require_once dirname(dirname(dirname(__FILE__))).'/vendor/autoload.php';

use Klarna\XMLRPC\Klarna;
use Klarna\XMLRPC\Country;
use Klarna\XMLRPC\Language;
use Klarna\XMLRPC\Currency;
use Klarna\XMLRPC\Flags;
use Klarna\XMLRPC\Address;
use Klarna\XMLRPC\PClass;

$k = new Klarna();

$k->config(
    0,              // Merchant ID
    'sharedSecret', // Shared secret
    Country::SE,    // Purchase country
    Language::SV,   // Purchase language
    Currency::SEK,  // Purchase currency
    Klarna::BETA    // Server
);

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

$addr = new Address(
    'always_approved@klarna.com', // Email address
    '',                           // Telephone number, only one phone number is needed
    '0762560000',                 // Cell phone number
    'Testperson-se',              // First name (given name)
    'Approved',                   // Last name (family name)
    '',                           // No care of, C/O
    'Stårgatan 1',                // Street address
    '12345',                      // Zip code
    'Ankeborg',                   // City
    Country::SE,                  // Country
    null,                         // House number (AT/DE/NL only)
    null                          // House extension (NL only)
);

$k->setAddress(Flags::IS_BILLING, $addr);
$k->setAddress(Flags::IS_SHIPPING, $addr);

try {
    $result = $k->reserveAmount(
        '4103219202',   // PNO (Date of birth for AT/DE/NL)
        null,           // Flags::MALE, Flags::FEMALE (AT/DE/NL only)
        -1,             // Automatically calculate and reserve the cart total amount
        Flags::NO_FLAG,
        PClass::INVOICE
    );

    $rno = $result[0];
    $status = $result[1];

    // $status is Flags::PENDING or Flags::ACCEPTED.

    echo "OK: reservation {$rno} - order status {$status}\n";
} catch (\Exception $e) {
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
