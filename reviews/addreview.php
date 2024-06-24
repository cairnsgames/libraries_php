<?php

include_once dirname(__FILE__)."/../corsheaders.php";
include_once dirname(__FILE__)."/../dbconfig.php";
include_once dirname(__FILE__)."/../apicore/apicore.php";
include_once dirname(__FILE__)."/../utils.php";
include_once dirname(__FILE__)."/../auth/authfunctions.php";

$appid = getAppId();
$type = getParam("type","");
$itemid = getParam("itemid","");
$userid = getParam("userid","");
$rating = getParam("rating",0);
$review = getParam("text",getParam("review",""));

$out = [];
$errors = [];

if ($type == "") {
    $errors[] = "Review Item Type not specified ?type=";
}
if ($itemid == "") {
    $errors[] = "Review Item Id not specified ?id=";
}
if ($userid == "") {
    $errors[] = "User doing review not specified";
}
if ($rating == 0) {
    $errors[] = "Star rating not specified";
}
if ($appid == "") {
    $errors[] = "Application Id not defined in headers  APP_ID={guid}";
}

if ($type != "" && $itemid > 0 && $rating > 0 && $userid > 0) {
	$sql = "insert into reviews (app_id, item_type, item_id, user_id, stars, text)
	  values (?,?,?,?,?,?)
	  on duplicate key update stars = ?, text = ?";
	$params = array($appid, $type, $itemid, $userid, $rating, $review, $rating, $review);
    PrepareExecSQL($sql,"ssssssss",$params);
	$out["success"] = true;
	http_response_code(200);
} else {
	$errors[] = "Not all required parameters sent";
}

// TODO: Send email to item_id that a review was added

if (!empty($errors)) {
    $out["error"] = $errors;
    http_response_code(400);
}

echo json_encode($out);

?>