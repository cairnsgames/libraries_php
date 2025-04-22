<?php

include_once "./corsheaders.php";
include_once "./dbutils.php";
include_once "./utils.php";
include_once "security.config.php";

$appid = getHeader("APP_ID", $appid = getHeader("app_id", $appid = getHeader("App_id", "")));
$userid = getParam("userid", "");
$old = getParam("old", "");
$password = getParam("password", "");
$password2 = getParam("password2", "");
$deviceid = getParam("deviceid", "");
$hash = getParam("hash", "");

$debugValues = array();
$errors = array();
$res = array();

if ($appid == "") {
    array_push($errors, array("message" => "App_id is Required."));
}
if ($userid == "") {
    array_push($errors, array("message" => "Userid is Required."));
}
if ($password == "" || $password2 == "") {
    array_push($errors, array("message" => "Password is Required."));
}
if ($password !== $password2) {
    array_push($errors, array("message" => "Passwords do not match."));
}

if (count($errors) > 0) {
    die(json_encode(array("errors" => $errors)));
}

try {
    $sql = "select * from user where app_id = ? and id = ? ";
    
    $params = array($appid, $userid);
    array_push($debugValues, array("selectUser" => array("sql" => $sql, "params" => $params)));
    $row = PrepareExecSQL($sql, "ss", $params);
    array_push($debugValues, array("user" => array("user" => $row)));

    if (count($row) == 0) {
        array_push($errors, array("message" => "User does not exist."));
    } else {

        $password_hash = crypt($password, $PASSWORDHASH);
        $sql = "update user set password = ? where app_id = ? and id = ? ";
        $params = array($password_hash, $appid, $userid);
        
        array_push($debugValues, array("changePassword" => array("sql" => $sql, "params" => $params)));
        $row = PrepareExecSQL($sql, "sss", $params);

        $res = array("message" => "Password changed.");
    }

} catch (Exception $e) {
    array_push($errors, array("message" => $e->getMessage()));
}


if (count($errors) > 0) {array_push($res, array("errors" => $errors));}
if (count($debugValues) > 0) {array_push($res, array("debug" => $debugValues));}
die(json_encode($res));