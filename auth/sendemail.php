<?php
include_once "./config.php";

$config = useConfig();

function sendEMail($to, $subject, $message)
{
    global $config;
    $fromname = $config["fromname"];
    $fromemail = $config["fromemail"];

    $headers = "From: $fromname <$fromemail>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    mail($to, $subject, $message, $headers);
}