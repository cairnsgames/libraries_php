<?php
include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../gapiv2/dbconn.php";
include_once dirname(__FILE__) . "/../gapiv2/v2apicore.php";
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

$userid = getUserId($token);
if (!$userid) {
    sendUnauthorizedResponse("User not found");
}

$query = "SELECT role_id, name FROM user_role, role WHERE user_id = ? and role_id = role.id";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $userid);
$stmt->execute();
$result = $stmt->get_result();

$roles = [];
while ($row = $result->fetch_assoc()) {
    if ($row['role_id'] < 26) {
        continue;
    }
    $roles[] = $row;
}
$stmt->close();

header('Content-Type: application/json');
echo json_encode($roles);
?>
