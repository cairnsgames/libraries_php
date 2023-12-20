<?php

include_once "./corsheaders.php";
include_once "./dbutils.php";
include_once "./utils.php";
include_once "./security.config.php";

$appid = getHeader("APP_ID",$appid = getHeader("app_id",$appid = getHeader("App_id","")));
$email = getParam("email", "");
$password = getParam("password", "");

$debugValues = array();
$out = array();
$debug = getParam("debug", false);


// VALIDATIONS
$errors = array();

if ($appid == "" || $appid == "undefined") {
    array_push($errors, array("message" => "app_id header is required."));
}
if ($email == "") {
    array_push($errors, array("message" => "Email is Required."));
}
if ($password == "") {
    array_push($errors, array("message" => "Password is Required."));
}

if (count($errors) > 0) {
    $out = array("errors" => $errors);
    if ($debug) {
    array_push($out, array("debug" => $debugValues));}
    die(json_encode($out));
}

try {
    $sql = "select id, firstname, lastname, avatar, role_id from user where app_id = ? and email = ? and password = ?  ";

    // echo $sql, "\n";
    $password_hash = crypt($password, $PASSWORDHASH);
    $params = array($appid, $email, $password_hash);
    $row = PrepareExecSQL($sql, "sss", $params);

    $params[2] = "######";
    array_push($debugValues, array("selectAuthUser" => array("sql" => $sql, "params" => $params)));

    if (empty($row)) {
        array_push($errors, array("message" => "Invalid Email or Password."));
    } else {

        $firstname = $row[0]["firstname"];
        $lastname = $row[0]["lastname"];
        $avatar = $row[0]["avatar"];
        $profileid = $row[0]["id"];
        $role_id = $row[0]["role_id"];

        $jwt = createToken(
            array("id" => $profileid, "firstname" => $firstname, "lastname" => $lastname, "role" => $role_id)
        );
        $out = 
            array(
                "message" => "Login succeded.",
                "id" => $profileid,
                "email" => $email,
                "firstname" => $firstname,
                "lastname" => $lastname,
                "avatar" => $avatar,
                "token" => $jwt,
                "role" => $role_id,
                "app_id" => $appid
        );
        // TODO: Record the key so that we can use it for future auto-login

        // Save login to database
        $sql = "insert into auth_login (userid,token) values (?,?)";
        $params = array($profileid, $jwt);
        $row = PrepareExecSQL($sql, "ss", $params);
        array_push($debugValues, array("insertAuthLogin" => array("sql" => $sql, "params" => $params)));
    }

} catch (Exception $e) {
    array_push($errors, array("message" => $e->getMessage()));
}

if (count($errors) > 0) {
    $out = array("errors" => $errors);
    if ($debug){
    array_push($out, array("debug" => $debugValues));}
    die(json_encode($out));
}

if ($debug) {array_push($out, array("debug" => $debugValues));}
$res = $out;
die(json_encode($res));