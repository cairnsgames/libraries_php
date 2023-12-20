<?php
include_once "./corsheaders.php";
// include_once "./dbutils.php";
include_once "./utils.php";

$out = [];
$errors = [];

$appid = getParam("app_id", "NONE");
if (!isset($appid) || $appid == "NONE") {
    $appid = getHeader("APP_ID", "NONE");
}
$out["appid"] = $appid;

$out["hello"] = "world";

if (count($errors) > 0) {
    $out["errors"] = $errors;
}

echo json_encode($out);