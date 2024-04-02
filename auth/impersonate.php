<?php

include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../dbutils.php";
include_once dirname(__FILE__) . "/../utils.php";
include_once dirname(__FILE__) . "/../security/security.config.php";
include_once dirname(__FILE__) . "/../auth/authfunctions.php";
include_once dirname(__FILE__) . "/../permissions/permissionfunctions.php";

$id = getParam("id", "");
$out = array();
$errors = array();
$debugValues = array();
$token = getParam("token", "");
$debug = getParam("debug", 0);
$appid = getAppId();
$deviceid = getParam("deviceid", "");

array_push($debugValues, array("token" => $token));

if (!$appid) {
    $errors[] = "No app id specified";
}
if ($id == "") {
    $error[] = "No user specified";
}
if ($token == "") {
    $errors[] = "No token specified";
} elseif (!validate_jwt($token)) {
    $errors[] = "Invalid token";
    $errors[] =  $jwtError;
}

if (empty($errors)) {
    $user = get_jwt_payload($token)->data;
    array_push($debugValues, array("user" => $user));

    if (hasAccess($user->id, "ImpersonateUser")) {
        getToken($id, $appid, $token);
        
        $out["mastertoken"] = $token;
    } else {
        $errors[] = "You do not have permission to impersonate a user";
    }
}

if (!empty($errors)) {
    $out["errors"] = $errors;
}
if ($debug != 0) {
    $out["debug"] = $debugValues;
}

echo json_encode($out);

