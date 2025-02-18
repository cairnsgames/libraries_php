<?php

include_once './config.php';

$data = array(
    'PAYGATE_ID'        => $PAYGATE_ID,
    'PAY_REQUEST_ID'    => 'BD0E632E-2344-F8F2-2708-C3EA76192E52',
    'REFERENCE'         => $_GET['order_id'] ?? null
);

$checksum = md5(implode('', $data) . $encryptionKey);

$data['CHECKSUM'] = $checksum;

$fieldsString = http_build_query($data);

// Execute cURL request using the new function
$result = executeCurlRequest('https://secure.paygate.co.za/payweb3/query.trans', 'POST', $fieldsString);

// Parse the result to extract the payment ID and transaction status
parse_str($result, $resultData);
$paymentId = $resultData['PAY_REQUEST_ID'] ?? null;
$newStatus = $resultData['TRANSACTION_STATUS'] ?? null;

// Update the payment_progress table
if ($paymentId && $newStatus) {
    executeQuery("UPDATE payment_progress SET status = ?, webhook_data = ? WHERE payment_id = ?", [$newStatus, json_encode($data), $paymentId]);
}

echo $result;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Data</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
    </style>
</head>
<body>
    <h1>Request Data</h1>
    <table>
        <thead>
            <tr>
                <th>Parameter</th>
                <th>Value</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $key => $value): ?>
                <tr>
                    <td><?php echo htmlspecialchars($key); ?></td>
                    <td><?php echo htmlspecialchars($value); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php
            // Display the result from the API call
            parse_str($result, $resultData);
            foreach ($resultData as $key => $value): ?>
                <tr>
                    <td><?php echo htmlspecialchars($key); ?></td>
                    <td><?php echo htmlspecialchars($value); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
