<?php
include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../gapiv2/dbconn.php";
include_once dirname(__FILE__) . "/../dbutils.php"; // Include dbutils for PrepareExecSQL
include_once dirname(__FILE__) . "/../gapiv2/v2apicore.php";
include_once dirname(__FILE__) . "/../utils.php";
include_once dirname(__FILE__) . "/../auth/authfunctions.php";

// Example of how to call this file:
// http://yourdomain.com/kloko/getmappins.php?lat_nw=-26.0&lng_nw=27.0&lat_se=-27.0&lng_se=28.0

// Retrieve latitude and longitude from the request
$lat_nw = getParam('lat_nw', "");
$lng_nw = getParam('lng_nw', "");
$lat_se = getParam('lat_se', "");
$lng_se = getParam('lng_se', "");

if (!isset($lat_nw) || !isset($lng_nw) || !isset($lat_se) || !isset($lng_se)) {
    sendBadRequestResponse("Latitude and longitude parameters are required.");
}

// Fetch data from the database
$query = "
SELECT title, '' AS NAME, 'event' AS category, id, image, event_type AS keywords, lat, lng, 'blue' color, '#event' as reference, start_time, end_time
FROM kloko_event
WHERE lat < ? AND lat > ? AND lng > ? AND lng < ?
UNION
SELECT kl.name, CONCAT(u.firstname, ' ', u.lastname) AS NAME, 'partner' AS pintype, u.id, u.avatar, GROUP_CONCAT(role.name) AS keywords, lat, lng, 'blue' color, '#user' as reference, null, null
FROM kloko_location kl, kloko_user_location ul, user u, user_role, role
WHERE lat < ? AND lat > ? AND lng > ? AND lng < ? AND ul.user_id = u.id AND kl.id = ul.location_id AND showonmap = 1
AND user_role.user_id = u.id AND user_role.role_id = role.id
";

$result = PrepareExecSQL($query, 'dddddddd', [$lat_nw, $lat_se, $lng_nw, $lng_se, $lat_nw, $lat_se, $lng_nw, $lng_se]);

$data = $result;

header('Content-Type: application/json');
echo json_encode($data);
?>