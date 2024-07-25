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

// TODO: Get application detaisl for chnage password page

$appid = getAppId();
$email = getParam("email", "");

if ($email == "") {
    array_push($errors, array("message" => "Email is Required."));
    $canRegister = false;
} else
    try {
        // Check if email exists
        $sql = "SELECT * FROM user WHERE email = ?";
        $params = array($email);
        $row = PrepareExecSQL($sql, "s", $params);
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

            $htmlContent = '<div>Welcome to <strong style="color:' . $color . '">' . $name . '</strong>
                <div>Click on this set a new password</div>
                <div><a href="' . $homeurl . '#reset?code=' . $key . '">Reset Password</a></div>
                <div>DEVELOPER: <a href="http://localhost:3000#reset?code=' . $key . '">Reset Password DEV</a></div>
            </div>';

            sendEmailWithSendGrid($appid, $email, "Reset password for " . $name, $htmlContent);
            //sendEMail($email,"Password reset","Hi ".$row["first_name"]."<br/><br/>Your new password is '".$password."<br/><br/>from<br/>Juzt.Dance");
            // sendEMail($email, "Password reset", "Hi <br/><br/>Please follow this link to <a href='https://juzt.dance/#force?force=" . $key . "'>reset your password</a><br/><br/>from<br/>Juzt.Dance");
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