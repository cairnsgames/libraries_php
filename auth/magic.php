<?php
include_once dirname(__FILE__) . "/../dbutils.php";
include_once dirname(__FILE__) . "/../utils.php";
include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../security/security.config.php";
include_once dirname(__FILE__) . "/authfunctions.php";
include_once dirname(__FILE__) . "/../emailer/sendemail.php";



$appid = getAppId();
$email = getParam("email", "");
$deviceid = getParam("deviceid", "");

$errors = [];
$out = [];

http_response_code(200);

if ($email == "") {
    array_push($errors, array("message" => "Email is Required."));
}

if (count($errors) == 0) {
    try {
        $magiccode = createMagicLink($email, $appid, $deviceid, $_SERVER['REMOTE_ADDR']);
        $out["magiccode"] = $magiccode;

        $htmlContent = '<div>Welcome to <strong style="color:purple">Juzt.Dance</strong>
  <div>Click on this link to access the system</div>
  <div><a href="http://juzt.dance#magic?code='.$magiccode.'">Login</a></div>
  <div>DEVELOPER: <a href="http://localhost:3000#magic?code='.$magiccode.'">Login to DEV</a></div>
</div>';

        sendEmailWithSendGrid($appid, $email, "Login to Juzt.Dance", $htmlContent);

    } catch (Exception $e) {
        array_push($errors, array("message" => $e->getMessage()));
    }
}

if (count($errors) > 0) {
    http_response_code(404);
    $out["errors"] = $errors;
}

die(json_encode($out));