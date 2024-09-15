<?php
include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../gapiv2/dbconn.php";
include_once dirname(__FILE__) . "/../gapiv2/v2apicore.php";
include_once dirname(__FILE__) . "/../utils.php";
include_once dirname(__FILE__) . "/../auth/authfunctions.php";
include_once dirname(__FILE__) . "/../breezo/breezo.php";

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

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['role'])) {
    sendErrorResponse("Invalid input data");
}

$roles = $data['role'];
$valid_roles = [26, 27, 28, 29, 30]; // Valid role IDs
$current_roles = [];

// Fetch current user roles
$query = "SELECT role_id FROM user_role WHERE user_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $userid);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $current_roles[] = $row['role_id'];
}
$stmt->close();

// Extract role IDs from input data
$new_roles = [];
foreach ($roles as $role) {
    if (isset($role['role_id']) && in_array($role['role_id'], $valid_roles)) {
        $new_roles[] = $role['role_id'];
    }
}

// Add or remove roles
foreach ($new_roles as $role) {
    if (!in_array($role, $current_roles)) {
        // Add new role
        $insert_query = "INSERT INTO user_role (user_id, role_id) VALUES (?, ?)";
        $insert_stmt = $mysqli->prepare($insert_query);
        $insert_stmt->bind_param('ii', $userid, $role);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
}

// Remove roles not in the new list
foreach ($current_roles as $current_role) {
    if (!in_array($current_role, $new_roles)) {
        $delete_query = "DELETE FROM user_role WHERE user_id = ? AND role_id = ?";
        $delete_stmt = $mysqli->prepare($delete_query);
        $delete_stmt->bind_param('ii', $userid, $current_role);
        $delete_stmt->execute();
        $delete_stmt->close();
    }
}

// Handle payment method data (if any logic needs to be added here)
// Example:
// $paymentMethod = $data['Payments']['paymentMethod'] ?? '';
// Process payment method if necessary...

sendSuccessResponse("Roles updated successfully.");
?>
