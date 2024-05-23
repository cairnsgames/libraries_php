<?php
include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../dbconfig.php";
include_once dirname(__FILE__) . "/../apicore/apicore.php";
include_once dirname(__FILE__) . "/../utils.php";

$appid = getAppId();

$config = array(
	"database" => $dbconfig,

	"content" => array(
		"key" => "id",
		"tablename" => "content",
		"select" => array("id", "parent_id", "user_id", "style", "type", "url", "title", "content"),
		"create" => array("parent_id", "app_id", "user_id", "style", "type", "url", "title", "content"),
		"update" => array("parent_id", "app_id", "user_id", "style", "type", "url", "title", "content"),
		"delete" => false,
		"beforeupdate" => "withApp",
		"beforeinsert" => "withApp",
		"beforedelete" => "withApp",
	),
	"zharo" => [
		"key" => "id",
		"select" => "SELECT uuid, type, style, url, title, content, user.firstname, user.lastname
		FROM content, content_share, user 
		WHERE UUID = {id}
		AND content_id = content.id
		AND user_id = user.id"
	]
);

Run($config);

function withApp($info)
{
	global $appid;
	$info["where"] = "app_id=?";
	$info["wheresss"] = "s";
	$info["whereparams"] = [$appid];
	$info["fields"]["app_id"] = $appid;
	return $info;
}

?>