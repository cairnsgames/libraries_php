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
  http_response_code(400);
  echo json_encode([
    'error' => 'Missing appid parameter',
  ]);
  exit;
}

$ElevenLabsApiKey = getSettingOrSecret($appId, 'eleven-labs-api-key');
if (!$ElevenLabsApiKey) {
  http_response_code(400);
  echo json_encode([
    'error' => 'ElevenLabs API key not found for the provided appid',
    'app_id' => $appId
  ]);
  exit;
}
$apiKey = $ElevenLabsApiKey;

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$voice = isset($input['voice']) ? $input['voice'] : null;
$text = isset($input['text']) ? $input['text'] : null;
$model_id = isset($input['model_id']) ? $input['model_id'] : 'eleven_multilingual_v2';
$voice_settings = isset($input['voice_settings']) ? $input['voice_settings'] : null; // array: stability, similarity_boost, style, use_speaker_boost
$output_format = 'mp3'; // Only mp3 supported for now

if (!$voice || !$text) {
  http_response_code(400);
  echo json_encode([
    'error' => 'Missing required parameters: voice and text'
  ]);
  exit;
}

// ElevenLabs TTS endpoint
$url = "https://api.elevenlabs.io/v1/text-to-speech/$voice";

// Prepare payload
$payload = [
  'text' => $text,
  'model_id' => $model_id,
  'output_format' => $output_format
];
if ($voice_settings && is_array($voice_settings)) {
  $payload['voice_settings'] = $voice_settings;
}

// Initialize cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  "xi-api-key: $apiKey",
  "Accept: audio/mpeg, application/json",
  "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

// Handle response
if ($httpCode === 200 && strpos($contentType, 'audio/mpeg') !== false) {
  // Output as downloadable mp3
  header('Content-Type: audio/mpeg');
  header('Content-Disposition: attachment; filename="tts.mp3"');
  echo $response;
  exit;
} else {
  // Try to decode error message
  $error = @json_decode($response, true);
  http_response_code($httpCode);
  echo json_encode([
    'error' => isset($error['detail']) ? $error['detail'] : 'Failed to generate audio',
    'status' => $httpCode,
    'response' => $response
  ]);
  exit;
}

/*
Example usage (JavaScript fetch):

fetch('http://localhost/cairnsgames/php/openai/11labstts.php?appid=YOUR_APP_ID', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
  voice: 'EXAVITQu4vr4xnSDxMaL', // required, voice id
  text: 'Hello, this is a test.', // required
  model_id: 'eleven_multilingual_v2', // optional
  voice_settings: { stability: 0.5, similarity_boost: 0.5 } // optional
  })
})
.then(response => response.blob())
.then(blob => {
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.style.display = 'none';
  a.href = url;
  a.download = 'tts.mp3';
  document.body.appendChild(a);
  a.click();
  window.URL.revokeObjectURL(url);
});
*/
?>