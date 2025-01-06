<?php

function getConfig($config) {
    $simpleConfig = [];
    foreach ($config as $key => $value) {
        $simpleConfig[$key] = [
            'select' => isset($value['select']) ? $value['select'] : [],
            'create' => isset($value['create']) ? $value['create'] : [],
            'update' => isset($value['update']) ? $value['update'] : [],
            'delete' => isset($value['delete']) ? $value['delete'] : false
        ];
    }
    return $simpleConfig;
}

function clean_invalid_utf8($data) {
    return preg_replace('/[^\x09\x0A\x0D\x20-\x7E\xA0-\xFF]/', '', $data);
}

function convert_to_utf8($data) {
    if (is_array($data)) {
        return array_map('convert_to_utf8', $data);
    } elseif (is_object($data)) {
        foreach ($data as $key => $value) {
            $data->$key = convert_to_utf8($value);
        }
        return $data;
    } elseif (is_string($data)) {
        if (!mb_check_encoding($data, 'UTF-8')) {
           echo("Invalid encoding detected: " . print_r($data, true));
        }
        $data = clean_invalid_utf8($data);
        if (!mb_check_encoding($data, 'UTF-8')) {
            $data = mb_convert_encoding($data, 'UTF-8', 'ISO-8859-1');
        }
        return $data;
    }
    return $data;
}