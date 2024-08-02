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