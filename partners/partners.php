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
$result = PrepareExecSQL($query, 'i', [$userid]);

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
        $insert_query = "INSERT INTO user_role (user_id, role_id) VALUES (?, ?) on duplicate key update role_id = ?";
        PrepareExecSQL($insert_query, 'iii', [$userid, $role, $role]);
    }
}

// Remove roles not in the new list
foreach ($current_roles as $current_role) {
    if (!in_array($current_role, $new_roles)) {
        $delete_query = "DELETE FROM user_role WHERE user_id = ? AND role_id = ?";
        PrepareExecSQL($delete_query, 'ii', [$userid, $current_role]);
    }
}

// Handle payment method data (if any logic needs to be added here)
// Example:
// $paymentMethod = $data['Payments']['paymentMethod'] ?? '';
// Process payment method if necessary...

sendSuccessResponse("Roles updated successfully.");
?>
