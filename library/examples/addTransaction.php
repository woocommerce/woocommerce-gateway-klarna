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

$k = new Klarna();

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

// Next we tell the Klarna instance to use the address in the next order.
$k->setAddress(KlarnaFlags::IS_BILLING, $addr);  // Billing / invoice address
$k->setAddress(KlarnaFlags::IS_SHIPPING, $addr); // Shipping / delivery address

/**
 * 4. Specify relevant information from your store. (OPTIONAL)
 */

// Set store specific information so you can e.g. search and associate invoices
// with order numbers.
$k->setEstoreInfo(
    '175012',       // Order ID 1
    '1999110234',   // Order ID 2
    ''              // Optional username, email or identifier
);

// If you don't have the order id available at this stage, you can later use the
// method updateOrderNo().

/**
 * 5. Set additional information. (OPTIONAL)
 */

/** Comment **/

$k->setComment('A text string stored in the invoice commentary area.');

/** Shipment type **/

// Normal shipment is defaulted, delays the start of invoice expiration/due-date.
$k->setShipmentInfo('delay_adjust', KlarnaFlags::EXPRESS_SHIPMENT);


/**
 * 6. Invoke addTransaction and transmit the data.
 */

try {
    // Transmit all the specified data, from the steps above, to Klarna.
    $result = $k->addTransaction(
        '4103219202',             // PNO (Date of birth for DE and NL).
        null,                   // Gender.
        KlarnaFlags::NO_FLAG,   // Flags to affect behavior.
        // -1, notes that this is an invoice purchase, for part payment purchase
        // you will have a pclass object on which you use getId().
        KlarnaPClass::INVOICE
    );

    // Check the order status
    if ($result[1] == KlarnaFlags::PENDING) {
        /* The order is under manual review and will be accepted or denied at a
           later stage. Use cronjob with checkOrderStatus() or visit Klarna
           Online to check to see if the status has changed. You should still
           show it to the customer as it was accepted, to avoid further attempts
           to fraud.
         */
    }

    // Here we get the invoice number
    $invno = $result[0];

    // Order is complete, store it in a database.
    echo "Status: {$result[1]}\nInvno: {$result[0]}\n";
} catch(Exception $e) {
    // The purchase was denied or something went wrong, print the message:
    echo "{$e->getMessage()} (#{$e->getCode()})\n";
    echo $e->getTraceAsString();
}
