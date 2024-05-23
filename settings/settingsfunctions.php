<?php

include_once dirname(__FILE__) . "/../dbconfig.php";
include_once dirname(__FILE__) . "/../dbutils.php";

function getSetting($appid, $keyname)
{
    $sql = "SELECT value FROM application_property WHERE app_id = ? AND name = ?";
    $params = array($appid, $keyname);
    $rows = PrepareExecSQL($sql, "ss", $params);
    if (count($rows) > 0) {
        return $rows[0]["value"];
    } else {
        return "";
    }
}

