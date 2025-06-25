<?php

include_once __DIR__ . '/../dbutils.php';
include_once __DIR__ . '/exchange_functions.php';

try {
    $count = updateExchangeCountries();
    echo "✅ Loaded $count countries\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}