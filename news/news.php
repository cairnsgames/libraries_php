<?php

include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../gapiv2/dbconn.php";
include_once dirname(__FILE__) . "/../gapiv2/utils.php";
include_once dirname(__FILE__) . "/../gapiv2/v2apicore.php";
include_once dirname(__FILE__) . "/../dbutils.php";

$appId = getAppId();
$token = getToken();

$userid = getUserId($token);

include_once dirname(__FILE__) . "/newsconfig.php";

// Define the configurations
function modifyrows($config, $rows)
{
    return $rows;
}

function addAppId($config, $data)
{
    global $appId;
    $data["app_id"] = $appId;
    
    $config["where"]["n.app_id"] = $appId;
    return [$config, $data];
}

function newsbeforecreate($config, $data)
{
    global $userid, $appId, $token;

    if (!hasValue($token)) {
        sendUnauthorizedResponse("Invalid token");
    }
    if (!hasValue($appId)) {
        sendUnauthorizedResponse("Invalid tenant");
    }

    $config["create"][] = "user_id";
    $config["create"][] = "app_id";
    $data["user_id"] = $userid;
    $data["app_id"] = $appId;

    return [$config, $data];
}

function checksecurity($config)
{
    global $appId;
    $config["where"]["app_id"] = $appId;
    return $config;
}
function checkSecurityCard($config)
{
    global $appId;
    $config["where"]["card.app_id"] = $appId;
    return $config;
}


function newsBeforeDelete($config, $id)
{
    global $userid, $appId, $token;

    if (!hasValue($token)) {
        sendUnauthorizedResponse("Invalid token");
    }
    if (!hasValue($appId)) {
        sendUnauthorizedResponse("Invalid tenant");
    }

    $sql = "SELECT user_id FROM news WHERE id = ? AND app_id = ?";
    $params = [$id, $appId];
    $sss = "ss";
    $result = PrepareExecSQL($sql, $sss, $params);

    if (empty($result) || $result[0]["user_id"] != $userid) {
        sendUnauthorizedResponse("You are not authorized to delete this news");
    }

    $config["where"]["user_id"] = $userid;
    $config["where"]["app_id"] = $appId;

    return [$config, $id];
}