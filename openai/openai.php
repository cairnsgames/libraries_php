<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: authorization, x-client-info, apikey, content-type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo 'ok';
    exit;
}

include_once dirname(__FILE__)."/../utils.php";
include_once dirname(__FILE__) . "/openai_functions.php";

$appId = getAppId();

// Read the JSON input
$input = json_decode(file_get_contents('php://input'), true);
$system = $input['system'] ?? null;
$prompt = $input['prompt'] ?? null;

// Optional parameters
$options = [];
if (isset($input['temperature'])) {
    $options['temperature'] = $input['temperature'];
}
if (isset($input['max_tokens'])) {
    $options['max_tokens'] = $input['max_tokens'];
}
if (isset($input['model'])) {
    $options['model'] = $input['model'];
}

// Call the OpenAI function
$result = openai_chat_completion($appId, $system, $prompt, $options);

// Return the result
http_response_code(200);
echo json_encode($result);