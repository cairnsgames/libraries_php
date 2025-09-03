<?php

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

// Define the configurations
$offeringconfigs = [
    "group" => [
        'tablename' => 'user',
        'key' => 'id',
        'select' => "getOfferings",
        'create' => false,
        'update' => false,
        'delete' => false
    ],
    "item" => [
        'tablename' => 'offeringitem',
        'key' => 'id',
        'select' => ["id", "group_id", "name", "sequence"],
        'create' => ["group_id", "name", "sequence"],
        'update' => ["name", "sequence"],
        'delete' => true
    ],
    "offer" => [
        "tablename" => "user_offerings",
        "key" => "id",
        "select" => ["id", "user_id", "offering_id", "active"],
        "create" => ["user_id", "offering_id"],
        "update" => ["offering_id", "active"],
        "delete" => true
    ],
    "user" => [
        "tablename" => "users",
        "key" => "id",
        "select" => ["id", "username", "email", "role"],
        "create" => false,
        "update" => false,
        "delete" => false,
        "subkeys" => [
            "offerings" => [
                "tablename" => "user_offerings",
                "key" => "user_id",
                "select" => ["id", "user_id", "offering_id", "active"]
            ]
        ]
    ],
    "post" => ["toggle" => "toggleOffering"]
];

function getOfferings()
{
    $sql = "SELECT 
    g.id AS group_id,
    g.name AS group_name,
    g.forrole,
    COALESCE(
        JSON_ARRAYAGG(
            JSON_OBJECT('id', i.id, 'name', i.name, 'sequence', i.sequence)
        ),
        JSON_ARRAY()
    ) AS items
FROM offeringgroup g
LEFT JOIN offeringitem i 
    ON g.id = i.group_id
GROUP BY g.id, g.name, g.forrole;
";
    return executeSQL($sql, [], ["JSON" => ["items"]]);

}

function toggleOffering($data)
{
    global $userid;
    $user_id = isset($data['user_id']) ? (int)$data['user_id'] : (isset($userid) ? (int)$userid : 0);
    $offering_id = isset($data['offering_id']) ? (int)$data['offering_id'] : 0;

    if (!$user_id || !$offering_id) {
        return ['success' => false, 'message' => 'Invalid user_id or offering_id'];
    }

    // Check if the record exists
    $sql = "SELECT id FROM user_offerings WHERE user_id = ? AND offering_id = ?";
    $result = executeSQL($sql, [$user_id, $offering_id]);

    if (!empty($result)) {
        // Record exists, delete it
        $sql = "DELETE FROM user_offerings WHERE user_id = ? AND offering_id = ?";
        executeSQL($sql, [$user_id, $offering_id]);
        return ['success' => true, 'action' => 'deleted', 'record' => null];
    } else {
        // Record does not exist, insert it
        $sql = "INSERT INTO user_offerings (user_id, offering_id, active) VALUES (?, ?, 1)";
        executeSQL($sql, [$user_id, $offering_id]);
        // Fetch the newly inserted record
        $sql = "SELECT id, user_id, offering_id, active FROM user_offerings WHERE user_id = ? AND offering_id = ? ORDER BY id DESC LIMIT 1";
        $newRecord = executeSQL($sql, [$user_id, $offering_id]);
        return [
            'success' => true,
            'action' => 'inserted',
            'record' => !empty($newRecord) ? $newRecord[0] : null
        ];
    }
}
