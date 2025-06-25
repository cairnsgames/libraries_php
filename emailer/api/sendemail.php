<?php
include_once dirname(__FILE__) . "/../sendemail.php";
include_once dirname(__FILE__) . "/../template.php";

$appid = $headers['app_id'] ?? null;

$headers = getallheaders();
$appid = isset($headers['app_id']) ? $headers['app_id'] : null;

// Read JSON POST body
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid JSON in request body.']);
  exit;
}
$templateName = $input['templateName'] ?? '';
$toEmail = $input['toEmail'] ?? '';

// var_dump($input);
// echo "templateName: $templateName\n";
// echo "toEmail: $toEmail\n";

if (empty($templateName) || empty($toEmail)) {
  http_response_code(400);
  echo json_encode(['error' => 'templateName and toEmail are required.']);
  exit;
}

try {
  $email = renderEmailTemplate($appid, $templateName, $input);
  if (!$email || !isset($email["subject"], $email["body"])) {
    throw new Exception("Invalid email template output");
  }
} catch (Exception $e) {
  http_response_code(200);
  echo json_encode(['result' => 'no email to send']);
  exit;
}

$subject = $email["subject"];
$htmlContent = $email["body"];


$result = sendEmail($appid, $toEmail, $subject, $htmlContent);

http_response_code(200);
echo json_encode($result);