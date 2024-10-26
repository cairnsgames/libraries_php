<?php
// Define the configurations
$partnerconfigs = [
    "partner" => [
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
            'payment' => [
                'tablename' => 'partner_banking',
                'key' => 'partner_id',
                'select' => ['id', 'bank_name','account_number','branch_code','payment_method','paypal_username'],
                'beforeselect' => '',
                'afterselect' => ''
            ],
            'banking' => [
                'tablename' => 'partner_banking',
                'key' => 'partner_id',
                'select' => ['id', 'bank_name','account_number','branch_code','payment_method','paypal_username'],
                'beforeselect' => '',
                'afterselect' => ''
            ],
        ]
    ],
    "banking" => [
        'tablename' => 'partner_banking',
        'key' => 'partner_id',
        'select' => false,
        'create' => ['partner_id', 'bank_name','account_number','branch_code','payment_method','paypal_username'],
        'update' => ['partner_id', 'bank_name','account_number','branch_code','payment_method','paypal_username'],
        'delete' => false,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => 'setPartnerId',
        'aftercreate' => '',
        'beforeupdate' => 'setPartnerId',
        'afterupdate' => '',
        'beforedelete' => '',
    ],
];
