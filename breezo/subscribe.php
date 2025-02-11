<?php

include_once dirname(__FILE__) . "/breezoconfig.php";
include dirname(__FILE__) . "/breezo.php";

$app_id = getHeader("app_id");
$option = getParam("option", null);
$price = getParam("price", null);

if(empty($app_id)) {
    sendBadRequestResponse("Invalid app id");
}

if(empty($option)) {
    sendBadRequestResponse("Invalid option");
}

if(empty($price)) {
    sendBadRequestResponse("Invalid price");
}

$order = subscribeOrder($app_id, $option, $price);
header("Content-Type: application/json");
echo json_encode($order);
