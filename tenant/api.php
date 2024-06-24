<?php
include_once dirname(__FILE__)."/../corsheaders.php";
include_once dirname(__FILE__)."/../dbconfig.php";
include_once dirname(__FILE__)."/../apicore/apicore.php";
include_once dirname(__FILE__)."/../utils.php";

$appid = getAppId();

if ($appid === "NONE") {
	die("No app_id provided");
} 

$config = array(
	"database" => $dbconfig,
	"tenant" => array(
		"key" => "uuid",
		"tablename" => "application",
		"select" => array("id", "uuid", "name", "description"),
		"create" => false,
		"update" => false,
		"beforeselect" => "beforeSelectApp"
	),
	"params" => array(
		"key" => "id",
		"tablename" => "application_property",
		"select" => array("id", "name", "value"),
		"delete" => false,
		"create" => false,
		"update" => false,
		"beforeselect" => "beforeSelectProperty"
	),
	"secrets" => array(
		"key" => "id",
		"tablename" => "application_secret",
		"select" => array("id", "name"),
		"insert" => array("name", "value"),
		"update" => array("name", "value"),
		"delete" => false,
		"beforeselect" => "beforeSelectProperty"
	)
);

Run($config);

// Define the before and after methods
function beforeSelectApp($info)
{
	global $appid;
	$info["where"] = "uuid=?";
	$info["wheresss"] = "s";
	$info["whereparams"] = [$appid];
	return $info;
}
function beforeSelectProperty($info)
{
	global $appid;
	$info["where"] = "app_id=?";
	$info["wheresss"] = "s";
	$info["whereparams"] = [$appid];
	return $info;
}

?>