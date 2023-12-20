<?php

include_once "./dbutils.php";
include_once "./security.config.php";
include_once "./sendemail.php";
include_once "./utils.php";
include_once "./corsheaders.php";

$email = '';
$conn = null;
$res = "";
$errors = array();

// TODO: Get application detaisl for chnage password page

$appid = getHeader("APP_ID");
$email = getParam("email");

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

        $sql = "insert into auth_forgot SET app_id = ?, user_id = ?, email = ?, newpasswordhash = ?, forcekey = ?";
        $params = array($appid, $row[0]["id"], $email, $password_hash, $key);
        $id = PrepareExecSQL($sql, "sssss", $params);

        if ($id > 0) {
            //sendEMail($email,"Password reset","Hi ".$row["first_name"]."<br/><br/>Your new password is '".$password."<br/><br/>from<br/>Juzt.Dance");
            sendEMail($email, "Password reset", "Hi <br/><br/>Please follow this link to <a href='https://juzt.dance/#force?force=" . $key . "'>reset your password</a><br/><br/>from<br/>Juzt.Dance");
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