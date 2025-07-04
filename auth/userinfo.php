<?php

include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../utils.php";
include_once dirname(__FILE__) . "/../auth/authfunctions.php";

include_once dirname(__FILE__) . "/../gapiv2/gapi.php";

/* Find
Lat, Lng (center of search), type (event_type), distance (radius of search), userid current user
*/

$appId = getAppId();
$token = getToken();

if (!hasValue($token)) {
    sendUnauthorizedResponse("Invalid token");
}
if (!hasValue($appId)) {
    sendUnauthorizedResponse("Invalid tenant");
}

$userid = getUserId($token);

include_once dirname(__FILE__) . "/userconfigs.php";

function usercreate($endpoint, $data) {
    global $userconfigs;
    return GAPIcreate($userconfigs, $endpoint, $data);
}

function userselect($endpoint, $id = null, $subkey = null, $where = [], $orderBy = '', $page = null, $limit = null) {
    global $userconfigs;
    return GAPIselect($userconfigs, $endpoint, $id, $subkey, $where, $orderBy, $page, $limit);

}

function userupdate($endpoint, $id, $data) {
    global $userconfigs;
    return GAPIupdate($userconfigs, $endpoint, $id, $data);

}

function userdelete($endpoint, $id) {
    global $userconfigs;
    return GAPIdelete($userconfigs, $endpoint, $id);
}


function beforecreateproperty($config, $data)
{
    global $userid;
    $data['user_id'] = $userid;
    // var_dump($data);
    return [$config, $data];
}
