<?php

include_once dirname(__FILE__)."/../corsheaders.php";
include_once dirname(__FILE__)."/../gapiv2/dbconn.php";
include_once dirname(__FILE__)."/../gapiv2/utils.php";
include_once dirname(__FILE__)."/../gapiv2/v2apicore.php";

$appId = getAppId();

// Define the configurations
$configs = [
    "calendar" => [
        'tablename' => 'kloko_calendar',
        'key' => 'id',
        'select' => ['id', 'user_id', 'app_id', 'name', 'description'],
        'create' => ['user_id', 'app_id', 'name', 'description'],
        'update' => ['name', 'description'],
        'delete' => true,
        'where' => ['user_id' => '22'],
        'beforeselect' => 'checksecurity',
        'afterselect' => 'modifyrows',
        'beforecreate' => 'checksecurity',
        'aftercreate' => '',
        'beforeupdate' => 'checksecurity',
        'afterupdate' => '',
        'beforedelete' => 'checksecurity',
        'subkeys' => [
            'event' => [
                'tablename' => 'kloko_event',
                'key' => 'id',
                'select' => ['id', 'calendar_id', 'user_id', 'event_template_id', 'app_id', 'name', 'description', 'duration', 'location', 'max_participants', 'start_time', 'end_time'],
                'beforeselect' => 'checksecurity',
                'afterselect' => ''
            ]
        ]
    ],
    "event" => [
        'tablename' => 'kloko_event',
        'key' => 'id',
        'select' => ['id', 'calendar_id', 'user_id', 'event_template_id', 'app_id', 'name', 'description', 'duration', 'location', 'max_participants', 'start_time', 'end_time'],
        'create' => ['calendar_id', 'user_id', 'event_template_id', 'app_id', 'name', 'description', 'duration', 'location', 'max_participants', 'start_time', 'end_time'],
        'update' => ['name', 'description', 'duration', 'location', 'max_participants', 'start_time', 'end_time'],
        'delete' => true,
        'beforeselect' => 'checksecurity',
        'afterselect' => '',
        'beforecreate' => 'checksecurity',
        'aftercreate' => '',
        'beforeupdate' => 'checksecurity',
        'afterupdate' => '',
        'beforedelete' => 'checksecurity',
        'subkeys' => [
            'booking' => [
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
        'select' => ['id', "first_name", "last_name", "avatar", "email"],
        'beforeselect' => 'checksecurity',
        'afterselect' => '',
        'subkeys' => [
            'booking' => [
                'tablename' => 'kloko_booking',
                'key' => 'user_id',
                'select' => ['id', 'event_id', 'user_id', 'participant_email', 'booking_time', 'status'],
                'beforeselect' => 'checksecurity',
                'afterselect' => ''
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
            'notification' => [
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
        'select' => ['id', 'user_id', 'name', 'description'],
        'create' => ['user_id', 'name', 'description'],
        'update' => ['name', 'description'],
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
            'event_template' => [
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
        'select' => ['id', 'user_id', 'name', 'description', 'duration', 'location', 'max_participants'],
        'create' => ['user_id', 'name', 'description', 'duration', 'location', 'max_participants'],
        'update' => ['name', 'description', 'duration', 'location', 'max_participants'],
        'delete' => true,
        'where' => ['user_id' => '22'],
        'beforeselect' => 'checksecurity',
        'afterselect' => '',
        'beforecreate' => 'checksecurity',
        'aftercreate' => '',
        'beforeupdate' => 'checksecurity',
        'afterupdate' => '',
        'beforedelete' => 'checksecurity'
    ]
];

function checksecurity($config)
{
    global $appId;
    // $config["where"]["app_id"] = $appId;
    return $config;
}

runAPI($configs);