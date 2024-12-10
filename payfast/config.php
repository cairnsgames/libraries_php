<?php
define('PAYFAST_MERCHANT_ID', 'your_merchant_id');
define('PAYFAST_MERCHANT_KEY', 'your_merchant_key');
define('PAYFAST_PASSPHRASE', 'your_passphrase');

function generateSignature($data, $passphrase) {
    $signatureString = '';
    ksort($data);
    foreach ($data as $key => $value) {
        $signatureString .= "$key=$value&";
    }
    $signatureString .= "passphrase=$passphrase";
    return md5($signatureString);
}

function createOrder($amount, $itemName) {
    $payfastUrl = "https://sandbox.payfast.co.za/eng/process";
    $merchantId = PAYFAST_MERCHANT_ID;
    $merchantKey = PAYFAST_MERCHANT_KEY;
    $passphrase = PAYFAST_PASSPHRASE;

    $data = array(
        'merchant_id' => $merchantId,
        'merchant_key' => $merchantKey,
        'amount' => $amount,
        'item_name' => $itemName,
        'return_url' => 'http://yourwebsite.com/success.php',
        'cancel_url' => 'http://yourwebsite.com/cancel.php',
        'notify_url' => 'http://yourwebsite.com/payfast/ipn.php',
        'custom_int1' => '123456', // Custom field for tracking
    );

    $data['signature'] = generateSignature($data, $passphrase);
    $queryString = http_build_query($data);

    header("Location: $payfastUrl?$queryString");
    exit();
}

function verifyIpn($data) {
    $payfastUrl = "https://sandbox.payfast.co.za/eng/query/validate";
    $merchantId = PAYFAST_MERCHANT_ID;
    $merchantKey = PAYFAST_MERCHANT_KEY;
    $passphrase = PAYFAST_PASSPHRASE;

    $data['merchant_id'] = $merchantId;
    $data['merchant_key'] = $merchantKey;
    $data['signature'] = generateSignature($data, $passphrase);

    $response = file_get_contents($payfastUrl . '?' . http_build_query($data));
    return trim($response) === 'VALID';
}

function updateOrderStatus($orderId, $status) {
    // Database connection (replace with your actual connection code)
    $conn = new mysqli('localhost', 'username', 'password', 'database');

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $orderId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}
?>
