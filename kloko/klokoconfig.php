<?php

// Define the configurations
$klokoconfigs = [
    "calendar" => [
        'tablename' => 'kloko_calendar',
        'key' => 'id',
        'select' => ['id', 'user_id', 'app_id', 'title', 'description'],
        'create' => ['user_id', 'app_id', 'title', 'description'],
        'update' => ['title', 'description'],
        'delete' => true,
        'beforeselect' => '',
        'afterselect' => 'modifyrows',
        'beforecreate' => '',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => '',
        'subkeys' => [
            'events' => [
                'tablename' => 'kloko_event',
                'key' => 'calendar_id',
                'select' => ['id', 'calendar_id', 'user_id', 'event_template_id', 'content_id', 'app_id', 'title', 'description', 'image', 'event_type', 'duration', 'location', 'lat', 'lng', 'max_participants', 'start_time', 'end_time'],
                'beforeselect' => '',
                'afterselect' => 'formatEventDates'
            ]
        ]
    ],
    "event" => [
        'tablename' => 'kloko_event',
        'key' => 'id',
        'select' => ['id', 'calendar_id', 'user_id', 'event_template_id', 'content_id', 'app_id', 'title', 'description', 'price', 'image', 'event_type', 'duration', 'location', 'lat', 'lng', 'max_participants', 'start_time', 'end_time'],
        'create' => ['calendar_id', 'user_id', 'event_template_id', 'content_id', 'app_id', 'title', 'description', 'price', 'image', 'event_type', 'duration', 'location', 'lat', 'lng', 'max_participants', 'start_time', 'end_time'],
        'update' => ['title', 'description', 'price', 'image', 'content_id', 'event_type', 'duration', 'location', 'lat', 'lng', 'max_participants', 'start_time', 'end_time'],
        'delete' => true,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => 'beforecreate',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => '',
        'subkeys' => [
            'bookings' => [
                'tablename' => 'kloko_booking',
                'key' => 'event_id',
                'select' => ['id', 'event_id', 'user_id', 'participant_email', 'booking_time', 'status'],
                'beforeselect' => '',
                'afterselect' => ''
            ]
        ]
    ],
    "user" => [
        'tablename' => 'user',
        'key' => 'id',
        'select' => ['id', "firstname", "lastname", "avatar", "email"],
        'beforeselect' => '',
        'afterselect' => '',
        'subkeys' => [
            'bookings' => [
                'tablename' => 'kloko_booking',
                'key' => 'user_id',
                'select' => ['id', 'event_id', 'user_id', 'participant_email', 'booking_time', 'status'],
                'beforeselect' => '',
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
        'delete' => false,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => 'beforeCreateBooking',
        'aftercreate' => 'afterCreateBooking',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => '',
        'subkeys' => [
            'notifications' => [
                'tablename' => 'kloko_notification',
                'key' => 'id',
                'select' => ['id', 'booking_id', 'user_id', 'notification_time', 'type', 'status'],
                'where' => ['booking_id' => ''],
                'beforeselect' => '',
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
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => '',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => ''
    ],
    "day_template" => [
        'tablename' => 'kloko_day_template',
        'key' => 'id',
        'select' => ['id', 'user_id', 'title', 'description'],
        'create' => ['user_id', 'title', 'description'],
        'update' => ['title', 'description'],
        'delete' => true,
        'where' => ['user_id' => '22'],
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => '',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => '',
        'subkeys' => [
            'event_templates' => [
                'tablename' => 'kloko_day_template_events',
                'key' => 'id',
                'select' => ['id', 'day_template_id', 'event_template_id', 'start_time'],
                'where' => ['day_template_id' => ''],
                'beforeselect' => '',
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
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => '',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => ''
    ],
    "search" => [
        'tablename' => 'kloko_event',
        'key' => 'id',
        'select' => "select * from kloko_event",
        'where' => ['app_id' => $appid],
        'fields' => [["title" => "in"], ["description" => "in"], ["user_id" => "="]],
        'beforeselect' => '',
        'afterselect' => ''
    ],
    "find" => [
        'tablename' => 'kloko_event',
        'key' => 'id',
        'select' => "SELECT ev.id, title, description, event_type, duration, location, 
              lat, lng, max_participants, price, start_time, content_id, 
              getDistance({lat}, {lng}, ev.lat, ev.lng) AS distance,
        ev.user_id, firstname, lastname, COALESCE(image, avatar) avatar, email, (SELECT COUNT(*) FROM kloko_booking bk WHERE bk.event_id = ev.id) bookings,
        (SELECT COUNT(*) FROM kloko_booking bk WHERE bk.event_id = ev.id AND bk.user_id = {userid}) booked,
        (SELECT COUNT(*) FROM kloko_booking bk WHERE bk.event_id = ev.id AND bk.user_id = {userid} and bk.status = 'paid') AS paid
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
        (SELECT COUNT(*) FROM kloko_booking bk WHERE bk.event_id = ev.id AND bk.user_id = {userid}) AS booked,
        (SELECT COUNT(*) FROM kloko_booking bk WHERE bk.event_id = ev.id AND bk.user_id = {userid} and bk.status = 'paid') AS paid
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
