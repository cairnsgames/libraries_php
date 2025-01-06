<?php

include_once dirname(__FILE__) . "/dbconn.php";
include_once dirname(__FILE__) . "/rud.php";
include_once dirname(__FILE__) . "/get.php";
include_once dirname(__FILE__) . "/generateopenapi.php";
include_once dirname(__FILE__) . "/gapifunctions.php";

function runAPI($configs)
{
    try {
        // Rest Endpoint Handler
        $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $path = preg_replace('/.*\.php\//', '', $path);
        $method = $_SERVER['REQUEST_METHOD'];
        $parts = explode('/', $path);
        $endpoint = $parts[0];
        $id = isset($parts[1]) ? $parts[1] : null;
        $subkey = isset($parts[2]) ? $parts[2] : null;

        // Handle the special $$ endpoint
        if ($endpoint === '$$') {
            if ($method === 'GET') {
                header('Content-Type: application/json');
                echo json_encode(getConfig($configs));
            } else {
                http_response_code(405);
                echo json_encode(["error" => "Method not allowed"]);
            }
            exit;
        }

        if ($endpoint === 'openapi') {
            if ($method === 'GET') {
                header('Content-Type: application/json');
                echo json_encode(generateOpenAPISpec($configs));
            } else {
                http_response_code(405);
                echo json_encode(["error" => "Method not allowed"]);
            }
            exit;
        }

        if (!isset($configs[$endpoint])) {
            // Check for 'post' special endpoint
            if (isset($configs['post']) && $method === 'POST') {
                $postAction = $endpoint;
                if (isset($configs['post'][$postAction])) {
                    $functionName = $configs['post'][$postAction];
                    if (function_exists($functionName)) {
                        // Try to get JSON body data
                        $inputData = file_get_contents('php://input');
                        $data = json_decode($inputData, true);

                        // Fallback to $_POST if JSON data is not available or invalid
                        if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
                            $data = $_POST;
                        }

                        // Call the function with the data
                        $response = $functionName($data);
                        header('Content-Type: application/json');
                        echo json_encode($response);
                        exit;
                    }
                }
            }

            http_response_code(404);
            echo json_encode(["error" => "Endpoint not found"]);
            exit;
        }


        $config = $configs[$endpoint];

        list($limit, $orderBy) = getLimitAndOrderBy();

        if ($subkey && isset($config['subkeys'][$subkey])) {
            $subconfig = $config['subkeys'][$subkey];
            $subconfig['where'][$config['subkeys'][$subkey]['key']] = $id;
            $subconfig['limit'] = $limit;
            $subconfig['orderBy'] = $orderBy;

            switch ($method) {
                case 'GET':
                    $response = SelectData($subconfig);
                    break;
                default:
                    http_response_code(405);
                    echo json_encode(["error" => "Method not allowed for subkey"]);
                    exit;
            }
        } else {
            $config['limit'] = $limit;
            $config['orderBy'] = $orderBy;

            switch ($method) {
                case 'GET':
                    $response = SelectData($config, $id);
                    
                    // var_dump($response);
                    break;
                case 'POST':
                    if ($id === 'bulk') {
                        // Handle bulk insert
                        $data = json_decode(file_get_contents('php://input'), true);
                        if (!is_array($data)) {
                            http_response_code(400);
                            echo json_encode(["error" => "Invalid data format"]);
                            exit;
                        }
                        $response = CreateDataBulk($config, $data);
                    } else {
                        $data = [];
                        foreach ($config['create'] as $field) {
                            $value = getParam($field, null);

                            if ($field != null) {
                                $data[$field] = $value;
                            }
                        }
                        $response = CreateData($config, $data);
                    }
                    break;
                case 'PUT':
                    if ($id === null) {
                        http_response_code(400);
                        echo json_encode(["error" => "ID is required for update"]);
                        exit;
                    }
                    $data = [];
                    if ($config['update'] === false) {
                        http_response_code(405);
                        echo json_encode(["error" => "Method not allowed for update"]);
                        exit;
                    }
                    foreach ($config['update'] as $field) {
                        $value = getParam($field, null);
                        // echo "Field: $field = $value\n";
                        if ($field != null) {
                            $data[$field] = $value;
                        }
                    }
                    $response = UpdateData($config, $id, $data);
                    break;
                case 'DELETE':
                    if ($id === null) {
                        http_response_code(400);
                        echo json_encode(["error" => "ID is required for delete"]);
                        exit;
                    }

                    $response = DeleteData($config, $id);
                    break;
                default:
                    http_response_code(405);
                    echo json_encode(["error" => "Method not allowed"]);
                    exit;
            }
        }

        header('Content-Type: application/json');
        $json = json_encode($response, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            echo json_encode(['error' => json_last_error_msg()]);
            exit;
        }
        echo $json;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
}