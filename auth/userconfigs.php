<?php

$userconfigs = [
    "user" => [
        'tablename' => 'user',
        'key' => 'id',
        'select' => ['id', 'firstname', 'lastname', 'email','avatar'],
        'create' => false,
        'update' => ['firstname', 'lastname', 'email','avatar'],
        'delete' => false,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => '',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => 'calcToken',
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
                'select' => "select id, name from role r where r.id in (select role_id from user_role where user_id = ?)",
                'beforeselect' => '',
                'afterselect' => ''
            ],
            'old' => [
                'tablename' => 'user_mapping',
                'key' => 'old_profile_id',
                'select' => ['old_user_id', 'old_profile_id', 'new_user_id'],
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
    "property" => [
        'tablename' => 'user_property',
        'key' => 'id',
        'select' => ['id', 'user_id', 'name', 'value', 'created', 'modified'],
        'create' => ['user_id', 'name', 'value'],
        'update' => ['name', 'value'],
        'delete' => true,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => 'beforecreateproperty',
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

function calcToken($config, $updated_record) {
    // After updating the user, return with token to allow front end to record new token
    global $appId;
    $record = $updated_record[0];
    $user = getUser($record["id"], $appId);
    $jwt = getTokenForUser($record["id"],$appId, null);
    $user["token"] = $jwt;
    return $user;
}
