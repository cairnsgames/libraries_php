<?php
require_once '../corsheaders.php';
require_once '../dbutils.php';
include_once dirname(__FILE__) . "/../utils.php";
include_once dirname(__FILE__) . "/../auth/authfunctions.php";

$appId = getAppId();
$token = getToken();

if (!hasValue($token)) {
    sendUnauthorizedResponse("Invalid token");
}
if (!hasValue($appId)) {
    sendUnauthorizedResponse("Invalid tenant");
}

// Admin use includes a user_id field - for personal role updates never send the user_id.
// TODO: Add validation check that token is an admin

$userid = getParam("user_id");
if (!$userid) {
    $userid = getUserId($token);
}
if (!$userid) {
    sendUnauthorizedResponse("User not found");
}


header('Content-Type: application/json');

// Get event_id from request
$event_id = getParam('event_id', '');
if (!hasValue($event_id)) {
    echo json_encode(["success" => false, "error" => "Missing event_id"]);
    exit;
}

// Check if favorite exists
$sql_check = "SELECT id FROM user_favorites WHERE user_id = ? AND event_id = ?";
$result = PrepareExecSQL($sql_check, 'ii', [$userid, $event_id]);

if (count($result) > 0) {
    // Exists, delete
    $sql_delete = "DELETE FROM user_favorites WHERE user_id = ? AND event_id = ?";
    PrepareExecSQL($sql_delete, 'ii', [$userid, $event_id]);
    echo json_encode(["success" => true, "action" => "deleted"]);
} else {
    // Not exists, insert
    $sql_insert = "INSERT INTO user_favorites (user_id, event_id, created_at) VALUES (?, ?, NOW())";
    $insert_id = PrepareExecSQL($sql_insert, 'ii', [$userid, $event_id]);
    // Fetch the inserted record
    $sql_get = "SELECT * FROM user_favorites WHERE id = ?";
    $record = PrepareExecSQL($sql_get, 'i', [$insert_id]);
    echo json_encode(["success" => true, "action" => "added", "record" => $record ? $record[0] : null]);
}





