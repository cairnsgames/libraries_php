<?php

include_once dirname(__FILE__)."/../utils.php";
include_once dirname(__FILE__) . "/../settings/settingsfunctions.php";

/**
 * Make a chat completion request to OpenAI API
 * 
 * @param string $appId The application ID for API key lookup
 * @param string $system The system message for the chat completion
 * @param string $prompt The user prompt for the chat completion
 * @param array $options Optional parameters (model, temperature, max_tokens)
 * @return array Response array with either 'response' or 'error' key
 */
function openai_chat_completion($appId, $system, $prompt, $options = []) {
    // Validate required parameters
    if (!$appId) {
        return [
            'error' => 'Missing appid parameter'
        ];
    }

    if (!$system || !$prompt) {
        return [
            'error' => 'Missing required parameters',
            'call' => [
                'system' => $system,
                'prompt' => $prompt
            ]
        ];
    }

    // Get API key from settings
    $OpenAIApiKey = getSettingOrSecret($appId, 'open-ai-api-key');
    if (!$OpenAIApiKey) {
        return [
            'error' => 'OpenAI API key not found for the provided appid',
            'app_id' => $appId
        ];
    }

    // Get model and other settings
    $OpenAIModel = getSettingOrSecret($appId, 'open-ai-model', 'gpt-4o-mini');
    
    // Set default options
    $temperature = $options['temperature'] ?? 0.7;
    $max_tokens = $options['max_tokens'] ?? 2000;
    $model = $options['model'] ?? $OpenAIModel;

    try {
        // Initialize OpenAI API
        $url = "https://api.openai.com/v1/chat/completions";
        $headers = [
            "Authorization: Bearer $OpenAIApiKey",
            "Content-Type: application/json"
        ];

        $apiCall = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $temperature,
            'max_tokens' => $max_tokens
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
            return [
                'error' => "OpenAI API Error: $error",
                'call' => $apiCall
            ];
        }

        curl_close($ch);

        $responseData = json_decode($response, true);

        if (!isset($responseData['choices'][0]['message'])) {
            return [
                'error' => 'No response from OpenAI',
                'call' => $apiCall
            ];
        }

        return [
            'response' => $responseData['choices'][0]['message']
        ];

    } catch (Exception $e) {
        return [
            'error' => $e->getMessage() ?: 'An unexpected error occurred',
            'details' => $e->getTraceAsString()
        ];
    }
}
