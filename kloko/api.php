<?php

include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../gapiv2/dbconn.php";
include_once dirname(__FILE__) . "/../gapiv2/v2apicore.php";
include_once dirname(__FILE__) . "/../utils.php";
include_once dirname(__FILE__) . "/../auth/authfunctions.php";

/* Find
Lat, Lng (center of search), type (event_type), distance (radius of search), userid current user
*/


$appId = getAppId();
$token = getToken();

if ($appId == "undefined" || $token == "undefined") {
    http_response_code(401);
    return json_encode(["error" => "Invalid token"]);
}

$userid = getUserId($token);

// Define the configurations
$configs = [
    "calendar" => [
        'tablename' => 'kloko_calendar',
        'key' => 'id',
        'select' => ['id', 'user_id', 'app_id', 'title', 'description'],
        'create' => ['user_id', 'app_id', 'title', 'description'],
        'update' => ['title', 'description'],
        'delete' => true,
        'where' => ["app_id" => $appId],
        'beforeselect' => 'checksecurity',
        'afterselect' => 'modifyrows',
        'beforecreate' => 'checksecurity',
        'aftercreate' => '',
        'beforeupdate' => 'checksecurity',
        'afterupdate' => '',
        'beforedelete' => 'checksecurity',
        'subkeys' => [
            'events' => [
                'tablename' => 'kloko_event',
                'key' => 'calendar_id',
                'select' => ['id', 'calendar_id', 'user_id', 'event_template_id', 'content_id', 'app_id', 'title', 'description', 'image', 'event_type', 'duration', 'location', 'lat', 'lng', 'max_participants', 'start_time', 'end_time'],
                'beforeselect' => 'checksecurity',
                'afterselect' => 'formatEventDates'
            ]
        ]
    ],
    "event" => [
        'tablename' => 'kloko_event',
        'key' => 'id',
        'select' => ['id', 'calendar_id', 'user_id', 'event_template_id', 'content_id', 'app_id', 'title', 'description', 'image', 'event_type', 'duration', 'location', 'lat', 'lng', 'max_participants', 'start_time', 'end_time'],
        'create' => ['calendar_id', 'user_id', 'event_template_id', 'content_id', 'app_id', 'title', 'description', 'image', 'event_type', 'duration', 'location', 'lat', 'lng', 'max_participants', 'start_time', 'end_time'],
        'update' => ['title', 'description', 'image', 'content_id', 'event_type', 'duration', 'location', 'lat', 'lng', 'max_participants', 'start_time', 'end_time'],
        'delete' => true,
        'beforeselect' => 'checksecurity',
        'afterselect' => '',
        'beforecreate' => 'beforecreate',
        'aftercreate' => '',
        'beforeupdate' => 'checksecurity',
        'afterupdate' => '',
        'beforedelete' => 'checksecurity',
        'subkeys' => [
            'bookings' => [
                'tablename' => 'kloko_booking',
                'key' => 'event_id',
                'select' => ['id', 'event_id', 'user_id', 'participant_email', 'booking_time', 'status'],
                'beforeselect' => 'checksecurity',
                'afterselect' => ''
            ]
        ]
    ],
    "user" => [
        'tablename' => 'user',
        'key' => 'id',
        'select' => ['id', "firstname", "lastname", "avatar", "email"],
        'beforeselect' => 'checksecurity',
        'afterselect' => '',
        'subkeys' => [
            'bookings' => [
                'tablename' => 'kloko_booking',
                'key' => 'user_id',
                'select' => ['id', 'event_id', 'user_id', 'participant_email', 'booking_time', 'status'],
                'beforeselect' => 'checksecurity',
                'afterselect' => ''
            ],
            "calendars" => [
                'tablename' => 'kloko_calendar',
                'key' => 'user_id',
                'select' => ['id', 'user_id', 'app_id', 'title', 'description']
            ],
            "templates" => [
                'tablename' => 'kloko_event_template',
                'key' => 'user_id',
                'select' => ['id', 'user_id', 'title', 'description', 'image', 'content_id', 'duration', 'location', 'lat', 'lng', 'max_participants', 'price', 'event_type'],
            ]
        ]
    ],
    "booking" => [
        'tablename' => 'kloko_booking',
        'key' => 'id',
        'select' => ['id', 'event_id', 'user_id', 'participant_email', 'booking_time', 'status'],
        'create' => ['event_id', 'user_id', 'participant_email', 'booking_time', 'status'],
        'update' => ['status'],
        'delete' => true,
        'beforeselect' => 'checksecurity',
        'afterselect' => '',
        'beforecreate' => 'checksecurity',
        'aftercreate' => '',
        'beforeupdate' => 'checksecurity',
        'afterupdate' => '',
        'beforedelete' => 'checksecurity',
        'subkeys' => [
            'notifications' => [
                'tablename' => 'kloko_notification',
                'key' => 'id',
                'select' => ['id', 'booking_id', 'user_id', 'notification_time', 'type', 'status'],
                'where' => ['booking_id' => ''],
                'beforeselect' => 'checksecurity',
                'afterselect' => ''
            ]
        ]
    ],
    "notification" => [
        'tablename' => 'kloko_notification',
        'key' => 'id',
        'select' => ['id', 'booking_id', 'user_id', 'notification_time', 'type', 'status'],
        'create' => ['booking_id', 'user_id', 'notification_time', 'type', 'status'],
        'update' => ['status'],
        'delete' => true,
        'where' => ['booking_id' => '12345'],
        'beforeselect' => 'checksecurity',
        'afterselect' => '',
        'beforecreate' => 'checksecurity',
        'aftercreate' => '',
        'beforeupdate' => 'checksecurity',
        'afterupdate' => '',
        'beforedelete' => 'checksecurity'
    ],
    "day_template" => [
        'tablename' => 'kloko_day_template',
        'key' => 'id',
        'select' => ['id', 'user_id', 'title', 'description'],
        'create' => ['user_id', 'title', 'description'],
        'update' => ['title', 'description'],
        'delete' => true,
        'where' => ['user_id' => '22'],
        'beforeselect' => 'checksecurity',
        'afterselect' => '',
        'beforecreate' => 'checksecurity',
        'aftercreate' => '',
        'beforeupdate' => 'checksecurity',
        'afterupdate' => '',
        'beforedelete' => 'checksecurity',
        'subkeys' => [
            'event_templates' => [
                'tablename' => 'kloko_day_template_events',
                'key' => 'id',
                'select' => ['id', 'day_template_id', 'event_template_id', 'start_time'],
                'where' => ['day_template_id' => ''],
                'beforeselect' => 'checksecurity',
                'afterselect' => ''
            ]
        ]
    ],
    "event_template" => [
        'tablename' => 'kloko_event_template',
        'key' => 'id',
        'select' => ['id', 'user_id', 'title', 'description', 'image', 'content_id', 'duration', 'location', 'lat', 'lng', 'max_participants', 'price', 'event_type'],
        'create' => ['user_id', 'title', 'description', 'image', 'content_id', 'duration', 'location', 'lat', 'lng', 'max_participants', 'price', 'event_type'],
        'update' => ['title', 'description', 'duration', 'image', 'content_id', 'location', 'lat', 'lng', 'max_participants', 'price', 'event_type'],
        'delete' => true,
        'where' => ['user_id' => '22'],
        'beforeselect' => 'checksecurity',
        'afterselect' => '',
        'beforecreate' => 'checksecurity',
        'aftercreate' => '',
        'beforeupdate' => 'checksecurity',
        'afterupdate' => '',
        'beforedelete' => 'checksecurity'
    ],
    "search" => [
        'tablename' => 'kloko_event',
        'key' => 'id',
        'select' => "select * from kloko_event",
        'where' => ['app_id' => $appid],
        'fields' => [["title" => "in"], ["description" => "in"], ["user_id" => "="]],
        'beforeselect' => 'checksecurity',
        'afterselect' => ''
    ],
    "find" => [
        'tablename' => 'kloko_event',
        'key' => 'id',
        'select' => "SELECT ev.id, title, description, event_type, duration, location, 
              lat, lng, max_participants, price, start_time, content_id, 
              getDistance({lat}, {lng}, ev.lat, ev.lng) AS distance,
        ev.user_id, firstname, lastname, COALESCE(image, avatar) avatar, email, (SELECT COUNT(*) FROM kloko_booking bk WHERE bk.event_id = ev.id) bookings,
        (SELECT COUNT(*) FROM kloko_booking bk WHERE bk.event_id = ev.id AND bk.user_id = {userid}) booked
      FROM kloko_event ev, user
      WHERE event_type = {type}
        AND ev.user_id = user.id
        AND user.active = true
        AND DATE(start_time) between {from} and {to}
      ORDER BY distance",
        'where' => ['app_id' => ''],
        'beforeselect' => 'beforesearch',
        'afterselect' => ''
    ],
    "random" => [
        "tablename" => "kloko_event",
        "key" => "id",
        "select" => "SELECT ev.id, title, description, duration, location, lat, lng, max_participants, price, start_time,
        getDistance({lat},{lng}, ev.lat, ev.lng) AS distance, content_id,
        ev.user_id, firstname, lastname, COALESCE(image, avatar) avatar, email,
        (SELECT COUNT(*) FROM kloko_booking bk WHERE bk.event_id = ev.id) AS bookings,
        (SELECT COUNT(*) FROM kloko_booking bk WHERE bk.event_id = ev.id AND bk.user_id = {userid}) AS booked
            FROM kloko_event ev
            JOIN user ON ev.user_id = user.id
            WHERE user.active = true
            AND start_time > NOW() 
            ORDER BY RAND()
            LIMIT 3",

        'beforeselect' => 'beforesearch',
        'afterselect' => ''
    ]
];

function checksecurity($config, $data)
{
    global $appId;
    // $config["where"]["app_id"] = $appId;
    return [$config, $data];
}

function beforesearch($config, $data)
{
    global $appId, $userid;
    $config["params"]["app_id"] = $appId;
    $config["params"]["userid"] = $userid;
    return [$config, $data];
}

function beforecreate($config, $data)
{
    global $appId, $userid;
    $data["app_id"] = $appId;
    $data["user_id"] = $userid;
    return [$config, $data];
}

function formatIsoDate($date)
{
    $dateTime = new DateTime($date);
    $isoDate = $dateTime->format('Y-m-d\TH:i:s');
    return $isoDate;
}
function formatEventDates($config, $data)
{
    foreach ($data as $key => $value) {
        $data[$key]["start"] = formatIsoDate($value["start_time"]);
        $data[$key]["end"] = formatIsoDate($value["end_time"]);
    }
    return $data;
}

runAPI($configs);