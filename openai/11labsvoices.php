<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: authorization, x-client-info, apikey, content-type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo 'ok';
    exit;
}

include_once dirname(__FILE__)."/../utils.php";
include_once dirname(__FILE__) . "/../settings/settingsfunctions.php";

$appId = getAppId();

if (!$appId) {
    http_response_code(200);
    echo json_encode([
        'error' => 'Missing appid parameter',
    ]);
    exit;
}

$ElevenLabsApiKey = getSettingOrSecret($appId, 'eleven-labs-api-key');
if (!$ElevenLabsApiKey) {
    http_response_code(200);
    echo json_encode([
        'error' => 'ElevenLabs API key not found for the provided appid',
        'app_id' => $appId
    ]);
    exit;
}
$apiKey = $ElevenLabsApiKey;

// ElevenLabs voices endpoint
$url = 'https://api.elevenlabs.io/v1/voices';

// Initialize cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  "xi-api-key: $apiKey",
  "Accept: application/json"
]);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check for errors
if ($httpCode !== 200) {
  http_response_code($httpCode);
  echo "Error fetching voices: HTTP $httpCode";
  exit;
}

// Decode and output the voices
$data = json_decode($response, true);
if (isset($data['voices'])) {
  header('Content-Type: application/json');
  echo json_encode($data['voices'], JSON_PRETTY_PRINT);
} else {
  echo "No voices found.";
}
?>