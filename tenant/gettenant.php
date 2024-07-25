<?php

function getTenant($appid)
{
    $tenant = [];
    include_once dirname(__FILE__) . "/../dbconfig.php";
    include_once dirname(__FILE__) . "/../dbutils.php";
    $sql = "SELECT * FROM application WHERE uuid = ?";
    $params = array($appid);
    $rows = PrepareExecSQL($sql, "s", $params);
    if (count($rows) > 0) {
        $tenant = $rows[0];
    }
    $sql = "SELECT * FROM application_property WHERE app_id = ?";
    $params = array($appid);
    $rows = PrepareExecSQL($sql, "s", $params);
    foreach ($rows as $row) {
        $tenant[$row["name"]] = $row["value"];
    }
    return $tenant;
}