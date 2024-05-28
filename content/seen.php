<?php
include_once dirname(__FILE__)."/../corsheaders.php";
include_once dirname(__FILE__)."/../dbutils.php";
include_once dirname(__FILE__)."/../utils.php";

$itemid = getParam("id", "");
$itemtype = getParam("type", "");
$ip = $_SERVER['REMOTE_ADDR'];
$out = ["itemtype"=>$itemtype, "itemid"=>$itemid, "ip_address"=>$ip];

if ($itemtype != "" && $itemid != "" && $user_id != "") {
	$sql = "insert into content_seen (itemtype, ip_address, item_id) values (?,?,?)
	  on duplicate key update seennumber = IF(seenlast <= DATE_SUB(NOW(), INTERVAL 15 minute), seennumber + 1, seennumber)";
	$params = array($itemtype, $ip, $itemid);
	$result = PrepareExecSQL($sql, "sss", $params);
	$out["message"] = "content_seen";
	$out["data"] = $result;
} else {
	$out["error"] = "All parameters are required!";
}
http_response_code(200);
echo json_encode($out);

?>