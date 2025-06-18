<?php

include_once dirname(__FILE__) . "/../dbconfig.php";
include_once dirname(__FILE__) . "/../dbutils.php";


function getPropertyValue($appid, $keyname, $domain = null)
{
    // Prefer domain-specific value
    $sql = "SELECT value FROM application_property 
            WHERE app_id = ? AND name = ? AND (domain = ? OR domain IS NULL OR domain = '')
            ORDER BY (domain IS NULL OR domain = '') ASC LIMIT 1";
    $params = array($appid, $keyname, $domain);
    $rows = PrepareExecSQL($sql, "sss", $params);

    if (count($rows) > 0) {
        return $rows[0]["value"];
    }

    return "";
}

function getSecretValue($appid, $keyname, $domain = null)
{
    $sql = "SELECT value FROM application_secret 
            WHERE app_id = ? AND name = ? AND (domain = ? OR domain IS NULL OR domain = '')
            ORDER BY (domain IS NULL OR domain = '') ASC LIMIT 1";
    $params = array($appid, $keyname, $domain);
    $rows = PrepareExecSQL($sql, "sss", $params);

    if (count($rows) > 0) {
        return $rows[0]["value"];
    }

    return "";
}

function getSettingOrSecret($appid, $keyname, $domain = null)
{
    $value = getPropertyValue($appid, $keyname, $domain);
    if ($value == "") {
        $value = getSecretValue($appid, $keyname, $domain);
    }
    return $value;
}

function getPropertyValueForUser($app_id, $profileid, $keyname)
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
    $value = PrepareExecSQL($sql, 'sss', [$profileid, $app_id, $keyname]);
    $setting = $value[0]["value"];
    return $setting;
}