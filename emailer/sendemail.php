<?php
include_once dirname(__FILE__) . "/../settings/settingsfunctions.php";

function sendEmail($appid, $toEmail, $subject, $htmlContent) {
    $apiUrl = 'https://api.resend.com/emails';
    $apiKey = getSettingOrSecret($appid, 'resend_apikey');
    $fromEmail = getSettingOrSecret($appid, 'resend_sender');

    // echo "Sending email with Resend API: From: $fromEmail, To: $toEmail, Subject: $subject, Content: $htmlContent";

    if ($apiKey == "" || $fromEmail == "") {
        return ['response' => 'API key or sender not found', 'http_code' => 401];
    }

    $data = [
        'from' => $fromEmail,
        'to' => [$toEmail],
        'subject' => $subject,
        'html' => $htmlContent
    ];

    $jsonData = json_encode($data);

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

    // var_dump($response);

    try {
        // Write the details to table email_log
        $sql = "INSERT INTO email_log (app_id, email, subject, body, ref_id) VALUES (?, ?, ?, ?, ?)";
        $refId = isset($decodedResponse['id']) ? $decodedResponse['id'] : null;
        $params = [$appid, json_encode($toEmail), $subject, $htmlContent, $refId];
        PrepareExecSQL($sql, "sssss", $params);
    } catch (Exception $e) {
        // Do nothing
        $response = 'Error logging email: ' . $e->getMessage();
    }

    return ['response' => $decodedResponse, 'http_code' => $httpCode];
}
