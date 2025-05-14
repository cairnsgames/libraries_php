<?php
header('Content-Type: application/json');

// Include your executeSQL function and webhook logic
require_once "../gapiv2/dbconn.php"; // Contains executeSQL() function
require_once 'webhook.php'; // Contains triggerWebhook(), generateWebhookGuidKey(), etc.

try {
    // Read JSON input
    $input = json_decode(file_get_contents("php://input"), true);

    // Validate required fields
    if (!isset($input['app_id'], $input['purpose'], $input['payload'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields: app_id, purpose, payload']);
        exit;
    }

    $app_id = $input['app_id'];
    $purpose = $input['purpose'];
    $payload = $input['payload'];

    // Call the webhook
    $result = triggerWebhook($app_id, $purpose, 2, $payload);

    if ($result === false) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Webhook not found']);
    } else {
        echo json_encode([
            'status' => 'success',
            'http_code' => $result['status'],
            'response' => $result['response']
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
