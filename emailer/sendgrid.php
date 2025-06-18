<?php

include_once dirname(__FILE__)."/../settings/settingsfunctions.php";

function sendEmailWithSendGrid($appid, $toEmail, $subject, $htmlContent) {
    $url = 'https://api.sendgrid.com/v3/mail/send';
    $apiKey = getSettingOrSecret($appid, 'sendgrid');
    if ($apiKey == "") {
        return ['response' => 'API key not found', 'http_code' => 401];
    }
    $fromEmail = getSettingOrSecret($appid, 'SendGrid-fromAddress');

    $data = [
        'personalizations' => [
            [
                'to' => [
                    ['email' => $toEmail]
                ]
            ]
        ],
        'from' => ['email' => $fromEmail],
        'subject' => $subject,
        'content' => [
            [
                'type' => 'text/html',
                'value' => $htmlContent
            ]
        ]
    ];

    $jsonData = json_encode($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    try {
        // write the details to table email_log
        $sql = "insert into email_log (app_id,email,subject,body) values (?,?,?,?)";
        $params = array($appid, $toEmail, $subject, $htmlContent);
        PrepareExecSQL($sql, "ssss", $params);
    } catch (Exception $e) {
        // do nothing
    }

    return ['response' => $response, 'http_code' => $httpCode];

}


?>
