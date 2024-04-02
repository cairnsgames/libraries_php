<?php

include_once dirname(__FILE__)."/../dbutils.php";
include_once dirname(__FILE__)."/../utils.php";

if (isset($_SERVER['PATH_INFO'])) {
	$request = explode('/', trim($_SERVER['PATH_INFO'], '/'));
} else {
	$request = [];
}

$image_id = end($request);

if (isset($image_id)) {
	$sql = "select url from content where id = ?";
	$params = array($image_id);
	$result = PrepareExecSQL($sql, "s", $params);
	if (empty($result)) {
		die("Image not found!");
	} else {
		$image = "c:/xampp/htdocs/files/".$result[0]["url"];
		readfile($image);
	}
} else {
	$out["error"] = "All parameters are required!";
}
http_response_code(200);
