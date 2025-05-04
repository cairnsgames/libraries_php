<?php
require_once '../dbutils.php';
require_once './exchange_functions.php';

try {
    $access_key = '98d16f4753dc30947ea68aabfeb4a1d8';

    $symbolData = fetchApi("https://api.exchangeratesapi.io/v1/symbols?access_key=$access_key");
    if ($symbolData['success']) {
        storeCurrencies($symbolData['symbols']);
    }

    $rateData = fetchApi("https://api.exchangeratesapi.io/v1/latest?access_key=$access_key");
    if ($rateData['success']) {
        storeExchangeRates($rateData);
    }

    echo "Exchange data synced.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
