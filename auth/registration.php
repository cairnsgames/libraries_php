<?php
include_once dirname(__FILE__)."/../dbutils.php";
include_once dirname(__FILE__)."/../utils.php";
include_once dirname(__FILE__)."/../security/security.config.php";
include_once dirname(__FILE__)."/../corsheaders.php";
include_once dirname(__FILE__)."/authfunctions.php";

// TODO: Send welcome email

$out = [];
$debugValues = [];
$errors = array();

$appid = getAppId();

if ($appid == "NONE") {
    throw new Exception("APP_ID is not set");
}

if (!isset($appid)) {
    http_response_code(400);
    echo "Error: App ID is required";
    exit;
}

$email = getParam("email","");
$password = getParam("password","");
$confirm = getParam("confirm","");
$deviceid = getParam("deviceid", "");

$canRegister = true;

if ($email == "") {
    array_push($errors, array("message" => "Email is Required."));
    $canRegister = false;
}
if ($confirm == "" || $confirm != $password) {
    array_push($errors, array("message" => "Passwords do not match."));
    $canRegister = false;
}

try {
    if ($canRegister) {
        // Check if email exists
        $sql = "SELECT id FROM user WHERE email = ? and app_id = ?";
        $params = array($email, $appid);
        $row = PrepareExecSQL($sql, "ss", $params);
        if (count($row) > 0 && $row[0]["id"] > 0) {
            throw new Exception('EMail has already been registered.');
        }

        $code = randomPassword(8);
        $sql = "INSERT INTO user SET app_id = ?, email = ?, password = ?";

        $password_hash = crypt($password, $PASSWORDHASH);
        $params = array($appid, $email, $password_hash);
        $id = PrepareExecSQL($sql, "sss", $params);

        // TODO send verification email
    }
} catch (Exception $e) {
    array_push($errors, array("message" => $e->getMessage()));
}

getLoginToken($email, $password, $appid);

if (count($errors) > 0) {
    die(json_encode(array("errors" => $errors)));
}

die(json_encode($out));