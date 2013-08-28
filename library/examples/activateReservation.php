<?php

require_once dirname(__DIR__) . '/Klarna.php';

// Dependencies from http://phpxmlrpc.sourceforge.net/
require_once dirname(dirname(__FILE__)) .
    '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc';
require_once dirname(dirname(__FILE__)) .
    '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc';

/**
 * 1. Initialize and setup the Klarna instance.
 */
// [[init]]
$k = new Klarna();
// [[init]]

// [[init:response]]
new Klarna();
// [[init:response]]

// [[config]]
$k->config(
    123456,               // Merchant ID
    'sharedSecret',       // Shared Secret
    KlarnaCountry::SE,    // Country
    KlarnaLanguage::SV,   // Language
    KlarnaCurrency::SEK,  // Currency
    Klarna::BETA,         // Server
    'json',               // PClass Storage
    '/srv/pclasses.json', // PClass Storage URI path
    true,                 // SSL
    true                  // Remote logging of response times of xmlrpc calls
);
// [[config]]

// [[config:response]]
null;
// [[config:response]]

// OR you can set the config to loads from a file, for example /srv/klarna.json:
// $k->setConfig(new KlarnaConfig('/srv/klarna.json'));

/**
 * 2. Add the article(s), shipping and/or handling fee.
 */

// Here we add a normal product to our goods list.
$k->addArticle(
    4,                      // Quantity
    "MG200MMS",             // Article number
    "Matrox G200 MMS",      // Article name/title
    299.99,                 // Price
    25,                     // 25% VAT
    0,                      // Discount
    KlarnaFlags::INC_VAT    // Price is including VAT.
);

// Next we might want to add a shipment fee for the product
// [[addArticle]]
$k->addArticle(
    1,
    "",
    "Shipping fee",
    14.5,
    25,
    0,
    // Price is including VAT and is shipment fee
    KlarnaFlags::INC_VAT | KlarnaFlags::IS_SHIPMENT
);
// [[addArticle]]

// [[addArticle:response]]
null;
// [[addArticle:response]]

// Lastly, we want to use an invoice/handling fee as well
$k->addArticle(
    1,
    "",
    "Handling fee",
    11.5,
    25,
    0,
    // Price is including VAT and is handling/invoice fee
    KlarnaFlags::INC_VAT | KlarnaFlags::IS_HANDLING
);


/**
 * 3. Create and set the address(es).
 */

// Create the address object and specify the values.
// [[setAddress]]
$addr = new KlarnaAddr(
    'always_approved@klarna.com', // email
    '',                           // Telno, only one phone number is needed.
    '0762560000',                 // Cellno
    'Testperson-se',              // Firstname
    'Approved',                   // Lastname
    '',                           // No care of, C/O.
    'Stårgatan 1',                // Street
    '12345',                      // Zip Code
    'Ankeborg',                   // City
    KlarnaCountry::SE,            // Country
    null,                         // HouseNo for German and Dutch customers.
    null                          // House Extension. Dutch customers only.
);

$k->setAddress(KlarnaFlags::IS_BILLING, $addr);
// [[setAddress]]

// [[setAddress:response]]
null;
// [[setAddress:response]]

$k->setAddress(KlarnaFlags::IS_SHIPPING, $addr); // Shipping / delivery address

/**
 * 4. Specify relevant information from your store. (OPTIONAL)
 */

// Set store specific information so you can e.g. search and associate invoices
// with order numbers.
// [[setEstoreInfo]]
$k->setEstoreInfo(
    'order id #1',
    'order id #2'
);
// [[setEstoreInfo]]

// [[setEstoreInfo:response]]
null;
// [[setEstoreInfo:response]]

// If you don't have the order id available at this stage, you can later use the
// method updateOrderNo().

/**
 * 5. Set additional information. (OPTIONAL)
 */

/** Comment **/

// [[setComment]]
$k->setComment('A text string stored in the invoice commentary area.');
// [[setComment]]

// [[setComment:response]]
null;
// [[setComment:response]]

/** Extra info **/

// Normal shipment is defaulted, delays the start of invoice
// expiration/due-date.

// [[setShipmentInfo]]
$k->setShipmentInfo('key', 'value');
// [[setShipmentInfo]]

// [[setShipmentInfo:response]]
null;
// [[setShipmentInfo:response]]

// [[setTravelInfo]]
$k->setTravelInfo('key', 'value');
// [[setTravelInfo]]

// [[setTravelInfo:response]]
null;
// [[setTravelInfo:response]]

// [[setBankInfo]]
$k->setBankInfo('key', 'value');
// [[setBankInfo]]

// [[setBankInfo:response]]
null;
// [[setBankInfo:response]]

// [[setIncomeInfo]]
$k->setIncomeInfo('key', 'value');
// [[setIncomeInfo]]

// [[setIncomeInfo:response]]
null;
// [[setIncomeInfo:response]]

// [[setExtraInfo]]
$k->setExtraInfo('key', 'value');
// [[setExtraInfo]]

// [[setExtraInfo:response]]
null;
// [[setExtraInfo:response]]

/**
 * 6. Invoke activateReservation and transmit the data.
 */

/* Make sure the order status is ACCEPTED, before activation.
   You can do this by using checkOrderStatus(). */

// Here you enter the reservation number you got from reserveAmount():
$rno = '123456';

try {
    // Transmit all the specified data, from the steps above, to Klarna.
    // [[activateReservation]]
    $result = $k->activateReservation(
        '4103219202',           // PNO (Date of birth for DE and NL).
        $rno,                   // Reservation to activate
        null,                   // Gender.
        '',                     // OCR number to use if you have reserved one.
        KlarnaFlags::NO_FLAG,   // Flags to affect behavior.
        // -1, notes that this is an invoice purchase, for part payment purchase
        // you will have a pclass object which you use getId() from.
        KlarnaPClass::INVOICE
    );
    // [[activateReservation]]

    // [[activateReservation:response]]
    array(
        "ok",
        "1234567890"
    );
    // [[activateReservation:response]]

    $risk = $result[0]; // ok or no_risk
    $invno = $result[1];


    echo "risk: {$risk}\ninvno: {$invno}\n";
    // Reservation is activated, proceed accordingly.
} catch(Exception $e) {
    // Something went wrong, print the message:
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
}
