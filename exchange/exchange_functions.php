<?php
require_once '../dbutils.php';

function fetchApi($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("Failed to fetch from: $url. CURL error: $error");
    }
    curl_close($ch);
    return json_decode($response, true);
}

function fetchApiWithFileGetContents($url) {
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

    return ["date" => $date, "result" => $result];
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

function fetchIndependentCountries() {
    $url = "https://restcountries.com/v3.1/independent?status=true&fields=currencies,name,cca3,cca2";
    $response = file_get_contents($url);
    if ($response === FALSE) {
        throw new Exception("Failed to fetch country data");
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        throw new Exception("Invalid API response format");
    }

    return $data;
}

function storeCountries($countries) {
    global $mysqli;
    $stmt = $mysqli->prepare("
        REPLACE INTO exchange_countries 
        (cca2, cca3, name_common, currency_name, currency_code, created_at, modified_at) 
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ");

    foreach ($countries as $country) {
        $cca2 = $country['cca2'] ?? null;
        $cca3 = $country['cca3'] ?? null;
        $nameCommon = $country['name']['common'] ?? null;

        // Extract first currency code
        $currencyKeys = array_keys($country['currencies'] ?? []);
        $currencyCode = $currencyKeys[0] ?? null;
        $currencyName = $country['currencies'][$currencyCode]['name'] ?? null;

        if ($cca2 && $cca3 && $nameCommon && $currencyCode) {
            $stmt->bind_param("sssss", $cca2, $cca3, $nameCommon, $currencyName, $currencyCode);
            $stmt->execute();
        }
    }

    $stmt->close();
}

function updateExchangeCountries() {
    $countries = fetchIndependentCountries();
    storeCountries($countries);
    return count($countries);
}

function getExchangeRateByCountry() {
    global $mysqli;
    $stmt = $mysqli->prepare("
        SELECT c.cca2, c.cca3, c.name_common, c.currency_name, c.currency_code, r.rate, r.base_currency
        FROM exchange_countries c
        LEFT JOIN exchange_rates r ON c.currency_code = r.target_currency
        WHERE r.date = (SELECT MAX(date) FROM exchange_rates)
        order by name_common
    ");
    $stmt->execute();
    $stmt->bind_result($cca2, $cca3, $name_common, $currency_name, $currency_code, $rate, $base_currency);

    $countries = [];
    while ($stmt->fetch()) {
        $countries[] = [
            'cca2' => $cca2,
            'cca3' => $cca3,
            'name_common' => $name_common,            
            'currency_code' => $currency_code,
            'currency_name' => $currency_name,
            'rate' => $rate
        ];
    }

    $stmt->close();
    return $countries;
}

?>
