<?php

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	header("Access-Control-Allow-Origin: *");
	header('Access-Control-Allow-Methods: POST, GET, PUT, OPTIONS');
	header("Access-Control-Allow-Headers: authorization, token, app_id, deviceid, Info, Origin, X-Requested-With, Content-Type, Accept");
	// header('Access-Control-Allow-Headers: token, Content-Type');
	header('Access-Control-Max-Age: 0');
	header('Content-Length: 0');
	header('Content-Type: application/json');
	die("OPTIONS");
} else {
	header("Access-Control-Allow-Origin: *");
	header('Access-Control-Max-Age: 86400'); // cache for 1 day
	header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, OPTIONS');
	header("Access-Control-Allow-Headers: token, deviceid, X-Requested-With, Content-Type, Origin, Cache-Control, Pragma, authorization, Authorization, Accept, Accept-Encoding, app_id");
	header('Access-Control-Allow-Credentials: true');
}