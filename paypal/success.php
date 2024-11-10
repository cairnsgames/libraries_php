<?php
// /paypal/success.php

require 'vendor/autoload.php'; // Load Composer dependencies
require_once './dbconfig.php';
require_once './utils.php';
require_once './settings.php';

$clientId = getPropertyValue('b0181e17-e5c6-11ee-bb99-1a220d8ac2c9', 'clientid');
$secret = getPropertyValue('b0181e17-e5c6-11ee-bb99-1a220d8', 'secret');

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;

// PayPal API Context setup
$apiContext = new ApiContext(
    new OAuthTokenCredential(
        $clientid,     // Client ID
        $secret  // Client Secret
    )
);
$apiContext->setConfig([
    'mode' => 'sandbox', // Change to 'live' in production
]);

if (isset($_GET['success']) && $_GET['success'] === 'true') {
    $paymentId = $_GET['paymentId'];
    $payerId = $_GET['PayerID'];

    $payment = Payment::get($paymentId, $apiContext);
    $paymentExecution = new PaymentExecution();
    $paymentExecution->setPayerId($payerId);

    try {
        $payment->execute($paymentExecution, $apiContext);
        success($paymentId);
        echo 'Payment completed successfully!';
    } catch (Exception $ex) {
        failed($paymentId);
        echo 'Payment failed: ' . $ex->getMessage();
    }
} else {
    echo 'Payment was canceled.';
}
?>
