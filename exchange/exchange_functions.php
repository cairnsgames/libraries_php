<?php
require_once '../dbutils.php';

function fetchApi($url) {
    $response = file_get_contents($url);
    if (!$response) {
        throw new Exception("Failed to fetch from: $url");
    }
    return json_decode($response, true);
}

function storeCurrencies($symbols) {
    global $mysqli;
    $stmt = $mysqli->prepare("REPLACE INTO exchange_currencies (code, name) VALUES (?, ?)");
    foreach ($symbols as $code => $name) {
        $stmt->bind_param("ss", $code, $name);
        $stmt->execute();
    }
    $stmt->close();
}

function storeExchangeRates($data) {
    global $mysqli;
    $date = $data['date'];
    $base = $data['base'];
    $rates = $data['rates'];

    $stmt = $mysqli->prepare("REPLACE INTO exchange_rates (date, base_currency, target_currency, rate) VALUES (?, ?, ?, ?)");
    foreach ($rates as $code => $rate) {
        $stmt->bind_param("sssd", $date, $base, $code, $rate);
        $stmt->execute();
    }
    $stmt->close();
}

function convertCurrency($from, $to, $amount, $date = null) {
    global $mysqli;
    if (!$date) {
        $date = date('Y-m-d');
    }
    if (!$date) {
        $stmt = $mysqli->prepare("SELECT MAX(date) FROM exchange_rates");
        $stmt->execute();
        $stmt->bind_result($date);
        $stmt->fetch();
        $stmt->close();
    }

    // Get EUR -> FROM rate
    $stmt1 = $mysqli->prepare("SELECT rate FROM exchange_rates WHERE date = ? AND base_currency = 'EUR' AND target_currency = ?");
    $stmt1->bind_param("ss", $date, $from);
    $stmt1->execute();
    $stmt1->bind_result($from_rate);
    $stmt1->fetch();
    $stmt1->close();

    // Get EUR -> TO rate
    $stmt2 = $mysqli->prepare("SELECT rate FROM exchange_rates WHERE date = ? AND base_currency = 'EUR' AND target_currency = ?");
    $stmt2->bind_param("ss", $date, $to);
    $stmt2->execute();
    $stmt2->bind_result($to_rate);
    $stmt2->fetch();
    $stmt2->close();

    if (!$from_rate || !$to_rate) {
        throw new Exception("Rates not found for conversion on $date");
    }

    // Convert FROM -> EUR -> TO
    $eur_amount = $amount / $from_rate;
    $result = $eur_amount * $to_rate;

    // Log conversion
    $stmt3 = $mysqli->prepare("INSERT INTO exchange_calcs (from_currency, to_currency, amount, result) VALUES (?, ?, ?, ?)");
    $stmt3->bind_param("ssdd", $from, $to, $amount, $result);
    $stmt3->execute();
    $stmt3->close();

    return $result;
}

function updateExchangeCurrencies($access_key) {
    $symbols = fetchApi("https://api.exchangeratesapi.io/v1/symbols?access_key=$access_key");
    if (!$symbols || !$symbols['success']) {
        throw new Exception("Failed to fetch symbols");
    }
    storeCurrencies($symbols['symbols']);
    return count($symbols['symbols']);
}

function updateExchangeRates($access_key) {
    $rates = fetchApi("https://api.exchangeratesapi.io/v1/latest?access_key=$access_key");
    if (!$rates || !$rates['success']) {
        throw new Exception("Failed to fetch exchange rates");
    }
    storeExchangeRates($rates);
    return $rates['date'];
}

?>
