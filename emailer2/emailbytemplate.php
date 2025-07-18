<?php
require_once 'conn.php';
require_once 'utils.php';
require_once 'template_functions.php';
include_once 'template_render.php';
include_once dirname(__FILE__) . "/sendemail.php";

$to = getParam('to', '');
$app_id = getParam('app_id', '');
$template_name = getParam('template_name', '');
$data_json = getParam('data', '{}');
$lang = getParam('lang', 'en');

if ($app_id == '' || $template_name == '') {
  http_response_code(400);
  echo "Missing required parameters: app_id and template_name are required.";
  exit;
}

if ($to == '') {
  http_response_code(400);
  echo "Missing 'to' parameter.";
  exit;
}

if (is_array($data_json)) {
  $data = $data_json;
} else {
  $data = json_decode($data_json, true);
  if ($data === null)
    $data = [];
}

$rendered_html = render((string) $app_id, $template_name, $data, $lang);

$subject = $rendered_html["subject"];
$htmlContent = $rendered_html["content"];

$toEmail = $to;
$result = sendEmail($app_id, $toEmail, $subject, $htmlContent);
