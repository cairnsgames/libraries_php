<?php

include_once dirname(__FILE__)."/../corsheaders.php";
include_once dirname(__FILE__)."/../gapiv2/dbconn.php";
include_once dirname(__FILE__)."/../gapiv2/utils.php";
include_once dirname(__FILE__)."/../gapiv2/v2apicore.php";

$appId = getAppId();

// Define the configurations
$configs = [
    "project" => [
        'tablename' => 'api_project',
        'key' => 'id',
        'select' => ['id', 'name', 'description'],
        'create' => ['name', 'description'],
        'update' => ['name', 'description'],
        'delete' => true,
        'beforeselect' => 'checksecurity',
        'afterselect' => 'modifyRows',
        'beforecreate' => 'checksecurity',
        'aftercreate' => '',
        'beforeupdate' => 'checksecurity',
        'afterupdate' => '',
        'beforedelete' => 'checksecurity',
        'subkeys' => [
            'default' => [
                'tablename' => 'api_default',
                'key' => 'id',
                'select' => ['id', 'project_id', 'default_headers'],
                'beforeselect' => 'checksecurity',
                'afterselect' => 'modifyRows'
            ],
            'calls' => [
                'tablename' => 'api_calls',
                'key' => 'project_id',
                'select' => ['id', 'project_id', 'name', 'url', 'method', 'headers', 'body'],
                'beforeselect' => 'checksecurity',
                'afterselect' => 'modifyRows'
            ]
        ]
    ],
    "call" => [
        'tablename' => 'api_calls',
        'key' => 'id',
        'select' => ['id', 'project_id', 'name', 'url', 'method', 'headers', 'body'],
        'create' => ['project_id', 'name', 'url', 'method', 'headers', 'body'],
        'update' => ['name', 'url', 'method', 'headers', 'body'],
        'delete' => true,
        'beforeselect' => 'checksecurity',
        'afterselect' => 'modifyRows',
        'beforecreate' => 'checksecurity',
        'aftercreate' => '',
        'beforeupdate' => 'checksecurity',
        'afterupdate' => '',
        'beforedelete' => 'checksecurity'
    ],
    "default" => [
        'tablename' => 'api_default',
        'key' => 'id',
        'select' => ['id', 'project_id', 'default_headers'],
        'create' => ['project_id', 'default_headers'],
        'update' => ['default_headers'],
        'delete' => true,
        'beforeselect' => 'checksecurity',
        'afterselect' => 'modifyRows',
        'beforecreate' => 'checksecurity',
        'aftercreate' => '',
        'beforeupdate' => 'checksecurity',
        'afterupdate' => '',
        'beforedelete' => 'checksecurity'
    ]
];

function checksecurity($config)
{
    global $appId;
    // $config["where"]["app_id"] = $appId;
    return $config;
}

function modifyRows($config, $results) {
    foreach ($results as &$row) {
        if (isset($row['headers'])) {
            $row['headers'] = json_decode($row['headers'], true);
        }
        if (isset($row['body'])) {
            $row['body'] = json_decode($row['body'], true);
        }
        if (isset($row['default_headers'])) {
            $row['default_headers'] = json_decode($row['default_headers'], true);
        }
    }
    return $results;
}

runAPI($configs);