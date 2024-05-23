<?php

// Function to get parameter from query string or post data
function getParam($name, $default = "")
{
	global $postdata;
	if (isset($_GET[$name])) {
		return $_GET[$name];
	}
	if (isset($_POST[$name])) {
        if (is_array($_POST[$name])) {
            return json_encode($_POST[$name]);
        }
		return $_POST[$name];
	}
	if (isset($postdata[$name])) {
        if (is_array($postdata[$name])) {
            return json_encode($postdata[$name]);
        }
		return $postdata[$name];
	}
	$headers = getallheaders();
	if (isset($headers[$name])) {
		return $headers[$name];
	}
	return $default;
}

// Function to get a value from the header
function getHeader($key)
{
    $headers = getallheaders();
    if (isset($headers[$key])) {
        return $headers[$key];
    }
    return null;
}

function getAppId()
{
    $appId = getHeader("app_id");
    if (!isset($appId)) {
        $appId = getHeader("App_id");
    }
    if (!isset($appId)) {
        $appId = getHeader("APP_ID");
    }
    return $appId;
}

function retrieveJsonPostData()
{
	// get the raw POST data
	$rawData = file_get_contents("php://input");
	// this returns null if not valid json
	return json_decode($rawData, true);
}

try {
	$postdata = retrieveJsonPostData();
} catch (exception $e) {
	return $e;
}