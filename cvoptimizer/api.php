<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once dirname(__FILE__) . "/../gapiv2/dbconn.php";
include_once dirname(__FILE__) . "/../gapiv2/v2apicore.php";
include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../utils.php";
include_once dirname(__FILE__) . "/../auth/authfunctions.php";

include_once dirname(__FILE__) . "/../gapiv2/gapi.php";

include_once dirname(__FILE__) . "/cvoptimizerconfig.php";
include dirname(__FILE__) . "/cvoptimizer.php";

runAPI($cvoptimizerconfigs);
