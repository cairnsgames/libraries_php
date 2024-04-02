<?php

include_once dirname(__FILE__)."/../corsheaders.php";
include_once dirname(__FILE__)."/../dbutils.php";
include_once dirname(__FILE__)."/../utils.php";
include_once dirname(__FILE__)."/../security/security.config.php";
include_once dirname(__FILE__)."/authfunctions.php";

$appid = getAppId();
$deviceid = getParam("deviceid", "");
$email = getParam("email", "");
$password = getParam("password", "");

$debugValues = array();
$out = array();
$debug = getParam("debug", false);

// VALIDATIONS
$errors = array();

if ($appid == "" || $appid == "undefined") {
    array_push($errors, array("message" => "app_id header is required."));
}
if ($email == "") {
    array_push($errors, array("message" => "Email is Required."));
}
if ($password == "") {
    array_push($errors, array("message" => "Password is Required."));
}

if (count($errors) > 0) {
    $out = array("errors" => $errors);
    if ($debug) {
    array_push($out, array("debug" => $debugValues));}
    die(json_encode($out));
}

getLoginToken($email, $password, $appid);

if (count($errors) > 0) {
    $out = array("errors" => $errors);
    if ($debug){
    array_push($out, array("debug" => $debugValues));}
    die(json_encode($out));
}

if ($debug) {array_push($out, array("debug" => $debugValues));}
die(json_encode($out));