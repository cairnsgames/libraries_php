<?php
include_once "jwt.php";
include_once "./dbutils.php";
include_once "./utils.php";

$appid = getHeader("APP_ID");

echo "APP ID:", $appid, "\n";

if (!isset($appid)) {
    http_response_code(400);
    echo "Error: App ID is required";
    exit;
}

// if (isset($headers["Token"])) {
//     $auth = $headers["Token"];
//     $auth = "FOUND";
// } else {
//     $auth = "MISSING";
// }

// echo "Token",   $auth, "\n";

// if (isset($_SERVER["HTTP_TOKEN"])) {
//     $auth = $_SERVER["HTTP_TOKEN"];
//     $auth = "FOUND";
// } else {
//     $auth = "MISSING";
// }

// echo "HTTP_TOKEN",   $auth, "\n";

$jwt = get_jwt_payload($_SERVER["HTTP_TOKEN"]);

var_dump( $jwt);
