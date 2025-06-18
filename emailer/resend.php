<?php
include_once dirname(__FILE__) . "/sendemail.php";

$appid = $headers['app_id'] ?? null;

$headers = getallheaders();
$appid = isset($headers['app_id']) ? $headers['app_id'] : null;

$apiUrl = 'https://api.resend.com/emails';
$to = 'cairnswm@gmail.com';
$subject = 'Testing Mail';
$html = '<p>it works!</p>';

$response = sendEmail($appid, $to, $subject, $html);
echo json_encode($response);