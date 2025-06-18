<?php
include_once dirname(__FILE__) . "/sendemail.php";

// Example usage:
$appid = "b0181e17-e5c6-11ee-bb99-1a220d8ac2c9";
$toEmail = 'cairnswm@gmail.com';
$subject = 'New Booking: Love Fest';
$htmlContent = '<div>There is a new booking for the <strong>Love Fest</strong>
  <div>Name: William Cairns</div>
  <div>Email: test@te.st</div>
  <div>Phone: 1234567890</div>
</div>';

$result = sendEmail($appid, $toEmail, $subject, $htmlContent);


http_response_code(200);
echo json_encode($result);