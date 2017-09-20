<?php

require_once dirname(dirname(dirname(__FILE__))).'/vendor/autoload.php';

use Klarna\XMLRPC\Klarna;
use Klarna\XMLRPC\Country;
use Klarna\XMLRPC\Language;
use Klarna\XMLRPC\Currency;
use Klarna\XMLRPC\Flags;
use Klarna\XMLRPC\Address;
use Klarna\XMLRPC\PClass;
use Klarna\XMLRPC\Calc;

$k = new Klarna();
$k->config(
    0,              // Merchant ID
    'sharedSecret', // Shared secret
    Country::SE,    // Purchase country
    Language::SV,   // Purchase language
    Currency::SEK,  // Purchase currency
    Klarna::BETA    // Server
);

// Method: addArticle

// Handling fee, price including VAT.
$flags = Flags::INC_VAT | Flags::IS_HANDLING;
$k->addArticle(
    4,              // Quantity
    'HANDLING',     // Article number
    'Handling fee', // Article name/title
    50.99,          // Price
    25,             // 25% VAT
    0,              // Discount
    $flags          // Flags
);

// Method: setAddress

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

// PClass related methods

$pclass1 = new PClass();
$pclass1->setId(1);

$pclass2 = new PClass();
$pclass2->setId(2);

// Method: calcMonthlyCost

$amount = 149.99;
$pclass = $k->getCheapestPClass($amount, Flags::PRODUCT_PAGE, array($pclass1, $pclass2));
if ($pclass) {
    $monthly = Calc::calcMonthlyCost($amount, $pclass, Flags::PRODUCT_PAGE);

    echo "monthly cost: {$monthly}\n";
}

// Method: totalCreditPurchaseCost

$amount = 100.50;
if ($pclass) {
    $total = Calc::totalCreditPurchaseCost($amount, $pclass1, Flags::CHECKOUT_PAGE);

    echo "total credit purchase cost: {$total}\n";
}

// Method: calcAPR

$amount = 105.50;
if ($pclass) {
    $apr = Calc::calcApr($amount, $pclass2, Flags::CHECKOUT_PAGE);

    echo "apr: {$apr}\n";
}

// $pclasses is now a list of PClass instances.
