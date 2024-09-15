<?php

include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../gapiv2/dbconn.php";
include_once dirname(__FILE__) . "/../gapiv2/utils.php";
include_once dirname(__FILE__) . "/../gapiv2/v2apicore.php";
include_once dirname(__FILE__) . "/../dbutils.php";

$appId = getAppId();
$token = getToken();

if (!hasValue($token)) {
    sendUnauthorizedResponse("Invalid token");
}
if (!hasValue($appId)) {
    sendUnauthorizedResponse("Invalid tenant");
}

$userid = getUserId($token);

include_once dirname(__FILE__) . "/assistantconfig.php";

// Define the configurations
function modifyrows($config, $rows)
{
    return $rows;
}

function addAppId($config, $data)
{
    global $appId;
    $data["app_id"] = $appId;
    return [$config, $data];
}

function beforecreate($config, $data)
{
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
    $sql = "UPDATE assistant_card SET stamps_collected = stamps_collected + 1 WHERE id = ?";
    $sss = 's';
    $params = [$cardId];
    $res = PrepareExecSQL($sql, $sss, $params);
    return [$config, $data, $new_record];
}


function assistantselect($endpoint, $id = null, $subkey = null, $where = [], $orderBy = '', $page = null, $limit = null)
{
    global $assistantconfigs;
    return GAPIselect($assistantconfigs, $endpoint, $id, $subkey, $where, $orderBy, $page, $limit);

}

function assistantupdate($endpoint, $id, $data)
{
    global $assistantconfigs;
    return GAPIupdate($assistantconfigs, $endpoint, $id, $data);

}

function assistantcreate($endpoint, $data)
{
    global $assistantconfigs;
    return GAPIcreate($assistantconfigs, $endpoint, $data);
}

function assistantdelete($endpoint, $id)
{
    global $assistantconfigs;
    return GAPIdelete($assistantconfigs, $endpoint, $id);
}