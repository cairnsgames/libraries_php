<?php

// $fields = "token, X-Requested-With, Content-Type, Origin, Cache-Control, Pragma, Authorization, Accept, Accept-Encoding, APP_ID";
// $fields = "APP_ID, app_id";
// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
// 	header("Access-Control-Allow-Origin: *");
// 	header('Access-Control-Allow-Methods: POST, GET, PUT');
// 	header("Access-Control-Allow-Headers: ".$fields);
// 	header('Access-Control-Max-Age: 0');
// 	header('Content-Length: 0');
// 	header('Content-Type: application/json');
// } 

// else {
// 	header("Access-Control-Allow-Origin: *");
// 	header('Access-Control-Max-Age: 86400'); // cache for 1 day
// 	header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, OPTIONS');
// 	header("Access-Control-Allow-Headers: token, X-Requested-With, Content-Type, Origin, Cache-Control, Pragma, Authorization, Accept, Accept-Encoding, APP_ID, App_id, app_id");
// 	header('Access-Control-Allow-Credentials: true');
// }

header("Access-Control-Allow-Origin: *");
header('Access-Control-Max-Age: 86400'); // cache for 1 day
header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, OPTIONS');
header("Access-Control-Allow-Headers: token, X-Requested-With, Content-Type, Origin, Cache-Control, Pragma, Authorization, Accept, Accept-Encoding, APP_ID, App_id, app_id");
header('Access-Control-Allow-Credentials: true');