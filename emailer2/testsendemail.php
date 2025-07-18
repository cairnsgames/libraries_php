<?php
include_once dirname(__FILE__) . "/sendemail.php";

// Example usage:
$appid = "b0181e17-e5c6-11ee-bb99-1a220d8ac2c9";
$toEmail = 'cairnswm@gmail.com';
$subject = 'Test Resend';
$htmlContent = '<div>This is a test email</div>';

$result = sendEmail($appid, $toEmail, $subject, $htmlContent);


http_response_code(200);
echo json_encode($result);