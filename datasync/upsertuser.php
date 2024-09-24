<?php
header('Content-Type: application/json');
include_once "../dbutils.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Extract the necessary data from the input
    $appId = $data['app_id'] ?? 'b0181e17-e5c6-11ee-bb99-1a220d8ac2c9';  // Default to '0' if not provided
    $firstName = $data['firstname'] ?? null;
    $lastName = $data['lastname'] ?? null;
    $avatar = $data['avatar'] ?? null;
    $email = $data['email'] ?? null;
    $roleId = $data['role_id'] ?? null;
    $active = $data['active'] ?? 1;  // Default active state to 1
    $password = $data['password'] ?? null;  // Assume password is already hashed
    $oldUserId = $data['id'] ?? null; // Old user ID for mapping
    $profileId = $data['profile_id'] ?? null;

    if (!$email) {
        echo json_encode(['error' => 'Email is required']);
        exit;
    }

    // Check if a user with the given email already exists
    $sqlCheckEmail = "SELECT id FROM user WHERE email = ? and app_id = ?";
    $result = PrepareExecSQL($sqlCheckEmail, 'ss', [$email, $appId]);

    if (!empty($result)) {
        // User exists, proceed to update
        $userId = $result[0]['id'];

        // Update the user record in the user table
        $sqlUpdateUser = "
            UPDATE user 
            SET 
                app_id = ?, 
                firstname = ?, 
                lastname = ?, 
                avatar = ?, 
                role_id = ?, 
                active = ?, 
                password = ?
            WHERE id = ?
        ";
        PrepareExecSQL($sqlUpdateUser, 'sssssisi', [$appId, $firstName, $lastName, $avatar, $roleId, $active, $password, $userId]);

        // Update the user mapping table (insert if not exists)
        if ($oldUserId) {
            $sqlCheckMapping = "SELECT id FROM user_mapping WHERE app_id = ? AND old_user_id = ?";
            $mappingResult = PrepareExecSQL($sqlCheckMapping, 'ss', [$appId, $oldUserId]);

            if (!empty($mappingResult)) {
                // Update existing mapping
                $sqlUpdateMapping = "
                    UPDATE user_mapping 
                    SET new_user_id = ? 
                    WHERE app_id = ? AND old_user_id = ?
                ";
                PrepareExecSQL($sqlUpdateMapping, 'sss', [$userId, $appId, $oldUserId]);
            } else {
                // Insert new mapping
                $sqlInsertMapping = "
                    INSERT INTO user_mapping (app_id, old_user_id, old_profile_id, new_user_id) 
                    VALUES (?, ?, ?, ?)
                ";
                PrepareExecSQL($sqlInsertMapping, 'ssss', [$appId, $oldUserId, $profileId, $userId]);
            }
        }

        // Return JSON response indicating the user was updated
        echo json_encode(['message' => 'User updated', 'user_id' => $userId]);
    } else {
        // User does not exist, proceed to create a new user
        // Insert new user into the user table
        $sqlInsertUser = "
            INSERT INTO user 
                (app_id, firstname, lastname, avatar, email, role_id, active, password, created) 
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";
        $userId = PrepareExecSQL($sqlInsertUser, 'ssssssis', [$appId, $firstName, $lastName, $avatar, $email, $roleId, $active, $password]);

        // Insert into the user_mapping table
        if ($oldUserId) {
            $sqlInsertMapping = "
                INSERT INTO user_mapping (app_id, old_user_id, old_profile_id, new_user_id) 
                VALUES (?, ?, ?, ?)
            ";
            PrepareExecSQL($sqlInsertMapping, 'ssss', [$appId, $oldUserId, $profileId, $userId]);
        }

        // Return JSON response indicating the user was created
        echo json_encode(['message' => 'New user created', 'user_id' => $userId]);
    }
}
?>
