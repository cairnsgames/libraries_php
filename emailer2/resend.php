<?php
include_once dirname(__FILE__) . "/sendemail.php";

die();

$appid = $headers['app_id'] ?? null;

$headers = getallheaders();
$appid = isset($headers['app_id']) ? $headers['app_id'] : null;

$apiUrl = 'https://api.resend.com/emails';
$to = 'cairnswm@gmail.com';
$subject = 'Testing Mail';
$html = '<div style="background-color: purple; color: white; padding: 10px; font-family: Arial, sans-serif;">
  <h1>Juzt.Dance</h1>
</div>

<p>Olá John Doe,</p>

<p>Obrigado por se juntar ao <strong>Juzt.Dance</strong>, a sua plataforma de confiança para tudo relacionado com Dança. Estamos entusiasmados por tê-lo conosco!</p>

<p>Explore os próximos eventos e comece a gerir os seus hoje mesmo.</p>

<div style="background-color: pink; color: white; padding: 10px; font-family: Arial, sans-serif; font-size: 12px;">
  <p>Contacte-nos a qualquer momento pelo email support@juzt.dance.</p>
  <p>Johanneburg, South Africa</p>
  <p><a href="https://juzt.dance" style="color: white;">Visite o nosso site</a></p>
</div>';

$response = sendEmail($appid, $to, $subject, $html);
echo json_encode($response);