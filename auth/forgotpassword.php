<?php

include_once dirname(__FILE__) . "/../dbutils.php";
include_once dirname(__FILE__) . "/../utils.php";
include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../security/security.config.php";
include_once dirname(__FILE__) . "/authfunctions.php";
include_once dirname(__FILE__) . "/../emailer/sendemail.php";
include_once dirname(__FILE__) . "/../tenant/gettenant.php";
include_once dirname(__FILE__) . "/../permissions/permissionfunctions.php";

$email = '';
$conn = null;
$res = "";
$errors = array();
$deviceid = getParam("deviceid", "");

$appid = getAppId();
$email = getParam("email", "");

if ($email == "") {
    array_push($errors, array("message" => "Email is Required."));
    $canRegister = false;
} else
    try {
        // Check if email exists
        $sql = "SELECT * FROM user WHERE app_id = ? and email = ?";
        $params = [$appid, $email];
        $row = PrepareExecSQL($sql, "ss", $params);
        if ($row[0]["id"] == 0) {
            throw new Exception('EMail does not exist.');
        }
        $password = randomPassword(12);
        $key = randomPassword(200);

        $sql = "UPDATE user SET password = ? where email = ?";

        $password_hash = crypt($password, $PASSWORDHASH);
        $params = array($password_hash, $email);
        $id = PrepareExecSQL($sql, "ss", $params);

        $ipaddress = $_SERVER['REMOTE_ADDR'];
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            $forwarded_for = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $forwarded_for = "";
        }

        $deviceid = getParam("deviceid", "");

        $sql = "insert into auth_forgot SET app_id = ?, user_id = ?, email = ?, newpasswordhash = ?, 
          forcekey = ?, ip_address = ?, forwarded_for = ?, device_id = ?";
        $params = array($appid, $row[0]["id"], $email, $password_hash, $key, $ipaddress, $forwarded_for, $deviceid);
        $id = PrepareExecSQL($sql, "ssssssss", $params);

        if ($id > 0) {

            $tenant = getTenant($appid);
            $homeurl = getProperty("url", null);
            $color = $tenant["color"] ?? "black";
            $name = $tenant["name"] ?? "Juzt Dance";
            $templateName = "ChangePassword";

            // echo "Sending email to $email<br>";
            // echo "Template: $templateName<br>";
            // echo "Color: $color<br>";
            // echo "Name: $name<br>";
            // echo "Home URL: $homeurl<br>";
            // echo "Reset URL: " . $homeurl . '#reset?code=' . $key . "<br>";

            $template = renderEmailTemplate(
                $appid,
                $templateName,
                ["color" => $color, "name" => $name, 
                "reset_url" => $homeurl . '#reset?code=' . $key, 
                "dev_reset_url" => "http://localhost:3000#reset?code=" . $key]
            );

            // echo "template: " . json_encode($template) . "<br>";

            $subject = $template["subject"];
            $htmlContent = $template["body"];

            sendEmailWithSendGrid($appid, $email, "Reset password for " . $name, $htmlContent);
            http_response_code(200);
            $res = json_encode(array("message" => "Change password link was sent.", "token" => $key));
        } else {
            array_push($errors, array("message" => "Could not reset password."));
            array_push($errors, array("dberror" => lastError()));
        }
    } catch (Exception $e) {
        array_push($errors, array("message" => $e->getMessage()));
    }

if (count($errors) > 0) {
    die(json_encode(array("errors" => $errors)));
}

echo $res;