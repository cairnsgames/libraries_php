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

$query = "SELECT role_id, name FROM user_role, role WHERE user_id = ? AND role_id = role.id";
$params = [$userid];
$result = PrepareExecSQL($query, 'i', $params);

$roles = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['role_id'] < 26) {
            continue;
        }
        $roles[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($roles);
?>
