<?php
include_once dirname(__FILE__)."/../corsheaders.php";
include_once dirname(__FILE__)."/../dbconfig.php";
include_once dirname(__FILE__)."/../apicore/apicore.php";
include_once dirname(__FILE__)."/../utils.php";
include_once dirname(__FILE__)."/../auth/authfunctions.php";

$appid = getAppId();

$config = array(
	"database" => $dbconfig,
	
	"access" => array(
		"key" => "id",
		"select" => "SELECT name, IF(NEVER>0,'NEVER', if(YES>0,'YES', 'NO')) permission FROM (
			SELECT name, SUM(yes) yes, SUM(NO) no, SUM(NEVER) never FROM (
			SELECT 'Application' role, NAME, if(VALUE=1,1,0) yes, if(VALUE=0,1,0) no, if(VALUE=-1,1,0) never FROM permission
			WHERE app_id = '$appid'
			UNION
			SELECT r.name role, p.name name, if(rp.VALUE=1,1,0) yes, if(rp.VALUE=0,1,0) no, if(rp.VALUE=-1,1,0) NEVER
				FROM permission p, role_permissions rp, role r, user_role ur
			WHERE p.app_id = '$appid'
				AND rp.permission_id = p.id
				AND rp.role_id = r.id
				AND ur.user_id = {id}
				AND ur.role_id = r.id
			UNION
			SELECT 'User' role, p.name name, if(up.VALUE=1,1,0) yes, if(up.VALUE=0,1,0) no, if(up.VALUE=-1,1,0) NEVER 
				FROM permission p, user_permissions up
			WHERE p.app_id = '$appid'
				AND up.permission_id = p.id
				AND up.user_id = {id}
				) t
			GROUP BY NAME) t2"
	),
	"permission" => array(
		"key" => "id",
		"tablename" => "permission",
		"select" => "SELECT id, name, value FROM permission",
		"beforeselect" => "withApp",
	),
	"user" => array(
		"key" => "id",
		"select" => ["id", "email", "firstname", "lastname"],
		"beforeselect" => "withApp",
	),
	"userrole" => array(
		"key" => "id",
		"tablename" => "user_role",
		"select" => "SELECT ur.id, user_id, ur.role_id
		FROM user_role ur, role r
		WHERE ur.role_id = r.id
		  AND app_id = '$appid'",
		"create" => ["app_id", "user_id", "role_id"],
		"update" => ["app_id", "user_id", "role_id"],
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