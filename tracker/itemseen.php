<?php

include_once dirname(__FILE__)."/../dbutils.php";
include_once dirname(__FILE__)."/../utils.php";

$appid = getAppId();
$user_id = getParam("user_id", "");
$itemid = getParam("id", "");
$itemtype = getParam("type", "");
$out = ["itemtype"=>$itemtype, "itemid"=>$itemid, "user_id"=>$user_id];

if ($itemtype != "" && $itemid != "" && $user_id != "") {
	$sql = "insert into itemseen (app_id, itemtype, user_id, item_id) values (?,?,?,?)
	  on duplicate key update seennumber = IF(seenlast <= DATE_SUB(NOW(), INTERVAL 15 minute), seennumber + 1, seennumber)";
	$params = array($appid, $itemtype, $user_id, $itemid);
	$result = PrepareExecSQL($sql, "ssss", $params);
	$out["message"] = "itemseen";
	$out["data"] = $result;
} else {
	$out["error"] = "All parameters are required!";
}
http_response_code(200);
echo json_encode($out);

?>