<?php
// Define the configurations
$loyaltyconfigs = [
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
            'systems' => [
                'tablename' => 'loyalty_system',
                'key' => 'venue_id',
                'select' => ['id', 'app_id', 'venue_id', 'name', 'description', 'stamps_required', 'reward_description', 'start_date', 'end_date'],
                'beforeselect' => '',
                'afterselect' => ''
            ],
            'cards' => [
                'tablename' => 'loyalty_card',
                'key' => 'user_id',
                'select' => ['id', 'app_id', 'user_id', 'system_id', 'qr_code', 'stamps_collected'],
                'beforeselect' => '',
                'afterselect' => ''
            ],
            'reward' => [
                'tablename' => 'loyalty_reward',
                'key' => 'user_id',
                'select' => ['id', 'app_id', 'user_id', 'system_id', 'reward_description', 'date_earned', 'date_redeemed'],
                'beforeselect' => '',
                'afterselect' => ''
            ]
        ]
    ],
    "system" => [
        'tablename' => 'loyalty_system',
        'key' => 'id',
        'select' => ['id', 'app_id', 'venue_id', 'name', 'description', 'stamps_required', 'reward_description', 'start_date', 'end_date'],
        'create' => ['app_id', 'venue_id', 'name', 'description', 'stamps_required', 'reward_description', 'start_date', 'end_date'],
        'update' => ['name', 'description', 'stamps_required', 'reward_description', 'start_date', 'end_date'],
        'delete' => true,
        'beforeselect' => '',
        'afterselect' => 'modifyrows',
        'beforecreate' => 'addAppId',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => '',
        'subkeys' => [
            'cards' => [
                'tablename' => 'loyalty_card',
                'key' => 'system_id',
                'select' => "SELECT card.id, user.id user_id, stamps_collected, firstname, lastname, email
                                FROM loyalty_card card, user
                                WHERE card.user_id = user.id",
                'beforeselect' => 'lCard',
                'afterselect' => ''
            ],
            'reward' => [
                'tablename' => 'loyalty_reward',
                'key' => 'system_id',
                'select' => ['id', 'app_id', 'user_id', 'system_id', 'reward_description', 'date_earned', 'date_redeemed'],
                'beforeselect' => '',
                'afterselect' => ''
            ],
        ]
    ],
    "card" => [
        'tablename' => 'loyalty_card',
        'key' => 'id',
        'select' => ['id', 'app_id', 'user_id', 'system_id', 'qr_code', 'stamps_collected'],
        'create' => ['app_id', 'user_id', 'system_id', 'qr_code', 'stamps_collected'],
        'update' => ['qr_code', 'stamps_collected'],
        'delete' => true,
        'where' => [],
        'beforeselect' => '',
        'afterselect' => 'modifyrows',
        'beforecreate' => 'addAppId',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => '',
        'subkeys' => [
            'stamps' => [
                'tablename' => 'loyalty_stamp',
                'key' => 'card_id',
                'select' => ['id', 'app_id', 'card_id', 'date_created'],
            ]
        ]
    ],
    "stamp" => [
        'tablename' => 'loyalty_stamp',
        'key' => 'id',
        'select' => ['id', 'app_id', 'card_id', 'date_created'],
        'create' => ['app_id', 'card_id', 'date_created'],
        'update' => false,
        'delete' => true,
        'where' => [],
        'beforeselect' => '',
        'afterselect' => 'modifyrows',
        'beforecreate' => 'beforecreate',
        'aftercreate' => 'updateStampCard',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => ''
    ],
    "reward" => [
        'tablename' => 'loyalty_reward',
        'key' => 'id',
        'select' => ['id', 'app_id', 'user_id', 'system_id', 'reward_description', 'date_earned', 'date_redeemed'],
        'create' => ['app_id', 'user_id', 'system_id', 'reward_description', 'date_earned', 'date_redeemed'],
        'update' => ['reward_description', 'date_redeemed'],
        'delete' => true,
        'where' => [],
        'beforeselect' => '',
        'afterselect' => 'modifyrows',
        'beforecreate' => '',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => ''
    ]
];
