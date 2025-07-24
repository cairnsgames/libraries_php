<?php
require_once 'conn.php';
require_once 'utils.php';
require_once 'template_functions.php';
include_once 'template_render.php';
include_once dirname(__FILE__) . "/sendemail.php";

$to = getParam('to', '');
$to_user_id = getParam('to_user_id', '');
$app_id = getParam('app_id', '');
$template_name = getParam('template_name', '');
$data_json = getParam('data', '{}');
$lang = getParam('lang', 'en');
$apikey = getParam('apikey', '');

$sql = "select * from email_apikey where app_id = ? and apikey = ?";
$params = [$app_id, $apikey];
$result = executeSQL($sql, $params);
if (empty($result)) {
    http_response_code(403);
    // echo "app_id:", $app_id, "\n";
    // echo "apikey:", $apikey, "\n";
    // echo json_encode($result);
    // echo "\n";
    echo "Invalid API key or app ID.";
    exit;
}

if ($app_id == '' || $template_name == '') {
  http_response_code(400);
  echo "Missing required parameters: app_id and template_name are required.";
  exit;
}

if ($to_user_id != '') {
  $sql = "SELECT email FROM user WHERE id = ? LIMIT 1";
  $params = [(int)$to_user_id];
  $result = executeSQL($sql, $params);
  if (empty($result)) {
    http_response_code(404);
    echo "User not found.";
    exit;
  }
  $user = $result[0];
  $to = $user['email'];
  // TODO: Set the user values in the data so we can address the user properly in the email
  if (!empty($user['firstname'])) {
    $data["user_name"] = $user['firstname'];
  } elseif (!empty($user['username'])) {
    $data["user_name"] = $user['username'];
  }
  $sql = "SELECT name, value FROM user_property WHERE user_id = ?";
  $params = [(int)$to_user_id];
  $user_properties = executeSQL($sql, $params);

  if (!empty($user_properties)) {
    foreach ($user_properties as $prop) {
      $data[$prop['name']] = $prop['value'];
      if ($prop['name'] === 'language') {
        switch (strtolower($prop['value'])) {
          case 'portuguese':
            $lang = 'pt';
            break;
          case 'french':
            $lang = 'fr';
            break;
          case 'spanish':
            $lang = 'sp';
            break;
          case 'english':
            $lang = 'en';
            break;
        }
      }
    }
  }
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
// $rendered_html["content"] .= json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$subject = $rendered_html["subject"];
$htmlContent = $rendered_html["content"];

$toEmail = $to;
$result = sendEmail($app_id, $toEmail, $subject, $htmlContent);

// var_dump("EMAIL RESULT:",$result);

$response = [
    "success" => false,
    "to" => $toEmail,
    "subject" => $subject,
    "content" => $htmlContent
];

if ($result === true) {
    $response["success"] = true;
    // echo json_encode($response);
    echo "Email sent successfully.";
} else {
    http_response_code(500);
    $response["error"] = is_array($result) && isset($result['response']) ? $result['response'] : "Failed to send email.";
    echo json_encode($response);
}
