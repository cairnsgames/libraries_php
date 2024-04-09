<?php

function getHeader($name, $default = "12345")
{
	$headers = getallheaders();
	if (isset($headers[$name])) {
		return $headers[$name];
	} else {
		return $default;
	}
}

function getParam($name, $default)
{
	global $postdata;
	if (isset($_GET[$name])) {
		return $_GET[$name];
	}
	if (isset($_POST[$name])) {
		return $_POST[$name];
	}
	if (isset($postdata[$name])) {
		return $postdata[$name];
	}
	$headers = getallheaders();
	if (isset($headers[$name])) {
		return $headers[$name];
	}
	return $default;
}

function getAppId()
{
	$appid = getHeader("APP_ID", "");
	if ($appid == "") {
		$appid = getHeader("app_id", "");
	}
	if ($appid == "") {
		$appid = getHeader("App_id", "");
	}
	return $appid;
}
function getToken()
{
	$token = getHeader("token", "");
	return $token;
}
function retrieveJsonPostData()
{
	// get the raw POST data
	$rawData = file_get_contents("php://input");
	// this returns null if not valid json
	return json_decode($rawData, true);
}

function orNull($value, $default = "")
{
	if ($value == null) {
		return $default;
	}
	return $value;
}

function my_json_decode($s)
{
	$s = str_replace(
		array('"', "'"),
		array('\"', '"'),
		$s
	);
	$s = preg_replace('/(\w+):/i', '"\1":', $s);
	return json_decode($s);
}

function randomString($len = 12)
{
	$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
	$pass = array(); //remember to declare $pass as an array
	$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
	for ($i = 0; $i < $len; $i++) {
		$n = rand(0, $alphaLength);
		$pass[] = $alphabet[$n];
	}
	return implode($pass); //turn the array into a string
}

function isLocalHost()
{
	if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1') {
		return true;
	}
	return false;
}

try {
	$postdata = retrieveJsonPostData();
} catch (exception $e) {
	return $e;
}
