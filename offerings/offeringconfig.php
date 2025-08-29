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
    ]
];

function getOfferings() {
    $sql = "SELECT 
    g.id AS group_id,
    g.name AS group_name,
    g.forrole,
    COALESCE(
        JSON_ARRAYAGG(
            JSON_OBJECT('id', i.id, 'name', i.name)
        ),
        JSON_ARRAY()
    ) AS items
FROM offeringgroup g
LEFT JOIN offeringitem i 
    ON g.id = i.groupod_id
GROUP BY g.id, g.name, g.forrole;
";
  return executeSQL($sql, [], ["JSON" => ["items"]]);

}
