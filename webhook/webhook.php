<?php

include_once "../gapiv2/dbconn.php";

function generateWebhookGuidKey($app_id, $purpose, $id) {
  $timestamp = time();
  $section1 = base_convert($timestamp, 10, 36);
  $section2 = bin2hex(random_bytes(4));
  $section3 = strrev($section2 . $section1 . $id);
  $checksum = crc32($section1 . $section2 . $section3) % 10000;
  $section4 = str_pad($checksum, 4, "0", STR_PAD_LEFT);
  return strtoupper("{$section1}-{$section2}-{$section3}-{$section4}");
}

function reverseWebhookGuidKey($guid_key) {
  $parts = explode("-", $guid_key);
  if (count($parts) !== 4) return false;

  [$section1, $section2, $section3, $section4] = $parts;
  $expected_checksum = crc32($section1 . $section2 . $section3) % 10000;
  if ((int)$section4 !== $expected_checksum) return false;

  $reversed = strrev($section3);
  $section1_check = substr($reversed, strlen($section2), strlen($section1));
  $id = substr($reversed, strlen($section2 . $section1));

  if ($section1_check !== $section1) return false;

  $timestamp = base_convert($section1, 36, 10);

  return [
      'timestamp' => (int)$timestamp,
      'random' => $section2,
      'id' => $id
  ];
}

function triggerWebhook($app_id, $purpose, $id, $payload) {
  // 1. Fetch the active webhook
  $sql = "SELECT id, url FROM webhooks WHERE app_id = ? AND purpose = ? AND is_active = 1 LIMIT 1";
  $result = executeSQL($sql, [$app_id, $purpose]);
  if (count($result) === 0) return false;

  $webhook = $result[0];

  // 2. Generate the webhook GUID key
  $key_id = $result[0]["id"]."-".$id;
  $guid_key = generateWebhookGuidKey($app_id, $purpose, $key_id);

  // 3. Store the webhook key
  $insert_sql = "INSERT INTO webhook_keys (app_id, purpose, guid_key, raw_data) VALUES (?, ?, ?, ?)";
  $insert_data = [$app_id, $purpose, $guid_key, json_encode(['id' => $key_id])];
  executeSQL($insert_sql, $insert_data);

  // 4. Add webhook key to payload
  $payload['webhook_key'] = $guid_key;

  // 5. Send HTTP POST
  $ch = curl_init($webhook['url']);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
      'X-App-Id: ' . $app_id,
      'X-Webhook-Purpose: ' . $purpose
  ]);
  $response = curl_exec($ch);
  $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  // 6. Log the webhook call
  $log_sql = "INSERT INTO webhook_logs (webhook_id, status_code, url, request_payload, response_body) VALUES (?, ?, ?, ?, ?)";
  $log_params = [$webhook['id'], $http_code, $webhook['url'], json_encode($payload), $response];
  executeSQL($log_sql, $log_params);

  return [
      'status' => $http_code,
      'response' => $response
  ];
}
