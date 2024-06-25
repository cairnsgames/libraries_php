<?php

include_once dirname(__FILE__) . "/../dbconfig.php";
include_once dirname(__FILE__) . "/../dbutils.php";

function getSettingValue($appid, $keyname)
{
    $sql = "SELECT value FROM application_property WHERE app_id = ? AND name = ?";
    $params = array($appid, $keyname);
    $rows = PrepareExecSQL($sql, "ss", $params);
    if (count($rows) > 0) {
        return $rows[0]["value"];
    }

    return "";
}

function getSecretValue($appid, $keyname)
{

    $sql = "SELECT value FROM application_secret WHERE app_id = ? AND name = ?";
    $params = array($appid, $keyname);
    $rows = PrepareExecSQL($sql, "ss", $params);
    if (count($rows) > 0) {
        return $rows[0]["value"];
    }

    return "";
}

function getSettingOrSecret($appid, $keyname)
{
    $value = getSettingValue($appid, $keyname);
    if ($value == "") {
        $value = getSecretValue($appid, $keyname);
    }
    return $value;
}