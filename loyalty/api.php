<?php

include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../gapiv2/dbconn.php";
include_once dirname(__FILE__) . "/../gapiv2/utils.php";
include_once dirname(__FILE__) . "/../gapiv2/v2apicore.php";
include_once dirname(__FILE__) . "/../dbutils.php";

$appId = getAppId();
if (!$appId) {
    echo json_encode(["error" => "App ID not found"]);
    exit;
}

// Define the configurations
$configs = [
    "user" => [
        'tablename' => 'user',
        'key' => 'id',
        'select' => ['id', 'app_id', 'name', 'email', 'phone'],
        'create' => ['app_id', 'name', 'email', 'phone'],
        'update' => ['name', 'email', 'phone'],
        'delete' => true,
        'where' => ["app_id" => $appId],
        'beforeselect' => 'checksecurity',
        'afterselect' => 'modifyrows',
        'beforecreate' => 'addAppId',
        'aftercreate' => '',
        'beforeupdate' => 'checksecurity',
        'afterupdate' => '',
        'beforedelete' => 'checksecurity',
        'subkeys' => [
            'systems' => [
                'tablename' => 'loyalty_system',
                'key' => 'venue_id',
                'select' => ['id', 'app_id', 'venue_id', 'name', 'description', 'stamps_required', 'reward_description', 'start_date', 'end_date'],
                'beforeselect' => 'checksecurity',
                'afterselect' => ''
            ],
            'cards' => [
                'tablename' => 'loyalty_card',
                'key' => 'user_id',
                'select' => ['id', 'app_id', 'user_id', 'system_id', 'qr_code', 'stamps_collected'],
                'beforeselect' => 'checksecurity',
                'afterselect' => ''
            ],
            'reward' => [
                'tablename' => 'loyalty_reward',
                'key' => 'user_id',
                'select' => ['id', 'app_id', 'user_id', 'system_id', 'reward_description', 'date_earned', 'date_redeemed'],
                'beforeselect' => 'checksecurity',
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
        'where' => ["app_id" => $appId],
        'beforeselect' => 'checksecurity',
        'afterselect' => 'modifyrows',
        'beforecreate' => 'addAppId',
        'aftercreate' => '',
        'beforeupdate' => 'checksecurity',
        'afterupdate' => '',
        'beforedelete' => 'checksecurity',
        'subkeys' => [
            'cards' => [
                'tablename' => 'loyalty_card',
                'key' => 'system_id',
                'select' => "SELECT card.id, user.id user_id, stamps_collected, firstname, lastname, email
                                FROM loyalty_card card, user
                                WHERE card.user_id = user.id",
                'beforeselect' => 'checkSecurityCard',
                'afterselect' => ''
            ],
            'reward' => [
                'tablename' => 'loyalty_reward',
                'key' => 'system_id',
                'select' => ['id', 'app_id', 'user_id', 'system_id', 'reward_description', 'date_earned', 'date_redeemed'],
                'beforeselect' => 'checksecurity',
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
        'beforeselect' => 'checksecurity',
        'afterselect' => 'modifyrows',
        'beforecreate' => 'addAppId',
        'aftercreate' => '',
        'beforeupdate' => 'checksecurity',
        'afterupdate' => '',
        'beforedelete' => 'checksecurity',
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
        'beforeselect' => 'checksecurity',
        'afterselect' => 'modifyrows',
        'beforecreate' => 'beforecreate',
        'aftercreate' => 'updateStampCard',
        'beforeupdate' => 'checksecurity',
        'afterupdate' => '',
        'beforedelete' => 'checksecurity'
    ],
    "reward" => [
        'tablename' => 'loyalty_reward',
        'key' => 'id',
        'select' => ['id', 'app_id', 'user_id', 'system_id', 'reward_description', 'date_earned', 'date_redeemed'],
        'create' => ['app_id', 'user_id', 'system_id', 'reward_description', 'date_earned', 'date_redeemed'],
        'update' => ['reward_description', 'date_redeemed'],
        'delete' => true,
        'where' => [],
        'beforeselect' => 'checksecurity',
        'afterselect' => 'modifyrows',
        'beforecreate' => 'checksecurity',
        'aftercreate' => '',
        'beforeupdate' => 'checksecurity',
        'afterupdate' => '',
        'beforedelete' => 'checksecurity'
    ]
];

function modifyrows($config, $rows)
{
    return $rows;
}

function addAppId($config, $data)
{
    global $appId;
    $data["app_id"] = $appId;
    return [$config, $data];
}

function beforecreate($config, $data)
{
    return [$config, $data];
}

function checksecurity($config)
{
    global $appId;
    $config["where"]["app_id"] = $appId;
    return $config;
}
function checkSecurityCard($config)
{
    global $appId;
    $config["where"]["card.app_id"] = $appId;
    return $config;
}

function updateStampCard($config, $data, $new_record)
{
    $cardId = $data["card_id"];
    $sql = "UPDATE loyalty_card SET stamps_collected = stamps_collected + 1 WHERE id = ?";
    $sss = 's';
    $params = [$cardId];
    $res = PrepareExecSQL($sql, $sss, $params);
    return [$config, $data, $new_record];
}

runAPI($configs);