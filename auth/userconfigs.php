<?php

$userconfigs = [
    "user" => [
        'tablename' => 'user',
        'key' => 'id',
        'select' => ['id', 'firstname', 'lastname', 'email'],
        'create' => false,
        'update' => false,
        'delete' => false,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => '',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => '',
        'subkeys' => [
            'permissions' => [
                'tablename' => 'user_permissions',
                'key' => 'user_id',
                'select' => ['id', 'user_id', 'permission_id', 'value'],
                'beforeselect' => '',
                'afterselect' => ''
            ],
            'properties' => [
                'tablename' => 'user_property',
                'key' => 'user_id',
                'select' => ['id', 'user_id', 'name', 'value'],
                'beforeselect' => '',
                'afterselect' => ''
            ],
            'roles' => [
                'tablename' => 'user_role',
                'key' => 'user_id',
                'select' => ['id', 'user_id', 'role_id'],
                'beforeselect' => '',
                'afterselect' => ''
            ]
        ]
    ],
    "user_permissions" => [
        'tablename' => 'user_permissions',
        'key' => 'id',
        'select' => ['id', 'user_id', 'permission_id', 'value', 'date_created', 'date_modified'],
        'create' => ['user_id', 'permission_id', 'value'],
        'update' => ['permission_id', 'value'],
        'delete' => true,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => '',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => ''
    ],
    "user_property" => [
        'tablename' => 'user_property',
        'key' => 'id',
        'select' => ['id', 'user_id', 'name', 'value', 'created', 'modified'],
        'create' => ['user_id', 'name', 'value'],
        'update' => ['name', 'value'],
        'delete' => true,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => '',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => ''
    ],
    "user_role" => [
        'tablename' => 'user_role',
        'key' => 'id',
        'select' => ['id', 'user_id', 'role_id', 'created', 'modified'],
        'create' => ['user_id', 'role_id'],
        'update' => ['role_id'],
        'delete' => true,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => '',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => ''
    ]
];
