<?php

$appId = getAppId();
$token = getToken();
$userId = getUserId($token);

$subscriptionconfigs = [
    "post" => ["active" => "hasActiveSubscription"],
    "user" => [
        'tablename' => 'user',
        'key' => 'id',
        'select' => ['id', 'firstname', 'lastname', 'email', 'avatar'],
        'create' => false,
        'update' => false,
        'delete' => false,
        'beforeselect' => 'forUserId',
        'subkeys' => [
            'subscriptions' => [
                'tablename' => 'subscription_user',
                'key' => 'user_id',
                'select' => 'select subscription_user.id, user_id, subscription_id, name, description, started, start_date, expiry_date, active from subscription_user, subscription
                  WHERE subscription_user.subscription_id = subscription.id',
                'select2' => ['id', 'user_id', 'subscription_id', 'started', "date_started", "date_end", "active"],
                'beforeselect' => 'forUserId',
                'afterselect' => ''
            ],
            'credits' => [
                'tablename' => 'subscription_user_credits',
                'key' => 'user_id',
                'select' => ['id', 'user_id', 'name', 'value'],
                'beforeselect' => 'forUserId',
                'afterselect' => ''
            ],
        ]
    ],
    "subscriptions" => [
        'tablename' => 'subscription',
        'key' => 'id',
        'select' => ['id', 'app_id', 'name', 'description', 'currency', 'price'],
        'beforeselect' => 'forAppId',
        'subkeys' => [
            'properties' => [
                'tablename' => 'subscription_property',
                'key' => 'subscription_id',
                'select' => ['id', 'name', 'value'],
            ]
        ]
    ],
];

function forAppId($config, $data)
{
    global $appId;
    $config["where"]["app_id"] = $appId;
    return [$config, $data];
}

function forUserId($config, $data)
{
    global $userId;
    if ($config['tablename'] == "user") {
        $config["where"]["id"] = $userId;
    } else {
        $config["where"]["user_id"] = $userId;
    }
    return [$config, $data];
}

function hasActiveSubscription($config)
{
    global $userId;
    $sql = "select * from subscription_user where user_id = ? and active = 1";
    $params = array($userId);
    $subscriptions = PrepareExecSQL($sql, "i", $params);
    $data["hasActiveSubscription"] = count($subscriptions) > 0;
    return [$config, $data];
}