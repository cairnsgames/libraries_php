<?php
require_once '../dbutils.php';
require_once './exchange_functions.php';

try {
    $access_key = '98d16f4753dc30947ea68aabfeb4a1d8';

    $count = updateExchangeCurrencies($access_key);
    echo "✅ Loaded $count currencies\n";

    $date = updateExchangeRates($access_key);
    echo "✅ Loaded exchange rates for $date\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}