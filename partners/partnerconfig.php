<?php
function getLocalPartners($data) {
    $lat = isset($data['lat']) ? floatval($data['lat']) : null;
    $lng = isset($data['lng']) ? floatval($data['lng']) : null;
    $distance = isset($data['distance']) ? floatval($data['distance']) : 50; // default 50 km

    // If lat/lng are not provided return empty array as requested
    if ($lat === null || $lng === null) {
        return [];
    }

    $appId = getAppId();

    $sql = "
SELECT *
FROM (
    SELECT
        u.id AS user_id,
        u.username,
        u.firstname,
        u.lastname,
        u.email,
        u.avatar,

        l.id AS location_id,
        l.name AS location_name,
        l.lat,
        l.lng,

        ROUND((
            6371 * ACOS(
                COS(RADIANS(?)) * COS(RADIANS(l.lat)) * COS(RADIANS(l.lng) - RADIANS(?)) +
                SIN(RADIANS(?)) * SIN(RADIANS(l.lat))
            )
        )) AS distance,

        ROW_NUMBER() OVER (
            PARTITION BY u.id
            ORDER BY
                (
                    6371 * ACOS(
                        COS(RADIANS(?)) * COS(RADIANS(l.lat)) * COS(RADIANS(l.lng) - RADIANS(?)) +
                        SIN(RADIANS(?)) * SIN(RADIANS(l.lat))
                    )
                )
        ) AS rn,

        (
            SELECT JSON_ARRAYAGG(
                JSON_OBJECT('id', r.id, 'name', r.name)
            )
            FROM user_role ur2
            JOIN role r
              ON ur2.role_id = r.id
             AND r.app_id = ?
            WHERE ur2.user_id = u.id
        ) AS roles,

        COALESCE(
            (
                SELECT JSON_ARRAYAGG(
                    JSON_OBJECT('id', oi.id, 'name', oi.name)
                    ORDER BY COALESCE(oi.sequence, 0), oi.id
                )
                FROM user_offerings uo
                JOIN offeringitem oi ON oi.id = uo.offering_id
                WHERE uo.user_id = u.id
                  AND uo.active = 1
            ),
            JSON_ARRAY()
        ) AS offerings

    FROM user u
    JOIN kloko_user_location ul ON u.id = ul.user_id
    JOIN kloko_location l ON ul.location_id = l.id

    WHERE u.app_id = ?
      AND EXISTS (
          SELECT 1
          FROM user_role urx
          JOIN role rx
            ON urx.role_id = rx.id
           AND rx.app_id = ?
          WHERE urx.user_id = u.id
      )
) ranked
WHERE rn = 1
  AND distance <= ?
ORDER BY distance ASC;
    ";

    $params = [
        $lat, $lng, $lat,   // distance SELECT
        $lat, $lng, $lat,   // distance ORDER BY (ROW_NUMBER)
        $appId,             // roles subquery
        $appId,             // u.app_id
        $appId,             // rx.app_id
        $distance           // max distance
    ];
    $types = 'ddddddsssd';

    $result = PrepareExecSQL($sql, $types, $params);

    // If the query failed or returned non-array, return empty array
    if (!is_array($result)) {
        return [];
    }

    // Decode the roles JSON for each row. If null -> [], on any JSON error -> return []
    foreach ($result as $i => $row) {
        if (!isset($row['roles']) || $row['roles'] === null) {
            $result[$i]['roles'] = [];
            continue;
        }

        $rolesJson = $row['roles'];
        if (is_resource($rolesJson) || is_object($rolesJson)) {
            $rolesJson = (string)$rolesJson;
        }

        $decoded = json_decode($rolesJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        if ($decoded === null) {
            $decoded = [];
        }

        $result[$i]['roles'] = $decoded;
    }

    // Decode offerings JSON similarly
    foreach ($result as $i => $row) {
        if (!isset($row['offerings']) || $row['offerings'] === null) {
            $result[$i]['offerings'] = [];
            continue;
        }

        $offJson = $row['offerings'];
        if (is_resource($offJson) || is_object($offJson)) {
            $offJson = (string)$offJson;
        }

        $decodedOff = json_decode($offJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        if ($decodedOff === null) {
            $decodedOff = [];
        }

        $result[$i]['offerings'] = $decodedOff;
    }

    return $result;
}
// Define the configurations
$partnerconfigs = [
    "post" => [
        "localpartners" => "getLocalPartners"
    ],
    "partner" => [
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
            'payment' => [
                'tablename' => 'partner_banking',
                'key' => 'partner_id',
                'select' => ['id', 'bank_name','account_number','branch_code','payment_method','paypal_username'],
                'beforeselect' => '',
                'afterselect' => ''
            ],
            'banking' => [
                'tablename' => 'partner_banking',
                'key' => 'partner_id',
                'select' => ['id', 'partner_id', 'bank_name','account_number','branch_code','payment_method','paypal_username'],
                'beforeselect' => '',
                'afterselect' => ''
            ],
            'events' => [
                'tablename' => 'kloko_event',
                'key' => 'user_id',
                'select' => ['id', 'user_id', 'title', 'description', 'image', 'event_type', 'keywords', 'duration', 'start_time', 'end_time', 'location', 'lat', 'lng', 'max_participants', 'currency', 'price', 'show_as_news', 'enable_bookings', 'overlay_text'],
                'beforeselect' => '',
                'afterselect' => ''
            ],
        ]
    ],
    "banking" => [
        'tablename' => 'partner_banking',
        'key' => 'partner_id',
        'select' => false,
        'create' => ['partner_id', 'bank_name','account_number','branch_code','payment_method','paypal_username'],
        'update' => ['partner_id', 'bank_name','account_number','branch_code','payment_method','paypal_username'],
        'delete' => false,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => 'setPartnerId',
        'aftercreate' => '',
        'beforeupdate' => 'setPartnerId',
        'afterupdate' => '',
        'beforedelete' => '',
    ],
];
