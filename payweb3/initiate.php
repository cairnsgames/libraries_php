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
$log = [];
$error = [];

$timezone = new DateTimeZone("Africa/Johannesburg"); // SAST timezone
$DateTime = new DateTime("now", $timezone);

$host = $_SERVER['HTTP_HOST'];

$returnURL = getSettingOrSecret($appid, 'returnURL', $host);
if (!isset($returnURL) || empty($returnURL)) {
    $returnURL = "https://cairnsgames.co.za/php/payweb3/return.php";
}
$paygateSecret = getSettingOrSecret($appid, 'PaygateSecret', $host);
if (!isset($paygateSecret) || empty($paygateSecret)) {
    $paygateSecret = $PAYGATE_SECRET;
}
$paygateid = getSettingOrSecret($appid, 'PaygateId', $host);
if (!isset($paygateid) || empty($paygateid)) {
    $PAYGATE_ID = $PAYGATE_ID_DEFAULT;
}

// echo "Return URL: $returnURL<br/>\n";
// echo "Paygate ID: $paygateid<br/>\n";

$log[] = "Host: $host";
$log[] = "Paygate ID: $paygateid";
$log[] = "Paygate Secret: $paygateSecret";
$log[] = "Return URL: $returnURL";
$log[] = "App ID: $appid";

$order_id = $_GET['order_id'];
$encryptionKey = $paygateSecret;

$order_details_sql = "SELECT * FROM breezo_order WHERE id = ?";
$order_details = executeQuery($order_details_sql, [$order_id]);

$order = [];

if (empty($order_details)) {
    $order['currency'] = 'ZAR';
    $order['total_price'] = 100;
} else {
    $order = $order_details[0];
}


$log[] = "order details:" . json_encode($order_details);

$currency = $order['currency'];
$amount = $order['total_price'];

$data = array(
    'PAYGATE_ID' => $paygateid,
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

$log[] = "Payment Data: " . json_encode($data);
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



$log[] = "Payweb Response:" . json_encode($result);

PrepareExecSQL("insert into webhook_log (data) values (?)", "s", array(json_encode($log)));

// Process the response
parse_str($result, $response); // Parse the query string response
if (!isset($response['CHECKSUM'])) {
    $response['CHECKSUM'] = '';
    $error[] = "Checksum not found in response";
}
$eccode = $response['CHECKSUM'];

$payment_id = null;
$eccode = null;

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
    'payment_id' => $payment_id,
    'checksum' => $eccode
];
if (count($error)) {
    $out['error'] = $error;
}

echo json_encode($out);
