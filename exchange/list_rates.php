<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../dbutils.php';

global $mysqli;
header('Content-Type: application/json');

function getParam($key, $default = null) {
    if (isset($_REQUEST[$key])) return $_REQUEST[$key];
    if (isset($_GET[$key])) return $_GET[$key];
    if (isset($_POST[$key])) return $_POST[$key];
    return $default;
}

$base = strtoupper(trim(getParam('base', 'EUR')));
$date = trim(getParam('date'));

if (!$date) {
    $stmt = $mysqli->prepare("SELECT MAX(date) FROM exchange_rates");
    $stmt->execute();
    $stmt->bind_result($date);
    $stmt->fetch();
    $stmt->close();
}

// Step 1: Get all EUR → target_currency rates
$stmt = $mysqli->prepare("
    SELECT target_currency, rate 
    FROM exchange_rates 
    WHERE date = ? AND base_currency = 'EUR'
");
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

$eurRates = [];
while ($row = $result->fetch_assoc()) {
    $eurRates[$row['target_currency']] = floatval($row['rate']);
}
$stmt->close();

// Step 2: Calculate conversions from custom base (not EUR)
$rates = [];

if ($base === 'EUR') {
    $rates = $eurRates;
} else {
    if (!isset($eurRates[$base]) || $eurRates[$base] == 0) {
        echo json_encode([
            'success' => false,
            'error' => "Base currency rate not found or zero for $base on $date"
        ]);
        exit;
    }

    $baseRate = $eurRates[$base];
    foreach ($eurRates as $code => $rate) {
        $rates[$code] = round($rate / $baseRate, 6);
    }

    // Optional: include base to base conversion as 1.0
    $rates[$base] = 1.0;
}

ksort($rates);

echo json_encode([
    'success' => true,
    'base' => $base,
    'date' => $date,
    'rates' => $rates
]);
