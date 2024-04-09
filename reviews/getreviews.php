<?php

include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../dbconfig.php";
include_once dirname(__FILE__) . "/../apicore/apicore.php";
include_once dirname(__FILE__) . "/../utils.php";
include_once dirname(__FILE__) . "/../auth/authfunctions.php";

$out = [];
$errors = [];

$appid = getAppId();
$token = getToken();
$type = getParam("type", "");
$itemid = getParam("id", "");
$userid = getUserId($token);

if ($type == "") {
    $errors[] = "Review Item Type not specified ?type=";
}
if ($itemid == "") {
    $errors[] = "Review Item Id not specified ?id=";
}
if ($appid == "") {
    $errors[] = "Application Id not defined in headers  APP_ID={guid}";
}

if (empty($errors)) {
    $sql = "SELECT reviews.id, stars, text, user.firstname, user.lastname 
FROM reviews, user
WHERE reviews.user_id = user.id
AND reviews.app_id = ?
AND item_type = ?
AND item_id = ?";
    $params = array($appid, $type, $itemid);
    $rowresult = PrepareExecSQL($sql, "sss", $params);
    $out["reviews"] = $rowresult;

    $sql = "SELECT avg(stars) rating
FROM reviews
WHERE reviews.app_id = ?
AND item_type = ?
AND item_id = ?";
    $params = array($appid, $type, $itemid);
    $rowresult = PrepareExecSQL($sql, "sss", $params);
    $out["average"] = $rowresult[0];

    if ($userid != "") {
        $sql = "SELECT reviews.id, stars, text, user.firstname, user.lastname
FROM reviews, user
WHERE reviews.user_id = user.id
AND reviews.app_id = ?
AND item_type = ?
AND item_id = ?
AND user_id = ?";
        $params = array($appid, $type, $itemid, $userid);
        $rowresult = PrepareExecSQL($sql, "ssss", $params);
        $out["myreview"] = $rowresult[0];
    }

    http_response_code(200);
}

if (!empty($errors)) {
    $out["error"] = $errors;
    http_response_code(400);
}

echo json_encode($out);