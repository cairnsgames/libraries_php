<?php
// Include or define executeSQL here
require_once "../gapiv2/dbconn.php";

// Read raw JSON input
$input = file_get_contents("php://input");

// Store it in the database
try {
    executeSQL(
        "INSERT INTO webhook_test_result (payload) VALUES (?)",
        [$input]
    );

    http_response_code(200);
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
