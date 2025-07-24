<?php

die();

include_once dirname(__FILE__) . "/sendemail.php";
include_once dirname(__FILE__) . "/template.php";

$appid = $headers['app_id'] ?? null;

$headers = getallheaders();
$appid = isset($headers['app_id']) ? $headers['app_id'] : null;

$templateName = "ForgotPassword";
$toEmail = 'cairnswm@gmail.com';

// die("DIE");

$email = render($appid, $templateName, 
  ["color" => "purple", "name" => "Juzt.Dance", "reset_url" => "http://RESET URL goes here/", "dev_reset_url" => "http://DEV RESET URL goes here/","current_year"=>"2025"]);

  $subject = $email["subject"];
  $htmlContent = $email["body"];

$result = sendEmail($appid, $toEmail, $subject, $htmlContent);


http_response_code(200);
echo json_encode($result);