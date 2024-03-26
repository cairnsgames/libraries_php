<?php
include_once dirname(__FILE__)."/../corsheaders.php";
include_once dirname(__FILE__)."/../dbconfig.php";
include_once dirname(__FILE__)."/../apicore/apicore.php";
include_once dirname(__FILE__)."/../utils.php";
include_once dirname(__FILE__)."/../auth/authfunctions.php";

$appid = getAppId();

// TODO: Replace $appid in SQL with secured value

$config = array(
	"database" => $dbconfig,
	"mysettings" => array(
		"key" => "id",
		"select" => "SELECT keyname, val FROM settingsoverrides
			WHERE profileid = {id} and app_id = '$appid'
			UNION All
			SELECT keyname, val FROM settings
			WHERE app_id = '$appid'
			  AND NOT EXISTS (SELECT 1 FROM settingsoverrides so2 WHERE settings.keyname = so2.keyname AND profileid = {id})"
	),
	"settings" => array(
		"key" => "id",
		"tablename" => "settings",
		"select" => ["id", "keyname", "val"],
		"create" => ["keyname", "val"],
		"update" => ["keyname", "val"],
		"delete" => true,
		"beforeinsert" => "securecheck",
		"beforeupdate" => "securecheck",
		"beforedelete" => "securecheck"
	),
	"override" => array(
		"key" => "id",
		"tablename" => "settingsoverrides",
		"select" => ["id", "keyname", "val", "profileid", "reason"],
		"create" => ["keyname", "val", "profileid", "reason"],
		"update" => ["keyname", "val", "profileid", "reason"],
		"delete" => true,
		"beforeinsert" => "securecheck",
		"beforeupdate" => "securecheck",
		"beforedelete" => "securecheck"
	),
	"overrides" => array(
		"key" => "id",
		"select" => "SELECT settingsoverrides.id, profileid, name, keyname, val, profileid, reason FROM settingsoverrides, profile where keyname = {id} and profile.id = settingsoverrides.profileid"
	),
);

Run($config);

function securecheck($info)
{
	// requiresAdminRights();
	return $info;
}

?>