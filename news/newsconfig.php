<?php
// Define the configurations
$newsconfigs = [
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
    n.expires > NOW();",
        'where' => [],
        'create' => ["title", "body", "image_url", "overlay_text", "date", "expires"],
        'update' => ["title", "body", "image_url", "overlay_text", "date", "expires","deleted"],
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

