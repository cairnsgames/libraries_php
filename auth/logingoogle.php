<?php

include_once "./corsheaders.php";
include_once "./dbutils.php";
include_once "./utils.php";
include_once "./security.config.php";

$appid = getHeader("APP_ID");
$email = getParam("email", "");
$password = getParam("password", "");

$errors = array();

if ($email == "") {
    array_push($errors, array("message" => "Email is Required."));
}

if (count($errors) > 0) {
    die(json_encode(array("errors" => $errors)));
}

// echo crypt("123", $PASSWORDHASH), "\n";
// echo crypt("123456", $PASSWORDHASH), "\n";
try {
    $sql = "select id, firstname, lastname, avatar, role_id from user where app_id = ? and email = ? ";

    // echo $sql, "\n";
    
    $params = array($appid, $email);
    // var_dump($params);
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

    $jwt = createToken(
        array("id" => $profileid, "firstname" => $firstname, "lastname" => $lastname, "role" => $role_id)
    );
    $res = array(
            "message" => "Login succeded.",
            "id" => $profileid,
            "email" => $email,
            "firstname" => $firstname,
            "lastname" => $lastname,
            "avatar" => $avatar,
            "token" => $jwt,
            "role" => $role_id
    );

    // Save login to database
    $sql = "insert into auth_login (userid,token) values (?,?)";
    $params = array($profileid, $jwt);
    $row = PrepareExecSQL($sql, "ss", $params);


} catch (Exception $e) {
    array_push($errors, array("message" => $e->getMessage()));
}

if (count($errors) > 0) {
    die(json_encode(array("errors" => $errors)));
}

die(json_encode($res));