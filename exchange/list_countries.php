<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../dbutils.php';
require_once 'exchange_functions.php';

global $mysqli;
header('Content-Type: application/json');

$countries = getExchangeRateByCountry();

if (empty($countries)) {
    echo json_encode([
        'success' => false,
        'error' => 'No exchange rate data found for countries.'
    ]);
    exit;
}

$response = [
    'success' => true,
    'countries' => $countries
];

echo json_encode($response);
?>
