<?php

include_once dirname(__FILE__) . "/../dbconfig.php";
include_once dirname(__FILE__) . "/../dbutils.php";
include_once dirname(__FILE__)."/../settings/settingsfunctions.php";


function getEmailTemplate($app_id, $name)
{
    $sql = "SELECT subject, body FROM application_email_templates 
            WHERE app_id = ? AND name = ? LIMIT 1";
    $params = array($app_id, $name);
    $rows = PrepareExecSQL($sql, "ss", $params);

    if (count($rows) > 0) {
        return [
            "subject" => $rows[0]["subject"],
            "body" => $rows[0]["body"]
        ];
    }

    return ["subject" => "", "body" => ""];
}

function renderEmailTemplate($appId, $templateName, $params) {
    // 1. Load template from DB
    $template = getEmailTemplate($appId, $templateName);
    $subject = $template["subject"];
    $body = $template["body"];

    // 2. Replace placeholders
    foreach ($params as $key => $value) {
        $placeholder = '{{' . $key . '}}';
        $subject = str_replace($placeholder, $value, $subject);
        $body = str_replace($placeholder, $value, $body);
    }

    return ["subject" => $subject, "body" => $body];
}


?>
