<?php

include_once "../corsheaders.php";
include_once "../dbutils.php";
include_once "../utils.php";
include_once "../security/security.config.php";

$email = '';
$password = '';

$res = array();
$errors = array();
$appid = getHeader("APP_ID",$appid = getHeader("app_id",$appid = getHeader("App_id","")));
$token = getParam("token", "");
$debug = getParam("debug", false);
$debugValues = array();


try {
    if (validateJwt($token) == true) {
        // Valid token
        $data = get_jwt_payload($token)->data;

        if ($debug) {
            $debugValues["debug"] = array("tokendata" => $data, "app_id" => $appid);
        }

        $table_name = 'Users';
        $sql = "select id, email, firstname, lastname, avatar, role_id from user where app_id = ? and id = ? ";
        $params = array($appid, $data->id);
        $row = PrepareExecSQL($sql, "ss", $params);
        if ($debug) {
            $debugValues["debug"]["sql"] = $sql;
            $debugValues["debug"]["params"] = $params;
        }

        try {
            if (count($row) == 1) {
                $firstname = $row[0]["firstname"];
                $lastname = $row[0]["lastname"];
                $avatar = $row[0]["avatar"];
                $profileid = $row[0]["id"];
                $role_id = $row[0]["role_id"];
                $email = $row[0]["email"];

                $jwt = createToken(
                    array("id" => $profileid, "firstname" => $firstname, "lastname" => $lastname, "role" => $role_id)
                );
                $res = array(
                    "message" => "Token passed validation.",
                    "id" => $profileid,
                    "email" => $email,
                    "firstname" => $firstname,
                    "lastname" => $lastname,
                    "avatar" => $avatar,
                    "token" => $jwt,
                    "role" => $role_id
                );

                // Save login to database
                // $sql = "insert into auth_login (userid,token) values (?,?)";
                // $params = array($profileid, $jwt);
                // $row = PrepareExecSQL($sql, "ss", $params);
            } else {
                array_push($errors, array("message" => "Login failed, invalid email or password"));
            }
        } catch (Exception $e) {
            array_push($errors, array("message" => $e->getMessage()));
        }
    } else {
        array_push($errors, array("message" => "Invalid token, please login"));
    }
} catch (Exception $e) {
    array_push($errors, array("message" => $e->getMessage()));
}

if (count($errors) > 0) {
    $res["errors"] = $errors;
}
if ($debug) {
    $res["debug"] = $debugValues;
}

die(json_encode($res));