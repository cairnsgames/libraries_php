<?php
include_once dirname(__FILE__)."/../corsheaders.php";
include_once dirname(__FILE__)."/../dbconfig.php";
include_once dirname(__FILE__)."/../apicore/apicore.php";
include_once dirname(__FILE__)."/../utils.php";
include_once dirname(__FILE__)."/../auth/authfunctions.php";

$appid = getAppId();

$config = array(
	"database" => $dbconfig,
	
	"application" => array(
		"key" => "id",
		"select" => ["id","name","description","owner"],
		"create" => ["name","description"],
		"update" => ["name","description"],
		"delete" => true,
		"aftercreate" => "addOwner"
	),
	"user" => array(
		"key" => "id",
		"select" => ["id", "email", "firstname", "lastname"],
		"beforeselect" => "withApp",
	),
	
);

Run($config);

function withApp($config, $info)
{
	global $appid;
	$info["where"] = "app_id=?";
	$info["wheresss"] = "s";
	$info["whereparams"] = [$appid];
	return $info;
}
function securecheck2($info)
{
	requiresAdminRights();
	return $info;
}

?>