<?php

include_once dirname(__FILE__)."/../dbutils.php";
include_once dirname(__FILE__)."/../utils.php";

$appid = getAppId();
$items = getParam("items", []);
$type = getParam("type", "");
$user_id = getParam("user_id", "");
$id = getParam("id", "");
$out = ["processed" => 0];

if (!empty($items)) {
    foreach ($items as $item) {
        $itemtype = $item["type"] ?? "";
        $user_id = $item["user_id"] ?? "";
        $itemid = $item["id"] ?? "";

        if ($itemtype != "" && $itemid != "" && $user_id != "") {
            $sql = "insert into itemseen (app_id, itemtype, user_id, item_id) values (?,?,?,?)
              on duplicate key update seennumber = IF(seenlast <= DATE_SUB(NOW(), INTERVAL 15 minute), seennumber + 1, seennumber)";
            $params = array($appid, $itemtype, $user_id, $itemid);
            PrepareExecSQL($sql, "ssss", $params);
            $out["processed"]++;
        }
    }
} elseif ($type != "" && $id != "" && $user_id != "") {
    $sql = "insert into itemseen (app_id, itemtype, user_id, item_id) values (?,?,?,?)
      on duplicate key update seennumber = IF(seenlast <= DATE_SUB(NOW(), INTERVAL 15 minute), seennumber + 1, seennumber)";
    $params = array($appid, $type, $user_id, $id);
    PrepareExecSQL($sql, "ssss", $params);
    $out["processed"]++;
} else {
    $out["error"] = "Invalid input!";
}

http_response_code(200);
echo json_encode($out);

?>