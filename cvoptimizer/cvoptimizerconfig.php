<?php

// Define configurations for cvoptimizer API
$cvoptimizerconfigs = [
    // Configuration for Users
    "user" => [
        'tablename' => 'user',
        'key' => 'id',
        'select' => ['id', 'firstname', 'lastname', 'email'], // Adjust columns as needed
        'create' => false,
        'update' => false,
        'delete' => false, // Users should not be deleted directly for integrity
        'where' => [], // Add default where clauses if needed
        'beforeselect' => '', // Optional validation logic
        'afterselect' => '',
        'subkeys' => [
            // Subkey for PersonCV
            'cv' => [
                'tablename' => 'cvoptimizer_PersonCV',
                'key' => 'user_id',
                'select' => ['id', 'user_id', 'cv_text', 'created_at', 'updated_at'],
                'beforeselect' => '',
                'afterselect' => ''
            ],
            'review' => [
                'tablename' => 'cvoptimizer_CVReview',
                'key' => 'person_cv_id',
                'select' => ['id', 'person_cv_id', 'review', 'created_at'],
                'beforeselect' => '',
                'afterselect' => ''
            ],
            // Subkey for JobAdvert
            'advert' => [
                'tablename' => 'cvoptimizer_JobAdvert',
                'key' => 'user_id',
                'select' => [
                    'id',
                    'title',
                    'company',
                    'company_website',
                    'description',
                    'source',
                    'status',
                    'date_applied',
                    'suggested_cv_id',
                    'created_at'
                ],
                'beforeselect' => '',
                'afterselect' => ''
            ]
        ]
    ],

    // Configuration for PersonCV
    "cv" => [
        'tablename' => 'cvoptimizer_PersonCV',
        'key' => 'id',
        'select' => ['id', 'user_id', 'cv_text', 'created_at', 'updated_at'],
        'create' => ['user_id', 'cv_text'],
        'update' => ['cv_text'],
        'delete' => true,
        'where' => [], // Add default where clauses if needed
        'beforeselect' => '',
        'beforecreate' => '',
        'beforeupdate' => '',
        'beforedelete' => '',
        'afterselect' => '',
        'aftercreate' => '',
        'afterupdate' => '',
    ],

    // Configuration for CVReview
    "review" => [
        'tablename' => 'cvoptimizer_CVReview',
        'key' => 'id',
        'select' => ['id', 'person_cv_id', 'review', 'created_at'],
        'create' => ['person_cv_id', 'review'],
        'update' => ['review'],
        'delete' => true,
        'where' => [], // Add default where clauses if needed
        'beforeselect' => '',
        'beforecreate' => '',
        'beforeupdate' => '',
        'beforedelete' => '',
        'afterselect' => '',
        'aftercreate' => '',
        'afterupdate' => '',
    ],

    // Configuration for JobAdvert
    "advert" => [
        'tablename' => 'cvoptimizer_JobAdvert',
        'key' => 'id',
        'select' => [
            'id',
            'title',
            'company',
            'company_website',
            'description',
            'source',
            'status',
            'date_applied',
            'suggested_cv_id',
            'user_id',
            'created_at'
        ],
        'create' => ['title', 'company', 'company_website', 'description', 'source', 'status', 'date_applied', 'suggested_cv_id', 'user_id'],
        'update' => ['title', 'company', 'company_website', 'description', 'source', 'status', 'date_applied', 'suggested_cv_id'],
        'delete' => true,
        'where' => [], // Add default where clauses if needed
        'beforeselect' => '',
        'beforecreate' => '',
        'beforeupdate' => '',
        'beforedelete' => '',
        'afterselect' => '',
        'aftercreate' => '',
        'afterupdate' => '',
    ],

    // Additional Post Endpoints
    "post" => [
        'customaction' => 'customPostAction', // Replace with actual function names for post operations
    ]
];

?>
