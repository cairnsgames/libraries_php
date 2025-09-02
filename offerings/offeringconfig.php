<?php
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
        "update" => ["offering_id","active"],
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
    ]
];

function getOfferings() {
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
