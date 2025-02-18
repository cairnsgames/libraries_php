<?php

include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../gapiv2/dbconn.php";
include_once dirname(__FILE__) . "/../gapiv2/v2apicore.php";
include_once dirname(__FILE__) . "/../utils.php";
include_once dirname(__FILE__) . "/../auth/authfunctions.php";

// A booking results in a cart item - so include breezo.php
include_once dirname(__FILE__) . "/../breezo/breezo.php";

/* Find
Lat, Lng (center of search), type (event_type), distance (radius of search), userid current user
*/


$appId = getAppId();
$token = getToken();

function klokoSecure()
{
    global $token, $appId;

    if (!hasValue($token)) {
        sendUnauthorizedResponse("Invalid token");
    }
    if (!hasValue($appId)) {
        sendUnauthorizedResponse("Invalid tenant");
    }
}

$userid = getUserId($token);

include_once dirname(__FILE__) . "/klokoconfig.php";

function klokochecksecurity($config, $data)
{
    global $appId;
    // $config["where"]["app_id"] = $appId;
    return [$config, $data];
}

function beforesearch($config, $data)
{
    global $appId, $userid;
    $config["params"]["app_id"] = $appId;
    $config["params"]["userid"] = $userid;
    return [$config, $data];
}

function beforecreate($config, $data)
{
    global $appId, $userid;
    $data["app_id"] = $appId;
    $data["user_id"] = $userid;
    return [$config, $data];
}

function beforeCreateEvent($config, $data)
{
    // var_dump($config, $data);
    global $appId, $userid;
    $data["app_id"] = $appId;
    $data["user_id"] = $userid;
    return [$config, $data];
}

function formatIsoDate($date)
{
    $dateTime = new DateTime($date);
    $isoDate = $dateTime->format('Y-m-d\TH:i:s');
    return $isoDate;
}
function formatEventDates($config, $data)
{
    foreach ($data as $key => $value) {
        $data[$key]["start"] = formatIsoDate($value["start_time"]);
        $data[$key]["end"] = formatIsoDate($value["end_time"]);
    }
    return $data;
}

function beforeCreateBooking($config, $data)
{
    global $appid, $userid, $token;
    $user = getUser($userid, $appid);
    $data["user_id"] = $userid;
    $data["booking_time"] = date("Y-m-d H:i:s");
    $data["participant_email"] = $user["email"];
    $data["status"] = "booked";
    return [$config, $data];
}

function afterCreateBooking($config, $data, $new_record)
{
    global $breezoconfigs;
    $event = klokoselect("event", $new_record[0]["event_id"]);
    breezocreate("cart_item", ["booking_id" => $new_record[0]["id"], "item_id" => 0, "item_type_id" => 1, "supplier_id" => $event[0]["user_id"], "price" => $event[0]["price"], "quantity" => 1]);
    return [$config, $data, $new_record];
}

function afterDeleteLocation($config, $id)
{
    $sql = "delete from kloko_user_location where location_id = ? ";
    $params = [$id];
    $sss = "s";
    PrepareExecSQL($sql, $sss, $params);

}

function klokoselect($endpoint, $id = null, $subkey = null, $where = [], $orderBy = '', $page = null, $limit = null)
{
    global $klokoconfigs;
    return GAPIselect($klokoconfigs, $endpoint, $id, $subkey, $where, $orderBy, $page, $limit);

}

function klokoupdate($endpoint, $id, $data)
{
    global $klokoconfigs;
    return GAPIupdate($klokoconfigs, $endpoint, $id, $data);

}

function klokocreate($endpoint, $data)
{
    global $klokoconfigs;
    return GAPIcreate($klokoconfigs, $endpoint, $data);
}

function klokodelete($endpoint, $id)
{
    global $klokoconfigs;
    return GAPIdelete($klokoconfigs, $endpoint, $id);
}




function getUpcomingEvents($data) {
    global $appId, $EVENT_FIELDS;
    $fields = implode(",", $EVENT_FIELDS);
    $sql = "select $fields from kloko_event where end_time > ? and app_id = ? order by start_time limit 10";
    $params = [date("Y-m-d H:i:s"), $appId];
    $sss = "ss";
    $result = PrepareExecSQL($sql, $sss, $params);
    // var_dump($result);
    return $result;
}

function getUserTickets($data)
{
    $userId = $data["user"];
    $sql = "SELECT e.app_id,  e.id event_id, 
            t.id ticket_id, t.ticket_type_id, t.ticket_option_id, e.title event_title, e.description event_description, 
            t.description, t.quantity, t.currency, t.price, 
            e.keywords, e.duration, e.location, e.lat, e.lng, e.start_time, e.end_time
        FROM kloko_tickets t, kloko_event e
        WHERE t.event_id = e.id
        AND t.user_id = ?";
    return PrepareExecSQL($sql, 'i', [$userId]);
}