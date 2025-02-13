<?php
include_once dirname(__FILE__)."/../corsheaders.php";
include_once dirname(__FILE__)."/../dbconfig.php";
include_once dirname(__FILE__)."/../dbutils.php";
include_once dirname(__FILE__)."/../apicore/apicore.php";
include_once dirname(__FILE__)."/../utils.php";

$appid = getAppId();

if ($appid === "NONE") {
	die("No app_id provided");
} 

$domain = $_SERVER['HTTP_HOST'];

$sql = "SELECT * FROM application_property ap
        WHERE app_id = ? 
        AND (domain = ? OR (domain IS NULL AND NOT EXISTS 
        (
            SELECT 1
            FROM application_property ap2
            WHERE ap.name = ap2.name AND 
            app_id = ? AND domain = ?
        )))";

$settings = PrepareExecSQL($sql, "ssss", [$appid, $domain, $appid, $domain]);
echo json_encode($settings);
