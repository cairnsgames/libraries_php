<?php
require_once '../dbutils.php';

global $mysqli;
header('Content-Type: application/json');

$result = $mysqli->query("SELECT code, name FROM exchange_currencies ORDER BY code");

$currencies = [];
while ($row = $result->fetch_assoc()) {
    $currencies[$row['code']] = $row['name'];
}

echo json_encode([
    'success' => true,
    'currencies' => $currencies
]);
