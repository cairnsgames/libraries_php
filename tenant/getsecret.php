<?php
include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../dbconfig.php";
include_once dirname(__FILE__) . "/../dbutils.php";
include_once dirname(__FILE__) . "/../apicore/apicore.php";
include_once dirname(__FILE__) . "/../utils.php";

function getSecret($appid = null, $domain = null)
{
    if ($appid === null) {
        $appid = getAppId();
    }

    if ($appid === "NONE") {
        return null;
    }

    if ($domain === null) {
        if (!empty($_SERVER['HTTP_ORIGIN'])) {
            // Get the domain from the Origin header (best option)
            $clientDomain = parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST);
        } elseif (!empty($_SERVER['HTTP_REFERER'])) {
            // Fallback to the Referer header
            $clientDomain = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
        } else {
            // No origin or referer found
            $clientDomain = 'Unknown';
        }
        $domain = $clientDomain;
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
