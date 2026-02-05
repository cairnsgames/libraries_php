<?php

$EVENT_FIELDS = ['id', 'calendar_id', 'user_id', 'event_template_id', 'content_id', 'app_id', 'title', 'description', 'currency', 'price', 'image', 'keywords', 'event_type', 'duration', 'location', 'lat', 'lng', 'max_participants', 'period_type', 'tickettypes', 'options', 'start_time', 'end_time', 'show_as_news', 'overlay_text', 'enable_bookings'];

// Define the configurations
$klokoconfigs = [
    "post" => [
        "events" => "getUpcomingEvents",
        "usertickets" => "getKlokoUserTickets",
        "classes" => "getKlokoClasses",
        "myclasses" => "getKlokoMyClasses",
        "setUserDefaultLocation" => "setUserDefaultLocation"
    ],
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
                'select' => $EVENT_FIELDS,
                'beforeselect' => '',
                'afterselect' => 'formatEventDates'
            ]
        ]
    ],
    "event" => [
        'tablename' => 'kloko_event',
        'key' => 'id',
        'select' => ['id', 'calendar_id', 'user_id', 'event_template_id', 'content_id', 'app_id', 'title', 'description', 'currency', 'price', 'image', 'keywords', 'event_type', 'duration', 'location', 'lat', 'lng', 'max_participants', 'start_time', 'end_time', 'period_type', 'tickettypes', 'options', 'show_as_news', 'overlay_text', 'enable_bookings'],
        'create' => ['calendar_id', 'user_id', 'event_template_id', 'content_id', 'app_id', 'title', 'description', 'currency', 'price', 'image', 'keywords', 'event_type', 'duration', 'location', 'lat', 'lng', 'max_participants', 'start_time', 'end_time', 'period_type', 'tickettypes', 'options', 'show_as_news', 'overlay_text', 'enable_bookings'],
        'update' => ['title', 'description', 'currency', 'price', 'image', 'content_id', 'keywords', 'event_type', 'duration', 'location', 'lat', 'lng', 'max_participants', 'start_time', 'end_time', 'period_type', 'tickettypes', 'options', 'show_as_news', 'overlay_text', 'enable_bookings'],
        'delete' => true,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => 'beforeCreateEvent',
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
            ],
            'tickets' => [
                'tablename' => 'kloko_ticket_types',
                'key' => 'event_id',
                'select' => ['id', 'event_id', 'name', 'description', 'currency', 'price'],
                'beforeselect' => '',
                'afterselect' => ''
            ],
            'options' => [
                'tablename' => 'kloko_ticket_options',
                'key' => 'event_id',
                'select' => ['id', 'event_id', 'name', 'description', 'currency', 'price'],
                'beforeselect' => '',
                'afterselect' => ''
            ],
            'user' => [
                'tablename' => 'kloko_tickets',
                'key' => 'event_id',
                'select' => 'getUserTicketsForEvent',
                'beforeselect' => '',
                'afterselect' => ''
            ],
            'classes' => [
                'tablename' => 'kloko_event',
                'key' => 'parent_id',
                'select' => ['id', 'calendar_id', 'user_id', 'event_template_id', 'content_id', 'app_id', 'title', 'description', 'currency', 'price', 'image', 'keywords', 'event_type', 'duration', 'location', 'lat', 'lng', 'max_participants', 'start_time', 'end_time', 'period_type', 'tickettypes', 'options', 'show_as_news', 'overlay_text', 'enable_bookings'],,
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
            "bookings" => [
                'tablename' => 'kloko_booking',
                'key' => 'b.user_id',
                'select' => "SELECT b.*, ev.title, ev.description, ev.start_time, ev.price
FROM kloko_booking b, kloko_event ev WHERE b.event_id = ev.id",
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
                'select' => ['id', 'user_id', 'title', 'description', 'image', 'content_id', 'duration', 'location', 'lat', 'lng', 'max_participants', 'currency', 'price', 'keywords', 'event_type'],
            ],
            "locations" => [
                'tablename' => 'kloko_user_location',
                'key' => 'user_id',
                'select' => "SELECT l.id, ul.user_id, l.name, l.address_line1, l.address_line2, l.showonmap, l.town, l.lat, l.lng, ul.default 
FROM kloko_user_location ul
JOIN kloko_location l ON ul.location_id = l.id",
                'beforeselect' => '',
                'afterselect' => ''
            ],
            "events" => [
                'tablename' => 'kloko_event',
                'key' => 'user_id',
                'select' => [
                    'id',
                    'user_id',
                    'title',
                    'description',
                    'image',
                    'event_type',
                    'keywords',
                    'duration',
                    'start_time',
                    'end_time',
                    'location',
                    'lat',
                    'lng',
                    'max_participants',
                    'currency',
                    'price',
                    'keywords',
                    'event_type',
                    'show_as_news',
                    'enable_bookings',
                    'overlay_text'
                ],
            ]
        ]
    ],

    "location" => [
        'tablename' => 'kloko_location',
        'key' => 'id',
        'select' => ['id', 'name', 'address_line1', 'address_line2', 'town', 'showonmap', 'lat', 'lng'],
        'create' => ['name', 'address_line1', 'address_line2', 'town', 'showonmap', 'lat', 'lng'],
        'update' => ['name', 'address_line1', 'address_line2', 'town', 'showonmap', 'lat', 'lng'],
        'delete' => true,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => '',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => '',
        'afterdelete' => 'afterDeleteLocation'
    ],

    "user_location" => [
        'tablename' => 'kloko_user_location',
        'key' => 'id',
        'select' => ['id', 'user_id', 'location_id', 'created', 'modified'],
        'create' => ['user_id', 'location_id'],
        'update' => ['default'],
        'delete' => true,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => '',
        'aftercreate' => '',
        'beforedelete' => ''
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
    'tickettype' => [
        'tablename' => 'kloko_ticket_types',
        'key' => 'id',
        'select' => ['id', 'event_id', 'name', 'description', 'currency', 'price'],
        'create' => ['event_id', 'name', 'description', 'currency', 'price'],
        'update' => ['name', 'description', 'currency', 'price'],
        'delete' => true,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => '',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => ''
    ],
    'ticketoption' => [
        'tablename' => 'kloko_ticket_options',
        'key' => 'id',
        'select' => ['id', 'event_id', 'name', 'description', 'currency', 'price'],
        'create' => ['event_id', 'name', 'description', 'currency', 'price'],
        'update' => ['name', 'description', 'currency', 'price'],
        'delete' => true,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => '',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => ''
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
    "template" => [
        'tablename' => 'kloko_event_template',
        'key' => 'id',
        'select' => ['id', 'user_id', 'title', 'description', 'image', 'content_id', 'duration', 'location', 'lat', 'lng', 'max_participants', 'currency', 'price', 'keywords', 'event_type'],
        'create' => ['user_id', 'title', 'description', 'image', 'content_id', 'duration', 'location', 'lat', 'lng', 'max_participants', 'currency', 'price', 'keywords', 'event_type'],
        'update' => ['title', 'description', 'duration', 'image', 'content_id', 'location', 'lat', 'lng', 'max_participants', 'currency', 'price', 'keywords', 'event_type'],
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
        'select' => "SELECT ev.id, title, description, keywords, event_type, duration, location, 
              lat, lng, max_participants, price, start_time, content_id, show_as_news, enable_bookings,
              getDistance({lat}, {lng}, ev.lat, ev.lng) AS distance,
        ev.user_id, firstname, lastname, COALESCE(image, avatar) avatar, email, (SELECT COUNT(*) FROM kloko_booking bk WHERE bk.event_id = ev.id) bookings,
        (SELECT COUNT(*) FROM kloko_booking bk WHERE bk.event_id = ev.id AND bk.user_id = {userid}) booked,
        (SELECT COUNT(*) FROM kloko_booking bk WHERE bk.event_id = ev.id AND bk.user_id = {userid} and bk.status = 'paid') AS paid
      FROM kloko_event ev, user
      WHERE (event_type = {type} OR keywords = {type})
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
        getDistance({lat},{lng}, ev.lat, ev.lng) AS distance, content_id, show_as_news, enable_bookings,
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
    ],
    "mycalendar" => [
        'tablename' => 'kloko_calendar',
        'key' => 'user_id',
        'select' => "getMyCalendarEvents",
    ]
];
