<?php
include_once dirname(__FILE__) . "/../settings/settingsfunctions.php";

function sendEmail($appid, $toEmail, $subject, $htmlContent)
{
    $apiUrl = 'https://api.resend.com/emails';
    $apiKey = getSettingOrSecret($appid, 'resend_apikey');
    $fromEmail = getSettingOrSecret($appid, 'resend_sender');

    // echo "Sending email with Resend API: From: $fromEmail, To: $toEmail, Subject: $subject, Content: $htmlContent";

    if ($apiKey == "" || $fromEmail == "") {
        return ['response' => 'API key or sender not found', 'http_code' => 401];
    }

    $text = mb_convert_encoding($htmlContent, 'UTF-8', 'auto');
    $data = [
        'from' => $fromEmail,
        'to' => [$toEmail],
        'subject' => $subject,
        'html' => $text
    ];

    // Encode with JSON_UNESCAPED_UNICODE and JSON_UNESCAPED_SLASHES to keep chars clean
    $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$apiKey}",
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decodedResponse = json_decode($response, true);

    try {
        // Write the details to table email_log
        $sql = "INSERT INTO email_log (app_id, email, subject, body, ref_id, response) VALUES (?, ?, ?, ?, ?, ?)";
        $refId = isset($decodedResponse['id']) ? $decodedResponse['id'] : null;
        $params = [$appid, json_encode($toEmail), $subject, $htmlContent, $refId, json_encode($decodedResponse)];
        PrepareExecSQL($sql, "ssssss", $params);
    } catch (Exception $e) {
        // Do nothing
        $response = 'Error logging email: ' . $e->getMessage();
        echo "Error logging email: " . $e->getMessage();
    }

    return ['response' => $decodedResponse, 'http_code' => $httpCode];
}
