<?php

include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../dbutils.php";
include_once dirname(__FILE__) . "/../utils.php";
include_once dirname(__FILE__) . "/../security/security.config.php";
include_once dirname(__FILE__) . "/authfunctions.php";

$appid = getAppId();
$email = getParam("email", "");
$password = getParam("password", "");
$deviceid = getParam("deviceid", "");
$debugValues = [];

$errors = array();

if ($email == "") {
    array_push($errors, array("message" => "Email is Required."));
}

if (count($errors) > 0) {
    die(json_encode(array("errors" => $errors)));
}

try {
    $sql = "select id, firstname, lastname, avatar, role_id from user where app_id = ? and email = ? ";

    $params = array($appid, $email);
    $row = PrepareExecSQL($sql, "ss", $params);

    if (empty($row)) {
        // CREATE NEW USER
        $firstname = getParam("firstname", "");
        $lastname = getParam("lastname", "");
        $avatar = getParam("avatar", "");
        $googleid = getParam("googleid", "");
        $sql = "INSERT INTO user SET app_id = ?, firstname = ?, lastname = ?, email = ?, password = ?, avatar = ?";

        $password_hash = crypt($password, $PASSWORDHASH);
        $params = array($appid, $firstname, $lastname, $email, "google user", $avatar);
        $id = PrepareExecSQL($sql, "ssssss", $params);

        $sql = "INSERT INTO user_property SET user_id = ?, name = ?, value = ?";

        $params = array($id, "google_id", $googleid);
        $id = PrepareExecSQL($sql, "sss", $params);

        $sql = "select id, firstname, lastname, avatar, role_id from user where app_id = ? and email = ? ";
        $params = array($appid, $email);
        $row = PrepareExecSQL($sql, "ss", $params);
    }

    $firstname = $row[0]["firstname"];
    $lastname = $row[0]["lastname"];
    $avatar = $row[0]["avatar"];
    $profileid = $row[0]["id"];
    $role_id = $row[0]["role_id"];

    $permissions = getUserPermissions($profileid, $appid);
    $jwt = getTokenForUser(id: $profileid, appid: $appid, permissions: $permissions);

    $res = array(
        "app_id" => $appid,
        "avatar" => $avatar,
        "email" => $email,
        "firstname" => $firstname,
        "id" => $profileid,
        "lastname" => $lastname,
        "message" => "Login succeded.",
        "permissions" => $permissions,
        "role" => $role_id,
        "token" => $jwt
    );

    // Save login to database
    $ipaddress = $_SERVER['REMOTE_ADDR'];
    if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
        $forwarded_for = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $forwarded_for = "";
    }
    $sql = "insert into auth_login (userid, token, ip_address, forwarded_for, device_id) values (?,?,?,?,?)";
    $params = array($profileid, $jwt, $ipaddress, $forwarded_for, $deviceid);
    $row = PrepareExecSQL($sql, "sssss", $params);

} catch (Exception $e) {
    array_push($errors, array("message" => $e->getMessage()));
}

if (count($errors) > 0) {
    die(json_encode(array("errors" => $errors)));
}

die(json_encode($res));