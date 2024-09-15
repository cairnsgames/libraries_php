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
                    user u ON n.user_id = u.id
                WHERE 
                    n.date <= NOW()
                    AND n.expires > NOW()",
        'where' => [],
        'create' => ["title", "body", "image_url", "date", "expires"],
        'update' => ["title", "body", "image_url", "date", "expires","deleted"],
        'delete' => false,
        'beforeselect' => 'addAppId',
        'afterselect' => '',
        'beforecreate' => 'newsbeforecreate',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => '',
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
    user u ON n.user_id = u.id
WHERE 
    n.date <= NOW()
    AND n.expires > NOW()",
                'beforeselect' => '',
                'afterselect' => ''
            ],
        ]
    ]
];
