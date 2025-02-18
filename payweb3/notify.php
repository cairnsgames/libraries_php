<?php
include 'config.php'; // Include the configuration file
include_once "../breezo/breezo.php";

$params = [];

// Handle POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $params = $_POST;
}

// Handle GET query string parameters
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $queryString = $_SERVER['QUERY_STRING'];
    parse_str($queryString, $params);
}

// Convert parameters to JSON
$jsonData = json_encode($params);

executeQuery("INSERT INTO webhook (data) VALUES (?)", [$jsonData]);

$paymentId = $params['PAY_REQUEST_ID'] ?? null;
$orderid = $params['REFERENCE'] ?? null;

// Transaction status descriptions
$transactionStatuses = [
    "0" => "Not Done",
    "1" => "Approved",
    "2" => "Declined",
    "3" => "Cancelled",
    "4" => "User Cancelled",
    "5" => "Received by PayGate",
    "7" => "Settlement Voided"
];



if (isset($params['TRANSACTION_STATUS'])) {
    switch ($params['TRANSACTION_STATUS']) {
        case "0":
        case 0:
            $newStatus = "pending";
            break;
        case "1":
        case 1:
        case "5":
        case 5:
            $newStatus = "completed";
            processOrderPayment($orderid);
            break;
        default:
            $newStatus = "failed";
            break;
    }
} else {
    $newStatus = null;
}

if (isset($params['TRANSACTION_STATUS']) && isset($transactionStatuses[$params['TRANSACTION_STATUS']])) {
    $params['TRANSACTION_STATUS'] .= " - " . $transactionStatuses[$params['TRANSACTION_STATUS']];
}

echo "Status", $newStatus;

// Update the payment_progress table
if ($paymentId && $newStatus) {
    executeQuery("UPDATE payment_progress SET status = ?, webhook_data = ? WHERE payment_id = ?", [$newStatus, json_encode($params), $paymentId]);
}




?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Query String Parameters</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
        }
    </style>
</head>

<body>
    <h1>Query String Parameters</h1>
    <table>
        <thead>
            <tr>
                <th>Parameter</th>
                <th>Value</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($params as $key => $value): ?>
                <tr>
                    <td><?php echo htmlspecialchars($key); ?></td>
                    <td><?php echo htmlspecialchars($value); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>