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

$system_id = getParam('id', "");
$user_id = getParam('user', "");

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

// Find the earliest unredeemed reward for the user in the specified system
$query = "SELECT id FROM loyalty_reward WHERE user_id = ? AND system_id = ? AND date_redeemed IS NULL ORDER BY date_earned ASC LIMIT 1";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('ii', $user_id, $system_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    sendBadRequestResponse("No unredeemed rewards available for this user.");
} else {
    // Mark the earliest reward as redeemed
    $row = $result->fetch_assoc();
    $reward_id = $row['id'];
    $stmt->close();

    $query = "UPDATE loyalty_reward SET date_redeemed = CURRENT_TIMESTAMP, date_modified = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $reward_id);
    $stmt->execute();
    $stmt->close();

    header('Content-Type: application/json');
    echo json_encode(["success" => true, "message" => "Reward redeemed successfully."]);
}
?>