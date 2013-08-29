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
 * 2. Load the PClasses from the local file or MySQL table.
 */

/*
   PClasses are loaded from the local storage, as defined by "pcStorage"
   and "pcURI".
 */

// Load all PClasses available.
$pclasses = $k->getPClasses();
// Here we can define a specific type of PClass we want to load
// (KlarnaPClass::CAMPAIGN, for example), or leave it empty to get all that
// are usable.

// Next we might want to display the description in a drop down menu:
echo "<select name='pclass'>\n";
foreach ($pclasses as $pclass) {
    echo "\t<option value='{$pclass->getId()}'>{$pclass->getDescription()}</option>\n";
}
echo "</select>\n";

// When the customer has confirmed the purchase and chosen a pclass, you can
// easily grab just that one by doing:
$pclassId = $pclasses[0]->getId(); // Let's say the customer picked the first one
$pclass = $k->getPClass($pclassId);

var_dump($pclass);

// Next we can use $pclassId in the addTransaction call or in the reserveAmount
// call.
