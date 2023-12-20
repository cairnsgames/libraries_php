<?php
include_once "./dbutils.php";
include_once "./utils.php";
include_once "security.config.php";
include_once "corsheaders.php";

// TODO: Send welcome email

$firstName = '';
$lastName = '';
$email = '';
$password = '';
$confirm = "";
$appid = "";
$conn = null;
$refer = "";
$rtype = "";
$accountlevel = 0;
$promo = "";

$res = "";
$errors = array();

$appid = getHeader("APP_ID");

if (!isset($appid)) {
    http_response_code(400);
    echo "Error: App ID is required";
    exit;
}

$firstName = getParam("firstName");
$lastName = getParam("lastName");
$email = getParam("email");
$password = getParam("password");
$confirm = getParam("confirm");

echo "FIRST NAME: ", $firstName, "\n";
echo "LAST NAME: ", $lastName, "\n";
echo "EMAIL: ", $email, "\n";
echo "PASSWORD: ", $password, "\n";
echo "CONFIRM: ", $confirm, "\n";

$canRegister = true;

if ($firstName == "" || $lastName == "") {
    array_push($errors, array("message" => "First name and last name are both Required."));
    $canRegister = false;
}
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
        $sql = "SELECT id FROM user WHERE email = ?";
        $params = array($email);
        $row = PrepareExecSQL($sql, "s", $params);
        if (count($row) > 0 && $row[0]["id"] > 0) {
            throw new Exception('EMail has already been registered.');
        }

        $code = randomPassword(8);
        $sql = "INSERT INTO user SET app_id = ?, firstname = ?, lastname = ?, email = ?, password = ?";

        $password_hash = crypt($password, $PASSWORDHASH);
        $params = array($appid, $firstName, $lastName, $email, $password_hash);
        $id = PrepareExecSQL($sql, "sssss", $params);
    }
} catch (Exception $e) {
    array_push($errors, array("message" => $e->getMessage()));
}

if (count($errors) > 0) {
    die(json_encode(array("errors" => $errors)));
}

die(json_encode($res));