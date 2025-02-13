<?php
include_once dirname(__FILE__)."/../corsheaders.php";
include_once dirname(__FILE__)."/../dbconfig.php";
include_once dirname(__FILE__)."/../dbutils.php";
include_once dirname(__FILE__)."/../apicore/apicore.php";
include_once dirname(__FILE__)."/../utils.php";

function getSecret($appid = null, $domain = null) {
    if ($appid === null) {
        $appid = getAppId();
    }
    
    if ($appid === "NONE") {
        return null;
    }
    
    if ($domain === null) {
        $domain = $_SERVER['HTTP_HOST'];
    }
    
    $sql = "SELECT * FROM application_secret ap
            WHERE app_id = ? 
            AND (domain = ? OR (domain IS NULL AND NOT EXISTS 
            (
                SELECT 1
                FROM application_secret ap2
                WHERE ap.name = ap2.name AND 
                app_id = ? AND domain = ?
            )))";
    
    return PrepareExecSQL($sql, "ssss", [$appid, $domain, $appid, $domain]);
}
