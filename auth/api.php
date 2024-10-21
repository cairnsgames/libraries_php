<?php

include_once dirname(__FILE__) . "/../gapiv2/dbconn.php";
include_once dirname(__FILE__) . "/../gapiv2/v2apicore.php";

include_once dirname(__FILE__) . "/userconfigs.php";
include_once dirname(__FILE__) . "/userinfo.php";

$appId = getAppId();
$debugValues = [];
$out = [];

runAPI($userconfigs);
