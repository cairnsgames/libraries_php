<?php

function sendEmailViaApi($app_id, $toEmail, $templateName, $params = []) {
  $url = 'http://cairnsgames.co.za/php/emailer/api/sendemail.php';

  // Prepare POST data (without app_id)
  $postData = [
    'toEmail' => $toEmail,
    'templateName' => $templateName,
    'params' => json_encode($params)
  ];

  $headers = [
    'App_id: ' . $app_id
  ];

  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

  $response = curl_exec($ch);

  if (curl_errno($ch)) {
    $error = curl_error($ch);
    curl_close($ch);
    return ['success' => false, 'error' => $error];
  }

  curl_close($ch);

  return ['success' => true, 'response' => $response];
}