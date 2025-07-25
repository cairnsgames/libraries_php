<?php
include_once dirname(__FILE__)."/../corsheaders.php";
include_once dirname(__FILE__)."/../dbutils.php";
include_once dirname(__FILE__)."/../utils.php";
include_once dirname(__FILE__)."/../security/security.config.php";
include_once dirname(__FILE__)."/authfunctions.php";
include_once dirname(__FILE__)."/../emailer2/email.php";

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

$firstname = getParam("firstname", "");
$lastname = getParam("lastname", "");
$username = getParam("username", "");
$language = getParam("language", "English");

$canRegister = true;

if ($email == "") {
    array_push($errors, array("message" => "Email is Required."));
    $canRegister = false;
}
if ($confirm == "" || $confirm != $password) {
    array_push($errors, array("message" => "Passwords do not match."));
    $canRegister = false;
}

// Validate username
if ($username == "") {
    $username = null; // Set username to null if not provided
}

// Check if username already exists
if ($canRegister && $username !== null) {
    $sql = "SELECT id FROM user WHERE username = ? and app_id = ?";
    $params = array($username, $appid);
    $row = PrepareExecSQL($sql, "ss", $params);
    if (count($row) > 0 && $row[0]["id"] > 0) {
        array_push($errors, array("message" => "Username has already been taken."));
        $canRegister = false;
    }
}

try {
    if ($canRegister) {
        // Check if email exists
        $sql = "SELECT id FROM user WHERE email = ? and app_id = ?";
        $params = array($email, $appid);
        $row = PrepareExecSQL($sql, "ss", $params);
        if (count($row) > 0 && $row[0]["id"] > 0) {
            throw new Exception('Email has already been registered.');
        }

        $code = randomPassword(8);
        $password_hash = crypt($password, $PASSWORDHASH);

        // Insert user with optional fields
        $sql = "INSERT INTO user SET app_id = ?, email = ?, password = ?, firstname = ?, lastname = ?, username = ?";
        $params = array($appid, $email, $password_hash, $firstname, $lastname, $username);
        $id = PrepareExecSQL($sql, "ssssss", $params);

        // Save language to user_property if set
        if (!empty($language)) {
            $sql = "INSERT INTO user_property SET user_id = ?, name = ?, value = ?";
            $params = array($id, "language", $language);
            PrepareExecSQL($sql, "iss", $params);
        }

        $languageCode = "en";
        switch (strtolower($language)) {
            case "french":
            case "fr":
                $languageCode = "fr";
                break;
            case "portuguese":
            case "pt":
                $languageCode = "pt";
                break;
            case "spanish":
            case "es":
                $languageCode = "es";
                break;
            default:
                $languageCode = "en";
                break;
        }

        // Send verification email
        sendEmailUsingTemplate(
            $email,
            $appid,
            "welcome_email",
            json_encode(array(
                "user_name" => $username,
                "first_name" => $firstname,
                "last_name" => $lastname,
                "language" => $language
            )),
            $languageCode
        );
    }
} catch (Exception $e) {
    array_push($errors, array("message" => $e->getMessage()));
}

getLoginToken($email, $password, $appid);

if (count($errors) > 0) {
    die(json_encode(array("errors" => $errors)));
}

http_response_code(200);
die(json_encode($out));
?>