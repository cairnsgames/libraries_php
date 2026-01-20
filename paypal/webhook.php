<?php
// /paypal/webhook.php

require 'vendor/autoload.php';
require_once './dbconfig.php';
require_once './settings.php';

require_once './dbconnection.php';
require_once '../breezo/breezo.php';

use PayPal\Api\WebhookEvent;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;

$clientId = getPropertyValue('b0181e17-e5c6-11ee-bb99-1a220d8ac2c9', 'paypal_clientid');
$secret = getPropertyValue('b0181e17-e5c6-11ee-bb99-1a220d8ac2c9', 'paypal_secret');

// PayPal API Context setup
$apiContext = new ApiContext(
    new OAuthTokenCredential(
        $clientId,
        $secret
    )
);
$apiContext->setConfig([
    'mode' => 'sandbox',
]);


// Retrieve webhook payload
$body = file_get_contents('php://input');
if (empty($body)) {
    http_response_code(400);
    die("No payload received.");
}

$data = json_decode($body);

$custom = json_decode($data->resource->transactions[0]->custom);

// using mysqli add $body to database "webhook" table with field data=$body
$conn = getDbConnection();
$query = "INSERT INTO `webhook` (`data`) VALUES (?)";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $body);
$stmt->execute();


try {
    $details = json_decode($body);
    $paymentid = $details->resource->id;
    $eventtype = $details->event_type;
    $status = "pending";
    if ($eventtype == 'PAYMENTS.PAYMENT.CREATED') {
        $status = "completed";
    }
    $query = "Update payment_progress set webhook_data = ?, event_type = ?, status = ?, custom_order_id = ?, custom_app_id = ?, custom_total_price = ? where payment_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sssssss', $body, $eventtype, $status, $custom->order_id, $custom->app_id, $custom->total_price, $paymentid);
    $stmt->execute();

    // processOrderPayment($custom->order_id);

} catch (Exception $e) {
    http_response_code(400); // Bad Request
    die("Error processing webhook: " . $e->getMessage());
}

http_response_code(200); // Respond to PayPal with HTTP 200 OK
?>