<?php

$encryptionKey = "secret";

$timezone = new DateTimeZone("Africa/Johannesburg"); // SAST timezone
$DateTime = new DateTime("now", $timezone);

$order_id = $_GET['order_id'];

$DateTime = new DateTime();

$data = array(
    'PAYGATE_ID'        => 10011072130,
    'REFERENCE'         => $order_id,
    'AMOUNT'            => 3299,
    'CURRENCY'          => 'ZAR',
    'NOTIFY_URL'        => 'cairnsgames.co.za/php/payweb3/notify.php',
    'RETURN_URL'        => 'https://cairnsgames.co.za/php/payweb3/redirect.php',
    'TRANSACTION_DATE'  => $DateTime->format('Y-m-d H:i:s'),
    'LOCALE'            => 'en-za',
    'COUNTRY'           => 'ZAF',
    'EMAIL'             => 'customer@paygate.co.za',
);

$checksum = md5(implode('', $data) . $encryptionKey);

$data['CHECKSUM'] = $checksum;

$fieldsString = http_build_query($data);

//open connection
$ch = curl_init();

//set the url, number of POST vars, POST data
curl_setopt($ch, CURLOPT_URL, 'https://secure.paygate.co.za/payweb3/initiate.trans');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOBODY, false);
curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsString);

//execute post
$result = curl_exec($ch);

//close connection
curl_close($ch);


echo "RESPONSE<br/>", $result, "<br/>";
// Process the response
parse_str($result, $response); // Parse the query string response

echo "Response Fields<br/>", json_encode($response), "<br/>";

