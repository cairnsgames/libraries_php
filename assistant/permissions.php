<?php
include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../gapiv2/dbconn.php";
include_once dirname(__FILE__) . "/../gapiv2/v2apicore.php";
include_once dirname(__FILE__) . "/../utils.php";
include_once dirname(__FILE__) . "/../auth/authfunctions.php";

// Get appId and token
$appId = getAppId();
$token = getToken();

// Check for token validity
if (!hasValue($token)) {
    sendUnauthorizedResponse("Invalid token");
}
if (!hasValue($appId)) {
    sendUnauthorizedResponse("Invalid tenant");
}

// Get user ID from token
$userid = getUserId($token);
if (!$userid) {
    sendUnauthorizedResponse("User not found");
}

// Get venue ID from request parameter
$venue_id = getParam('venue', "");

if (!hasValue($venue_id)) {
    sendBadRequestResponse("Venue ID missing");
}

// Check if the user has roles at the venue
$query = "SELECT ar.id AS role_id 
          FROM assistant_user_role aur
          JOIN assistant_role ar ON aur.role_id = ar.id
          WHERE aur.user_id = ? AND aur.venue_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('ii', $userid, $venue_id);
$stmt->execute();
$result = $stmt->get_result();

// Initialize permissions array
$permissions = [];

// Fetch the roles and get corresponding permissions
while ($row = $result->fetch_assoc()) {
    $role_id = $row['role_id'];

    // Fetch permissions for each role
    $permissionQuery = "SELECT ap.id, ap.name as permission_name, ap.description, least(ap.value, arp.value) as value
                        FROM assistant_role_permission arp
                        JOIN assistant_permission ap ON arp.permission_id = ap.id
                        WHERE arp.role_id = ?
                        having value > 0";
    $permissionStmt = $mysqli->prepare($permissionQuery);
    $permissionStmt->bind_param('i', $role_id);
    $permissionStmt->execute();
    $permissionResult = $permissionStmt->get_result();

    // Add permissions to the array
    while ($permissionRow = $permissionResult->fetch_assoc()) {
        if (!in_array($permissionRow['permission_name'], $permissions)) {
            $permissions[] = $permissionRow;
        }
    }

    $permissionStmt->close();
}

$stmt->close();

// Return permissions as a JSON response
header('Content-Type: application/json');
echo json_encode($permissions);
?>
