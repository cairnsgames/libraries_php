<?php
include_once "./dbutils.php";
include_once "./utils.php";
include_once "./corsheaders.php";
include_once "./security.config.php";


$appid = getHeader("APP_ID");
$email = getParam("email", "");
$deviceid = getParam("deviceid", "");

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
        $res = json_encode(
            array(
                "message" => "Magic link to login has been sent",
            )
        );
        // TODO: Record the key so that we can use it for future auto-login

        // Save login to database
        $sql = "insert into auth_login (userid,token) values (?,?)";
        $params = array($profileid, $jwt);
        $row = PrepareExecSQL($sql, "ss", $params);
    }

} catch (Exception $e) {
    array_push($errors, array("message" => $e->getMessage()));
}

if (count($errors) > 0) {
    die(json_encode(array("errors" => $errors)));
}

die(json_encode($res));