<?php
include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../gapiv2/dbconn.php";
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

$system_id = getParam('id',"");
$user_id = getParam('user',"");

if (!hasValue($system_id) || !hasValue($user_id)) {
    sendBadRequestResponse("System ID or User ID missing");
}

// Validate that the $userid matches the venue_id of the system
$query = "SELECT id FROM loyalty_system WHERE id = ? AND venue_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('ii', $system_id, $userid);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    sendUnauthorizedResponse("Unauthorized access");
}
$stmt->close();

// Check if the user already has a card for this system
$query = "SELECT id FROM loyalty_card WHERE user_id = ? AND system_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('ii', $user_id, $system_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // No card exists, create a new card for the user
    $query = "INSERT INTO loyalty_card (app_id, user_id, system_id, qr_code, stamps_collected, date_created, date_modified) 
              VALUES (?, ?, ?, NULL, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('sii', $appId, $user_id, $system_id);
    $stmt->execute();
    $card_id = $stmt->insert_id;
    $stmt->close();
} else {
    // Card exists, get the card ID
    $row = $result->fetch_assoc();
    $card_id = $row['id'];
    $stmt->close();
}

// Add a new stamp to the card
$query = "INSERT INTO loyalty_stamp (app_id, card_id, date_created, date_modified) 
          VALUES (?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('si', $appId, $card_id);
$stmt->execute();
$stmt->close();

// Update the stamp count on the card
$query = "UPDATE loyalty_card SET stamps_collected = stamps_collected + 1, date_modified = CURRENT_TIMESTAMP WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $card_id);
$stmt->execute();
$stmt->close();

checkAndAllocateReward($mysqli, $appId, $user_id, $system_id);

header('Content-Type: application/json');
echo json_encode(["success" => true, "message" => "Stamp added successfully."]);
?>
