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

// Define temporary values for smallest and biggest lat/lng
$smallestlat = min($lat_nw, $lat_se);
$biggestlat = max($lat_nw, $lat_se);
$smallestlng = min($lng_nw, $lng_se);
$biggestlng = max($lng_nw, $lng_se);

// Fetch data from the database
$query = "
SELECT id as pinid, title, 
    '[]' offerings, '' AS name, 'event' AS category, id, image, JSON_ARRAY(event_type) subcategory, keywords, lat, lng, 'blue' color, '#event' as reference, start_time, end_time
FROM kloko_event
WHERE lat < ? AND lat > ? AND lng < ? AND lng > ?
UNION
SELECT 
    kl.id, 
    kl.name, 
    COALESCE(
      JSON_ARRAYAGG(
        DISTINCT
        IF(
          oi.id IS NULL, 
          NULL, 
          JSON_OBJECT(
            'item_id',   oi.id,
            'item_name', oi.name,
            'group_id',  og.id,
            'group_name',og.name
          )
        )
      ),
      JSON_ARRAY()
    ) AS offerings,
    CONCAT(u.firstname, ' ', u.lastname) AS NAME, 
    'partner' AS partner,
    u.id, 
    u.avatar, 
    JSON_ARRAYAGG(DISTINCT r.name) AS roles,
    '' AS keywords, 
    lat, 
    lng, 
    'blue' AS color, 
    '#user' AS reference, 
    NULL, 
    NULL
FROM kloko_location kl
JOIN kloko_user_location kul 
    ON kl.id = kul.location_id
JOIN user u 
    ON kul.user_id = u.id
JOIN user_role ur 
    ON u.id = ur.user_id
JOIN role r 
    ON ur.role_id = r.id
LEFT JOIN user_offerings uo 
    ON uo.user_id = u.id 
   AND uo.active = 1
LEFT JOIN offeringitem oi 
    ON oi.id = uo.offering_id
LEFT JOIN offeringgroup og 
    ON og.id = oi.group_id    
WHERE lat < ? 
  AND lat > ? 
  AND lng < ? 
  AND lng > ? 
  AND showonmap = 1
GROUP BY 
    kl.id, kl.name, u.id, u.firstname, u.lastname, u.avatar, lat, lng;
    ";

// SELECT kl.name, CONCAT(u.firstname, ' ', u.lastname) AS NAME, 'partner', u.id, u.avatar, JSON_ARRAYAGG(role.name), '' AS keywords, lat, lng, 'blue' color, '#user' as reference, null, null
// FROM kloko_location kl, kloko_user_location ul, user u, user_role, role
// WHERE lat < ? AND lat > ? AND lng < ? AND lng > ? AND ul.user_id = u.id AND kl.id = ul.location_id AND showonmap = 1
// AND user_role.user_id = u.id AND user_role.role_id = role.id
// ";

$result = PrepareExecSQL($query, 'dddddddd', [$biggestlat, $smallestlat, $biggestlng, $smallestlng, $biggestlat, $smallestlat, $biggestlng, $smallestlng]);

// Filter results for valid numerical IDs
$data = array_filter($result, function($item) {
    return isset($item['id']) && is_numeric($item['id']);
});
foreach ($data as &$item) {
    // Decode offerings
    if (isset($item['offerings'])) {
        $decoded = json_decode($item['offerings'], true);
        // If decoded is not an array or is [null], set to []
        if (!is_array($decoded) || (count($decoded) === 1 && is_null($decoded[0]))) {
            $item['offerings'] = [];
        } else {
            $item['offerings'] = $decoded;
        }
    }
    // Decode subcategory
    if (isset($item['subcategory'])) {
        $decoded = json_decode($item['subcategory'], true);
        $item['subcategory'] = is_array($decoded) ? $decoded : [];
    }
}
unset($item);

header('Content-Type: application/json');
echo json_encode($data);
?>
