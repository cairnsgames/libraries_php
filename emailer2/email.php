<?php
require_once 'conn.php';
require_once '../utils.php';
require_once 'template_functions.php';
include_once 'template_render.php';
include_once dirname(__FILE__) . "/sendemail.php";

function sendEmailUsingTemplate($to, $app_id, $template_name, $data_json = '{}', $lang = 'en') {
    if ($app_id == '' || $template_name == '') {
        throw new Exception("Missing required parameters: app_id and template_name are required.");
    }

    if ($to == '') {
        throw new Exception("Missing 'to' parameter.");
    }

    if (is_array($data_json)) {
        $data = $data_json;
    } else {
        $data = json_decode($data_json, true);
        if ($data === null) {
            $data = [];
        }
    }

    $rendered_html = render((string) $app_id, $template_name, $data, $lang);

    $subject = $rendered_html["subject"];
    $htmlContent = $rendered_html["content"];

    $toEmail = $to;
    $result = sendEmail($app_id, $toEmail, $subject, $htmlContent);

    return $result;
}
