<?php

$postdata = retrieveJsonPostData();

function getParam($name, $default = null) {
	global $postdata;
	if (isset($_GET[$name])) { return $_GET[$name]; }
	if (isset($_POST[$name])) { return $_POST[$name]; }
	if (isset($postdata[$name])) { return $postdata[$name]; }
	return $default;
}

function retrieveJsonPostData() {
	// get the raw POST data
	$rawData = file_get_contents("php://input");
	// this returns null if not valid json
	return json_decode($rawData, true);
}

function getHeader($name, $default = "12345") {
    $headers = getallheaders();
    if (isset($headers[$name])) {
        return $headers[$name];
    } else {
        return $default;
    }
}

?>