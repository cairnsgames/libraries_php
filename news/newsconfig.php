<?php

function getNews($data) {
    $lat = isset($data['lat']) ? floatval($data['lat']) : null;
    $lng = isset($data['lng']) ? floatval($data['lng']) : null;
    $distance = isset($data['distance']) ? floatval($data['distance']) : 50; // default 50 km

    // If lat/lng are not provided, return all news ordered by date
    if ($lat === null || $lng === null) {
        $sql = "
            SELECT n.*
            FROM news n
            WHERE n.date <= NOW() AND n.expires > NOW()
            ORDER BY n.date DESC
        ";

        // No bound parameters required
        return PrepareExecSQL($sql, "", []);
    }

    // Haversine formula to calculate distance (use positional placeholders)
    $sql = "
        SELECT n.*, 
        ROUND((6371 * acos(
            cos(radians(?)) * cos(radians(n.lat)) * cos(radians(n.lng) - radians(?)) +
            sin(radians(?)) * sin(radians(n.lat))
        ))) AS distance
        FROM news n
        WHERE n.date <= NOW() AND n.expires > NOW()
        HAVING distance <= ?
        ORDER BY distance ASC
    ";

    // PrepareExecSQL in this codebase expects a types string and a numeric params array
    $types = 'dddd';
    $params = [$lat, $lng, $lat, $distance];

    return PrepareExecSQL($sql, $types, $params);
}
// Define the configurations
$newsconfigs = [
    "post" => [
        "localnews" => "getNews"
    ],
    "news" => [
        'tablename' => 'news',
        'key' => 'id',
        'select' => "SELECT 
    n.id AS id,
    n.app_id,
    n.title,
    n.body,
    n.image_url,
    n.overlay_text,
    n.date,
    n.expires,
    n.location, n.lat, n.lng,
    CONCAT(u.firstname, ' ', u.lastname) AS author,
    u.id as user_id,
    up.value AS phone,
    n.created_at,
    n.updated_at,
    n.deleted
FROM 
    news n
JOIN 
    user u ON n.user_id = u.id
LEFT JOIN 
    user_property up ON up.user_id = u.id AND up.name = 'phone'
WHERE 
    n.date <= NOW() AND
    n.expires > NOW()",
        'where' => [],
        'create' => ["title", "body", "image_url", "overlay_text", "date", 'location', 'lat', 'lng', "expires"],
        'update' => ["title", "body", "image_url", "overlay_text", "date", 'location', 'lat', 'lng',"expires","deleted"],
        'delete' => true,
        'beforeselect' => 'addAppId',
        'afterselect' => '',
        'beforecreate' => 'newsbeforecreate',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => 'newsBeforeDelete',
        'subkeys' => []
    ],
    "user" => [
        'tablename' => 'user',
        'key' => 'id',
        'select' => ['id', 'app_id', 'firstname', 'lastname'],
        'create' => false,
        'update' => false,
        'delete' => false,
        'beforeselect' => '',
        'afterselect' => 'modifyrows',
        'beforecreate' => 'addAppId',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => '',
        'subkeys' => [
            'news' => [
                'tablename' => 'news',
                'key' => 'user_id',
                'select' => "SELECT 
    n.id AS id,
    n.app_id,
    n.title,
    n.body,
    n.image_url,
    n.overlay_text,
    n.date,
    n.expires,
    n.location, n.lat, n.lng,
    CONCAT(u.firstname, ' ', u.lastname) AS author,
    u.id as user_id,
    n.created_at,
    n.updated_at,
    n.deleted
FROM 
    news n
JOIN 
    user u ON n.user_id = u.id",
                'beforeselect' => '',
                'afterselect' => ''
            ],
        ]
    ]
];

