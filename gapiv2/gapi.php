<?php

include_once dirname(__FILE__) . "/dbconn.php";
include_once dirname(__FILE__) . "/rud.php";
include_once dirname(__FILE__) . "/get.php";
include_once dirname(__FILE__) . "/generateopenapi.php";
include_once dirname(__FILE__) . "/gapifunctions.php";

function GAPIcreate($configs, $endpoint, $data)
{
    if (!isset($configs[$endpoint])) {
        throw new Exception("Endpoint not found ($endpoint)");
    }

    $config = $configs[$endpoint];

    $payload = [];
    foreach ($config['create'] as $field) {
        if (isset($data[$field])) {
            $payload[$field] = $data[$field];
        }
    }

    $response = CreateData($config, $payload);
    return $response;
}

function GAPIupdate($configs, $endpoint, $id, $data)
{

    if (!isset($configs[$endpoint])) {
        throw new Exception("Endpoint not found");
    }

    if ($id === null) {
        throw new Exception("ID is required for update");
    }

    $config = $configs[$endpoint];

    $payload = [];
    foreach ($config['update'] as $field) {
        if (isset($data[$field])) {
            $payload[$field] = $data[$field];
        }
    }

    $response = UpdateData($config, $id, $payload);
    return $response;
}

function GAPIselect($configs, $endpoint, $id = null, $subkey = null, $where = [], $orderBy = '', $page = null, $limit = null)
{

    if (!isset($configs[$endpoint])) {
        throw new Exception("Endpoint not found ($endpoint)");
    }

    $config = $configs[$endpoint];

    // Set limit and orderBy if provided
    if ($limit) {
        $config['limit'] = $limit;
    }
    if ($orderBy) {
        $config['orderBy'] = $orderBy;
    }

    // Add where conditions if provided
    if (!empty($where)) {
        $config['where'] = array_merge($config['where'] ?? [], $where);
    }

    // Calculate offset based on page and limit
    if ($page && $limit) {
        $config['offset'] = ($page - 1) * $limit;
    }

    if ($subkey && isset($config['subkeys'][$subkey])) {
        $subconfig = $config['subkeys'][$subkey];
        $subconfig['where'][$config['subkeys'][$subkey]['key']] = $id;
        if (isset($limit)) {
            $subconfig['limit'] = $limit;
        }
        $subconfig['orderBy'] = $orderBy;

        if (!empty($where)) {
            $subconfig['where'] = array_merge($subconfig['where'] ?? [], $where);
        }

        $subconfig['offset'] = ($page - 1) * $limit;

        $response = SelectData($subconfig);
    } else {
        $response = SelectData($config, $id);
    }
    if (!is_array($response)) {
        $response = [];
    }

    return $response;
}

function GAPIdelete($configs, $endpoint, $id)
{

    if (!isset($configs[$endpoint])) {
        throw new Exception("Endpoint not found");
    }

    if ($id === null) {
        throw new Exception("ID is required for delete");
    }

    $config = $configs[$endpoint];
    $response = DeleteData($config, $id);
    return $response;
}
