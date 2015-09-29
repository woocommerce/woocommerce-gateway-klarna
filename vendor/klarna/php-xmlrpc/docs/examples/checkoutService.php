<?php

require_once dirname(dirname(dirname(__FILE__))) . '/vendor/autoload.php';

$klarna = new Klarna();
$config = new KlarnaConfig();

// Default required options but not used by the checkout service.
$config['mode'] = Klarna::BETA;
$config['pcStorage'] = 'json';
$config['pcURI'] = './pclasses.json';

// Configuration needed for the checkout service
$config['eid'] = 0;
$config['secret'] = "secret";

// Optional configuration for the checkout service
// $config['timeout'] = 15; // time-out in seconds
// $config['checkout_service_uri'] = 'http://localhost/'; // full uri to a custom endpoint

$klarna->setConfig($config);

try {
    $response = $klarna->checkoutService(
        1000.50, // Total price of the checkout including VAT
        'SEK', // Currency used by the checkout
        'sv_SE' // Locale used by the checkout
    );
} catch (KlarnaException $e) {
    // cURL exception
    throw $e;
}

$data = $response->getData();

if ($response->getStatus() >= 400) {
    // server responded with error
    throw new Exception(print_r($data, true));
}

print_r($data);
