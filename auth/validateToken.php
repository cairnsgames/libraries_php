<?php

include_once "../corsheaders.php";
include_once "../dbutils.php";
include_once "../utils.php";
include_once "../security/security.config.php";
include_once "./authfunctions.php";

$email = '';
$password = '';

$out = array();
$errors = array();
$appid = getAppId();
$token = getParam("token", "");
$debug = getParam("debug", false);
$debugValues = array();

$mastertoken = null;

try {
    if (validateJwt($token) == true) {
        // Valid token
        $data = get_jwt_payload($token)->data;
        $id = $data->id;
        if (isset($data->mastertoken)) {
            $mastertoken = $data->mastertoken;
        }

        if ($debug) {
            $debugValues["debug"] = array("tokendata" => $data, "app_id" => $appid);
        }

        getToken($id, $appid, $mastertoken);
        if (isset($mastertoken)) {
            $out["mastertoken"] = $mastertoken;
        }

    } else {
        array_push($errors, array("message" => "Invalid token, please login"));
    }
} catch (Exception $e) {
    array_push($errors, array("message" => $e->getMessage()));
}

if (count($errors) > 0) {
    $out["errors"] = $errors;
}
if ($debug) {
    $out["debug"] = $debugValues;
}

die(json_encode($out));