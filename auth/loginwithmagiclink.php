<?php

include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../dbutils.php";
include_once dirname(__FILE__) . "/../utils.php";
include_once dirname(__FILE__) . "/../security/security.config.php";
include_once dirname(__FILE__) . "/authfunctions.php";

$appid = getAppId();
$deviceid = getParam("deviceid", "");
$code = getParam("code", "");

$debugValues = array();
$out = array();
$debug = getParam("debug", false);

// VALIDATIONS
$errors = array();

if ($appid == "" || $appid == "undefined") {
    array_push($errors, array("message" => "app_id header is required."));
}
if ($code == "") {
    array_push($errors, array("message" => "MagicCode is Required."));
}

if (count($errors) > 0) {
    $out = array("errors" => $errors);
    if ($debug) {
        array_push($out, array("debug" => $debugValues));
    }
    die(json_encode($out));
}

try {
    $sql = "select * from auth_magic_link where magic_code = ? and app_id = ?";
    $params = array($code, $appid);
    $row = PrepareExecSQL($sql, "ss", $params);

    $sql = "update auth_magic_link set used = used + 1, last_used = NOW(), device_id = ? where magic_code = ? and app_id = ?";
    $params = array($deviceid, $code, $appid);
    PrepareExecSQL($sql, "sss", $params);

    $user = getUserByEmail($row[0]["email"], $appid)[0];
    $jwt = getTokenForUser($user["id"], $appid);

    $profileid = $user["id"];
    $ipaddress = $_SERVER['REMOTE_ADDR'];
    if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
        $forwarded_for = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $forwarded_for = "";
    }

    $data = ["code" => $code];

    $sql = "insert into auth_login (userid,token, ip_address, forwarded_for, device_id, data) values (?,?,?,?,?,?)";
    $params = array($profileid, $jwt, $ipaddress, $forwarded_for, $deviceid, json_encode($data));
    $row = PrepareExecSQL($sql, "ssssss", $params);
    array_push($debugValues, array("insertAuthLogin" => array("sql" => $sql, "params" => $params)));

} catch (Exception $e) {
    array_push($errors, array("message" => $e->getMessage()));
}

if (count($errors) > 0) {
    $out = array("errors" => $errors);
    if ($debug) {
        array_push($out, array("debug" => $debugValues));
    }
    die(json_encode($out));
}

if ($debug) {
    array_push($out, array("debug" => $debugValues));
}
die(json_encode($out));