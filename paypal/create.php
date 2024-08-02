<?php
// /paypal/create.php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, OPTIONS');
    header("Access-Control-Allow-Headers: authorization, token, app_id, deviceid, Info, Origin, X-Requested-With, Content-Type, Accept");
    header('Access-Control-Max-Age: 0');
    header('Content-Length: 0');
    header('Content-Type: application/json');
    die("OPTIONS");
} else {
    header("Access-Control-Allow-Origin: *");
    header('Access-Control-Max-Age: 86400'); // cache for 1 day
    header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, OPTIONS');
    header("Access-Control-Allow-Headers: token, deviceid, X-Requested-With, Content-Type, Origin, Cache-Control, Pragma, authorization, Authorization, Accept, Accept-Encoding, app_id");
    header('Access-Control-Allow-Credentials: true');
}

require 'vendor/autoload.php'; // Load Composer dependencies
require_once 'config.php';
require_once 'utils.php';
require_once 'settings.php';

$clientId = getPropertyValue('b0181e17-e5c6-11ee-bb99-1a220d8ac2c9', 'paypal_clientid');
$secret = getPropertyValue('b0181e17-e5c6-11ee-bb99-1a220d8ac2c9', 'paypal_secret');

// echo "clientid: $clientId, secret: $secret \n";

function getECCode($payment)
{
    // Retrieve the links array from the Payment object
    $links = $payment->getLinks();

    // Initialize the EC token variable
    $ecToken = null;

    // Loop through the links to find the approval URL
    foreach ($links as $link) {
        if ($link->getRel() === 'approval_url') {
            $approvalUrl = $link->getHref();
            // Parse the token from the approval URL
            parse_str(parse_url($approvalUrl, PHP_URL_QUERY), $queryParams);
            $ecToken = $queryParams['token'] ?? null;
            break;
        }
    }

    return $ecToken;
}

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Api\Amount;

// PayPal API Context setup
$apiContext = new ApiContext(
    new OAuthTokenCredential(
        $clientId,     // Client ID
        $secret  // Client Secret
    )
);
$apiContext->setConfig([
    'mode' => 'sandbox', // Change to 'live' in production
]);

// Example order details - replace with data from your database
$orderID = $_POST['order_id'];
$totalPrice = $_POST['total_price']; // Get from your database

$amount = new Amount();
$amount->setTotal($totalPrice)
    ->setCurrency('USD');

$transaction = new Transaction();
$transaction->setAmount($amount)
    ->setDescription('Order payment');

$redirectUrls = new RedirectUrls();
$redirectUrls->setReturnUrl($returnUrl)
    ->setCancelUrl($cancelUrl);

$payment = new Payment();
$payment->setIntent('sale')
    ->setPayer(['payment_method' => 'paypal'])
    ->setTransactions([$transaction])
    ->setRedirectUrls($redirectUrls);

try {
    $payment->create($apiContext);
    $paymentId = $payment->getId();
    $eccode = getECCode($payment);
    create($orderID, $paymentId, $eccode);
    echo json_encode(['paymentid' => $paymentId, 'eccode' => $eccode]);
} catch (PayPal\Exception\PayPalConnectionException $ex) {
    $code = $ex->getCode(); // Prints the Error Code
    $message = $ex->getData(); // Prints the detailed error message
    echo json_encode(['code' => $code, 'error' => $message]);
}
?>