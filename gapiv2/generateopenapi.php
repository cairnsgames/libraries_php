<?php

function generateOpenAPISpec($configs) {
    $openAPISpec = [
        'openapi' => '3.0.0',
        'info' => [
            'title' => 'API Documentation',
            'version' => '1.0.0'
        ],
        'paths' => [],
        'components' => [
            'schemas' => []
        ]
    ];

    foreach ($configs as $endpoint => $config) {
        $pathItem = [
            'get' => [
                'summary' => "Get $endpoint",
                'responses' => [
                    '200' => [
                        'description' => 'Successful response',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'array',
                                    'items' => ['$ref' => "#/components/schemas/$endpoint"]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'post' => [
                'summary' => "Create $endpoint",
                'responses' => [
                    '201' => [
                        'description' => 'Created',
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => "#/components/schemas/$endpoint"]
                            ]
                        ]
                    ]
                ],
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => "#/components/schemas/$endpoint"]
                        ]
                    ]
                ]
            ],
            'put' => [
                'summary' => "Update $endpoint",
                'responses' => [
                    '200' => [
                        'description' => 'Updated',
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => "#/components/schemas/$endpoint"]
                            ]
                        ]
                    ]
                ],
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => "#/components/schemas/$endpoint"]
                        ]
                    ]
                ]
            ],
            'delete' => [
                'summary' => "Delete $endpoint",
                'responses' => [
                    '204' => [
                        'description' => 'Deleted'
                    ]
                ]
            ]
        ];

        $openAPISpec['paths']['/' . $endpoint] = $pathItem;

        $schema = [
            'type' => 'object',
            'properties' => []
        ];

        foreach ($config['select'] as $field) {
            $schema['properties'][$field] = ['type' => 'string'];
        }

        $openAPISpec['components']['schemas'][$endpoint] = $schema;
    }

    return $openAPISpec;
}