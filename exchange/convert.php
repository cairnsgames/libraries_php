<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../corsheaders.php';
require_once '../dbutils.php';
require_once './exchange_functions.php';

header('Content-Type: application/json');

$from   = isset($_GET['from']) ? strtoupper(trim($_GET['from'])) : null;
$to     = isset($_GET['to']) ? strtoupper(trim($_GET['to'])) : null;
$amount = isset($_GET['amount']) ? floatval($_GET['amount']) : null;
$date   = isset($_GET['date']) ? trim($_GET['date']) : null;

if (!$from || !$to || !$amount) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters: from, to, amount']);
    exit;
}

try {
    $result = convertCurrency($from, $to, $amount, $date);
    echo json_encode([
        'success' => true,
        'from' => $from,
        'to' => $to,
        'amount' => $amount,
        'converted' => round($result["result"], 6),
        'date' => $date ?: $result["date"]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
