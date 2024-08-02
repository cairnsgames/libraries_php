<?php

include_once dirname(__FILE__) . "/breezoconfig.php";
include dirname(__FILE__) . "/breezo.php";

$id = getParam("id", null);

if (empty($id)) {
    sendBadRequestResponse("Invalid cart id");
}

convertCartToOrder($id);