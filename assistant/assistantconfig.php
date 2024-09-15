<?php
$assistantconfigs = [
    "user" => [
        'tablename' => 'user',
        'key' => 'id',
        'select' => ['id', 'app_id', 'firstname', 'lastname'],
        'subkeys' => [
            'roles' => [
                'tablename' => 'assistant_user_role',
                'key' => 'user_id',
                'select' => ['id', 'user_id', 'venue_id', 'role_id'],
            ]
        ],
        'create' => false,
        'update' => false,
        'delete' => false
    ],
    "venue" => [
        'tablename' => 'user',
        'key' => 'id',
        'select' => ['id', 'app_id', 'firstname', 'lastname'],
        'subkeys' => [
            'roles' => [
                'tablename' => 'assistant_user_role',
                'key' => 'venue_id',
                'select' => ['id', 'user_id', 'venue_id', 'role_id'],
            ],
            'users' => [
                'tablename' => 'assistant_user_role',
                'key' => 'venue_id',
                'select' => 'SELECT ur.id, ur.user_id, u.firstname, u.lastname, ur.role_id 
                            FROM assistant_user_role ur 
                            JOIN user u ON ur.user_id = u.id 
                            WHERE ur.venue_id = {id}',
            ]
        ],
        'create' => false,
        'update' => false,
        'delete' => false
    ],
    "role" => [
        'tablename' => 'assistant_role',
        'key' => 'id',
        'select' => ['id', 'name', 'description'],
        'subkeys' => [
            'users' => [
                'tablename' => 'assistant_user_role',
                'key' => 'role_id',
                'select' => 'SELECT ur.id, ur.user_id, u.firstname, u.lastname 
                            FROM assistant_user_role ur 
                            JOIN user u ON ur.user_id = u.id 
                            WHERE ur.role_id = {role_id}',
            ],
            'permissions' => [
                'tablename' => 'assistant_role_permission',
                'key' => 'role_id',
                'select' => 'SELECT rp.id, rp.permission_id, p.name, p.description, least(p.value, rp.value) as value 
                            FROM assistant_role_permission rp 
                            JOIN assistant_permission p ON rp.permission_id = p.id 
                            ',
            ]
        ],
        'create' => ['name', 'description'],
        'update' => ['name', 'description'],
        'delete' => true
    ],
    "permission" => [
        'tablename' => 'assistant_permission',
        'key' => 'id',
        'select' => ['id', 'name', 'description', 'value'],
        'create' => ['name', 'description', 'value'],
        'update' => ['name', 'description', 'value'],
        'delete' => true
    ],
    "role_permission" => [
        'tablename' => 'assistant_role_permission',
        'key' => 'id',
        'select' => ['id', 'role_id', 'permission_id', 'value'],
        'create' => ['role_id', 'permission_id', 'value'],
        'update' => ['role_id', 'permission_id', 'value'],
        'delete' => true
    ],
    "user_role" => [
        'tablename' => 'assistant_user_role',
        'key' => 'id',
        'select' => ['id', 'user_id', 'venue_id', 'role_id'],
        'create' => ['user_id', 'venue_id', 'role_id'],
        'update' => ['user_id', 'venue_id', 'role_id'],
        'delete' => true
    ],
];
?>
