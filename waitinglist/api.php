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
	"waitinglist" => array(
		"key" => "app_id",
		"tablename" => "app_waiting_list",
		"select" => array("id", "app_id", "user_name", "user_email", "additional_info"),
		"create" => array("app_id", "user_name", "user_email", "additional_info"),
		"update" => false,
		"beforeselect" => "beforeSelectWaitingList",
    "beforeinsert" => "beforeCreateWaitingList",
	)
);

Run($config);

// Define the before method
function beforeSelectWaitingList($info)
{
	global $appid;
	$info["where"] = "app_id=?";
	$info["wheresss"] = "s";
	$info["whereparams"] = [$appid];
	return $info;
}
function beforeCreateWaitingList($info) {
  global $appid;
  // var_dump($info);
  $info["fields"]["app_id"] = $appid;
  return $info;
}

?>
