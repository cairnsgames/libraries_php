<?php
// Display all PHP errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once './config.php';
require_once __DIR__ . '/../utils.php';
include_once "../corsheaders.php";
include_once dirname(__FILE__)."/../settings/settingsfunctions.php";


$appid = getAppId();

$timezone = new DateTimeZone("Africa/Johannesburg"); // SAST timezone
$DateTime = new DateTime("now", $timezone);

$returnURL = getSettingOrSecret($appid, 'returnURL');
if (!isset($returnURL) || empty($returnURL)) {
    $returnURL = "https://cairnsgames.co.za/php/payweb3/return.php";
}

$order_id = $_GET['order_id'];
$encryptionKey = 'secret';

$order_details_sql = "SELECT * FROM breezo_order WHERE id = ?";
$order_details = executeQuery($order_details_sql, [$order_id]);

$order = [];

if (empty($order_details)) {
    $order['currency'] = 'ZAR';
    $order['total_price'] = 100;
} else {
    $order = $order_details[0];
}

$currency = $order['currency'];
$amount = $order['total_price'];

$data = array(
    'PAYGATE_ID' => $PAYGATE_ID,
    'REFERENCE' => $order_id, // Use order_id as the reference
    'AMOUNT' => $amount * 100, // cents
    'CURRENCY' => $currency,
    'RETURN_URL' => $returnURL,
    'TRANSACTION_DATE' => $DateTime->format('Y-m-d H:i:s'),
    'LOCALE' => 'en-za',
    'COUNTRY' => 'ZAF',
    'EMAIL' => 'customer@paygate.co.za',
    'NOTIFY_URL' => 'https://cairnsgames.co.za/php/payweb3/notify.php',
);

$checksum = md5(implode('', $data) . $encryptionKey);

$data['CHECKSUM'] = $checksum;
// echo "Payment Details: ", json_encode($data), "<br/>\n";
// echo "Encryption Key: ", $encryptionKey, "<br/>\n";
// echo "CHECKSUM: ", $checksum, "<br/>\n";

$fieldsString = http_build_query($data);

// Execute cURL request using the new function
$result = executeCurlRequest('https://secure.paygate.co.za/payweb3/initiate.trans', 'POST', $fieldsString);

// echo "RESPONSE: ", $result, "<br/>\n";
// echo "============================<br/>\n";
// Process the response
parse_str($result, $response); // Parse the query string response
$eccode = $response['CHECKSUM'];

if (isset($response['PAY_REQUEST_ID'])) {
    $payment_id = $response['PAY_REQUEST_ID'];
    $eccode = $response['CHECKSUM'];
    $status = "pending";
    $source = "paygate";

    // Insert payment tracking data
    $sql = "INSERT INTO payment_progress (order_id, payment_source, payment_id, eccode, status) VALUES (?, ?, ?, ?,?)";
    executeQuery($sql, [$order_id, $source, $payment_id, $eccode, $status]);
}

$out = [
    'payment_id' => $response['PAY_REQUEST_ID'],
    'checksum' => $eccode
];

echo json_encode($out);
