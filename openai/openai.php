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

$OpenAIApiKey = getSettingOrSecret($appId, 'open-ai-api-key');
if (!$OpenAIApiKey) {
    http_response_code(200);
    echo json_encode([
        'error' => 'OpenAI API key not found for the provided appid',
        'app_id' => $appId
    ]);
    exit;
}
$OpenAIModel = getSettingOrSecret($appId, 'open-ai-model', 'gpt-4o-mini');

try {
    // Read the JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $system = $input['system'] ?? null;
    $prompt = $input['prompt'] ?? null;

    if (!$system || !$prompt) {
        http_response_code(200);
        echo json_encode([
            'error' => 'Missing required parameters',
            'call' => [
                'system' => $system,
                'prompt' => $prompt
            ]
        ]);
        exit;
    }

    // Initialize OpenAI API
    $url = "https://api.openai.com/v1/chat/completions";
    $headers = [
        "Authorization: Bearer $OpenAIApiKey",
        "Content-Type: application/json"
    ];

    $apiCall = [
        'model' => $OpenAIModel,
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.7,
        'max_tokens' => 2000
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiCall));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        http_response_code(200);
        echo json_encode([
            'error' => "OpenAI API Error: $error",
            'call' => $apiCall
        ]);
        exit;
    }

    curl_close($ch);

    $responseData = json_decode($response, true);

    if (!isset($responseData['choices'][0]['message'])) {
        http_response_code(200);
        echo json_encode([
            'error' => 'No response from OpenAI',
            'call' => $apiCall
        ]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'response' => $responseData['choices'][0]['message']
    ]);
} catch (Exception $e) {
    http_response_code(200);
    echo json_encode([
        'error' => $e->getMessage() ?: 'An unexpected error occurred',
        'details' => $e->getTraceAsString()
    ]);
}