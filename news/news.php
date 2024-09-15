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

    echo "beforecreate\n";
    $config["create"][] = "user_id";
    $config["create"][] = "app_id";
    $data["user_id"] = $userid;
    $data["app_id"] = $appId;
    echo "config: " . json_encode($config) . "\n";
    echo "data: " . json_encode($data) . "\n";

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

function updateStampCard($config, $data, $new_record)
{
    $cardId = $data["card_id"];
    $sql = "UPDATE loyalty_card SET stamps_collected = stamps_collected + 1 WHERE id = ?";
    $sss = 's';
    $params = [$cardId];
    $res = PrepareExecSQL($sql, $sss, $params);
    return [$config, $data, $new_record];
}


function loyaltyselect($endpoint, $id = null, $subkey = null, $where = [], $orderBy = '', $page = null, $limit = null)
{
    global $loyaltyconfigs;
    return GAPIselect($loyaltyconfigs, $endpoint, $id, $subkey, $where, $orderBy, $page, $limit);

}

function loyaltyupdate($endpoint, $id, $data)
{
    global $loyaltyconfigs;
    return GAPIupdate($loyaltyconfigs, $endpoint, $id, $data);

}

function loyaltycreate($endpoint, $data)
{
    global $loyaltyconfigs;
    return GAPIcreate($loyaltyconfigs, $endpoint, $data);
}

function loyaltydelete($endpoint, $id)
{
    global $loyaltyconfigs;
    return GAPIdelete($loyaltyconfigs, $endpoint, $id);
}