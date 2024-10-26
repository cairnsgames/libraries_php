<?php
include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../gapiv2/dbconn.php";
include_once dirname(__FILE__) . "/../gapiv2/dbutils.php";
include_once dirname(__FILE__) . "/../gapiv2/v2apicore.php";
include_once dirname(__FILE__) . "/../utils.php";
include_once dirname(__FILE__) . "/../auth/authfunctions.php";
include_once dirname(__FILE__) . "/loyaltyfunctions.php";

$appId = getAppId();
$token = getToken();

if (!hasValue($token)) {
    sendUnauthorizedResponse("Invalid token");
}
if (!hasValue($appId)) {
    sendUnauthorizedResponse("Invalid tenant");
}

$userid = getUserId($token);
if (!$userid) {
    sendUnauthorizedResponse("User not found");
}

$system_id = getParam('id', "");
$user_id = getParam('user', "");
$lat = getParam('lat', "");
$lng = getParam('lng', "");

if (!hasValue($system_id) || !hasValue($user_id)) {
    sendBadRequestResponse("System ID or User ID missing");
}

// Validate that the $userid matches the venue_id of the system
$query = "SELECT id FROM loyalty_system WHERE id = ? AND venue_id = ?";
$result = PrepareExecSQL($query, 'ii', [$system_id, $userid]);

if ($result->num_rows === 0) {
    sendUnauthorizedResponse("Unauthorized access");
}

$query = "SELECT id FROM loyalty_card WHERE user_id = ? AND system_id = ?";
$result = PrepareExecSQL($query, 'ii', [$user_id, $system_id]);

if (count($result) === 0) {
    $query = "INSERT INTO loyalty_card (app_id, user_id, system_id, qr_code, stamps_collected) 
              VALUES (?, ?, ?, NULL, 0)";
    $id = PrepareExecSQL($query, 'sii', [$appId, $user_id, $system_id]);
    $card_id = $id;
} else {
    $card_id = $result[0]['id'];
}

// Add a new stamp to the card
$query = "INSERT INTO loyalty_stamp (app_id, card_id, lat, lng, stamped_by) 
          VALUES (?, ?, ?, ?, ?)";
PrepareExecSQL($query, 'si', [$appId, $card_id, $lat, $lng, $userid]);

// Update the stamp count on the card
$query = "UPDATE loyalty_card SET stamps_collected = stamps_collected + 1, date_modified = CURRENT_TIMESTAMP WHERE id = ?";
PrepareExecSQL($query, 'i', [$card_id]);

checkAndAllocateReward($mysqli, $appId, $user_id, $system_id);

header('Content-Type: application/json');
echo json_encode(["success" => true, "message" => "Stamp added successfully."]);
?>