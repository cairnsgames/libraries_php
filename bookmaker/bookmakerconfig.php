<?php

// Define the configurations
$bookmakerconfigs = [
    "items" => [
        'tablename' => 'bookmaker_items',
        'key' => 'id',
        'select' => ['id', 'app_id', 'type', 'name', 'role', 'description', 'keywords', 'aliases', 'classnames', 'hierarchy_id'],
        'create' => ['app_id', 'type', 'name', 'role', 'description', 'keywords', 'aliases', 'classnames', 'hierarchy_id'],
        'update' => ['type', 'name', 'role', 'description', 'keywords', 'aliases', 'classnames', 'hierarchy_id'],
        'delete' => true,
        'beforeselect' => 'checkBookmakerSecurity',
        'afterselect' => 'modifyBookmakerRows',
        'beforecreate' => 'checkBookmakerSecurity',
        'aftercreate' => '',
        'beforeupdate' => 'checkBookmakerSecurity',
        'afterupdate' => '',
        'beforedelete' => 'checkBookmakerSecurity',
    ],
    "hierarchy" => [
        'tablename' => 'bookmaker_hierarchy',
        'key' => 'id',
        'select' => ['id', 'app_id', 'name', 'type', 'description', 'parent_id'],
        'create' => ['app_id', 'name', 'type', 'description', 'parent_id'],
        'update' => ['name', 'type', 'description', 'parent_id'],
        'delete' => true,
        'beforeselect' => 'checkBookmakerSecurity',
        'afterselect' => '',
        'beforecreate' => 'checkBookmakerSecurity',
        'aftercreate' => '',
        'beforeupdate' => 'checkBookmakerSecurity',
        'afterupdate' => '',
        'beforedelete' => 'checkBookmakerSecurity',
        'subkeys' => [
            // Subkey to get child hierarchy entries (e.g., sub-levels in the hierarchy)
            'children' => [
                'tablename' => 'bookmaker_hierarchy',
                'key' => 'parent_id',
                'select' => ['id', 'app_id', 'name', 'type', 'description', 'parent_id'],
                'beforeselect' => 'checkBookmakerSecurity',
                'afterselect' => ''
            ],
            // Subkey to get items belonging to this hierarchy level
            'items' => [
                'tablename' => 'bookmaker_items',
                'key' => 'hierarchy_id',
                'select' => "getItemsInHierarchy",
                'beforeselect' => 'checkBookmakerSecurity',
                'afterselect' => ''
            ],
            'tree' => [
                'tablename' => 'bookmaker_hierarchy',
                'key' => 'id',
                'select' => "getHierarchyTree",
                'beforeselect' => 'checkBookmakerSecurity',
                'afterselect' => ''
            ]
        ]
    ],
    "relationships" => [
        'tablename' => 'bookmaker_relationships',
        'key' => 'id',
        'select' => ['id', 'app_id', 'parent_id', 'child_id'],
        'create' => ['app_id', 'parent_id', 'child_id'],
        'update' => ['parent_id', 'child_id'],
        'delete' => true,
        'beforeselect' => 'checkBookmakerSecurity',
        'afterselect' => '',
        'beforecreate' => 'checkBookmakerSecurity',
        'aftercreate' => '',
        'beforeupdate' => 'checkBookmakerSecurity',
        'afterupdate' => '',
        'beforedelete' => 'checkBookmakerSecurity',
    ],
];
