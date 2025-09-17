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
    global $appId, $userid;
    if (!isset($appId) || empty($appId)) {
        throw new Exception("App ID is not set or empty.");
    }
    $dateTime = new DateTime($data["start_time"]);
    $dateTime->setTimezone(new DateTimeZone(date_default_timezone_get()));
    $data["start_time"] = $dateTime->format('Y-m-d H:i:s');
    $dateTime = new DateTime($data["end_time"]);
    $dateTime->setTimezone(new DateTimeZone(date_default_timezone_get()));
    $data["end_time"] = $dateTime->format('Y-m-d H:i:s');
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




function getUpcomingEvents($data)
{
    global $userid, $appId;
    // Use date from $data if provided, otherwise use current date
    $date = isset($data['date']) && !empty($data['date']) ? $data['date'] : date("Y-m-d H:i:s");

    // echo "User ID: $userid, App ID: $appId, Date: $date\n";
    $sql = "
        SELECT e.id,
               e.calendar_id,
               e.user_id,
               e.event_template_id,
               e.content_id,
               e.app_id,
               e.title,
               e.description,
               e.currency,
               e.price,
               e.image,
               e.keywords,
               e.event_type,
               e.duration,
               e.location,
               e.lat,
               e.lng,
               e.max_participants,
               e.period_type,
               e.tickettypes,
               e.options,
               e.start_time,
               e.end_time,
               e.show_as_news,
               e.overlay_text,
               e.enable_bookings,
               CASE WHEN uf.id IS NOT NULL THEN 1 ELSE 0 END AS favorite
        FROM kloko_event e
        LEFT JOIN user_favorites uf 
               ON uf.event_id = e.id 
              AND uf.user_id = ?
        WHERE e.end_time > ?
          AND e.app_id = ?
          AND e.event_type = 'event'
        ORDER BY e.start_time
    ";

    $params = [$userid, $date, $appId];
    $types = "sss";
    return PrepareExecSQL($sql, $types, $params);
}

function getKlokoUserTickets($data)
{
    $userId = $data["user"];
    $sql = "SELECT e.app_id,  e.id event_id, 
            t.id ticket_id, t.ticket_type_id, t.ticket_option_id, e.title event_title, e.description event_description, 
            t.description, t.quantity, t.currency, t.price, 
            e.keywords, e.duration, e.location, e.lat, e.lng, e.start_time, e.end_time
        FROM kloko_tickets t, kloko_event e
        WHERE t.event_id = e.id
        AND t.user_id = ?
        AND e.end_time > NOW()";
    return PrepareExecSQL($sql, 'i', [$userId]);
}

function getKlokoClasses($data)
{
    $search = $data["search"] ?? null;
    $limit = $data["limit"] ?? 20;
    $offset = $data["offset"] ?? 0;
    $lat = $data["lat"] ?? null;
    $lng = $data["lng"] ?? null;
    $distance = $data["distance"] ?? null;
    $startDate = $data["start_date"] ?? null;
    $endDate = $data["end_date"] ?? null;

    $params = [];
    $types = '';
    $where = ["e.start_time > NOW()"];

    // Optional full-text search — only if it's not empty or whitespace
    if (!is_null($search) && trim($search) !== '') {
        $where[] = "MATCH(e.title, e.keywords) AGAINST (? IN NATURAL LANGUAGE MODE)";
        $params[] = $search;
        $types .= 's';
    }

    // Optional date range filtering
    if (!empty($startDate)) {
        $where[] = "e.start_time >= ?";
        $params[] = $startDate;
        $types .= 's';
    }
    if (!empty($endDate)) {
        $where[] = "e.end_time <= ?";
        $params[] = $endDate;
        $types .= 's';
    }

    // Optional location filtering
    $selectDistance = '';
    if (!empty($lat) && !empty($lng) && !empty($distance)) {
        $selectDistance = ",
            (6371 * acos(
                cos(radians(?)) * cos(radians(e.lat)) *
                cos(radians(e.lng) - radians(?)) +
                sin(radians(?)) * sin(radians(e.lat))
            )) AS distance,";

        $where[] = "(6371 * acos(
                cos(radians(?)) * cos(radians(e.lat)) *
                cos(radians(e.lng) - radians(?)) +
                sin(radians(?)) * sin(radians(e.lat))
            )) <= ?";

        // Add parameters for both SELECT and WHERE
        array_push($params, $lat, $lng, $lat, $lat, $lng, $lat, $distance);
        $types .= 'dddddds';
    }

    $sql = "
        SELECT 
            e.id, 
            e.title, 
            e.start_time, 
            e.end_time, 
            e.duration, 
            e.user_id AS instructor_id, 
            e.max_participants, 
            (SELECT sum(quantity) FROM kloko_tickets WHERE event_id = e.id) AS currentEnrollment, 
            FALSE AS multiday, 
            e.lat, 
            e.lng,
            currency, price, 
            keywords, location, 
            $selectDistance
            CONCAT(u.firstname, ' ', u.lastname) AS instructor
        FROM kloko_event e
        JOIN user u ON e.user_id = u.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY e.start_time ASC
        LIMIT ? OFFSET ?
    ";

    // echo $sql;

    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    return PrepareExecSQL($sql, $types, $params);
}

function getKlokoMyClasses($data)
{
    global $userid;
    $data["user_id"] = $userid;
    return getKlokoClasses($data);
}



function setUserDefaultLocation($data)
{
    global $appId, $userid;
    if (!isset($appId) || empty($appId)) {
        throw new Exception("App ID is not set or empty.");
    }

    $id = $data["id"];
    $default = $data["default"];

    // Update all locations in one statement
    $sql = "UPDATE kloko_user_location 
                SET `default` = CASE WHEN location_id = ? THEN ? ELSE 0 END 
                WHERE user_id = ?";
    $params = [$id, $default, $userid];
    PrepareExecSQL($sql, "iii", $params);

    // $data["in"] = $data;
    // $data["user"] = $userid;
    // $data["app_id"] = $appId;
    // $data["sql"] = $sql;
    // $data["params"] = $params;

    return $data;
}

function getUserTicketsForEvent($config, $data) {
    global $userid;
    // var_dump("Config", $config);
    // var_dump("Data", $data);
    $event_id = $config['where']['event_id'] ?? null;
    // Expect event_id in $config or $data
    if (!$event_id) {
        throw new Exception("event_id is required");
    }

    // echo "User ID: $userid, Event ID: $event_id\n";

    $sql = "
        SELECT 
            t.event_id, 
            'ticket' AS itemtype, 
            t.id, 
            t.ticket_type_id, 
            0 AS ticket_option_id, 
            title, 
            t.description AS event_name, 
            tt.name AS ticket_name, 
            quantity, 
            t.currency, 
            t.price 
        FROM kloko_tickets t
        JOIN kloko_ticket_types tt ON t.ticket_type_id = tt.id
        WHERE t.user_id = ? AND t.event_id = ?
        UNION
        SELECT 
            t.event_id, 
            'ticket' AS itemtype, 
            t.id, 
            0 AS ticket_type_id, 
            t.ticket_option_id, 
            title, 
            t.description AS event_name, 
            tt.name AS ticket_name, 
            quantity, 
            t.currency, 
            t.price 
        FROM kloko_tickets t
        JOIN kloko_ticket_options tt ON t.ticket_option_id = tt.id
        WHERE t.user_id = ? AND t.event_id = ?
    ";

    $params = [$userid, $event_id, $userid, $event_id];
    return PrepareExecSQL($sql, "iiii", $params);
}