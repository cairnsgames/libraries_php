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

function getSettingValueForUser($app_id, $profileid, $keyname)
{
    $sql = "
        SELECT COALESCE(o.val, s.val) AS value
        FROM settings s
        LEFT JOIN settingsoverrides o
          ON s.app_id = o.app_id
          AND s.keyname = o.keyname
          AND o.profileid = ?
        WHERE s.app_id = ?
          AND s.keyname = ?
    ";

    // Assuming the parameters are in this order: profileid, app_id, keyname
    $value = PrepareExecSQL($sql, 'sss', [ $profileid, $app_id, $keyname]);
    $setting = $value[0]["value"];
    return $setting;
}