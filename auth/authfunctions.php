<?php

include_once dirname(__FILE__)."/../security/security.config.php";

function getUserPermissions($id, $appid)
{
    $sql = "SELECT name, IF(NEVER>0,'NEVER', if(YES>0,'YES', 'NO')) permission FROM (
        SELECT NAME, SUM(yes) yes, SUM(NO) no, SUM(NEVER) never FROM (
        SELECT 'Application' role, NAME, if(VALUE=1,1,0) yes, if(VALUE=0,1,0) no, if(VALUE=-1,1,0) never FROM permission
        WHERE app_id = ?
        UNION
        SELECT r.name, p.NAME, if(rp.VALUE=1,1,0) yes, if(rp.VALUE=0,1,0) no, if(rp.VALUE=-1,1,0) NEVER
            FROM permission p, role_permissions rp, role r, user_role ur
        WHERE p.app_id = ?
            AND rp.permission_id = p.id
            AND rp.role_id = r.id
            AND ur.user_id = ?
            AND ur.role_id = r.id
            UNION
        SELECT 'User' name, p.NAME, if(up.VALUE=1,1,0) yes, if(up.VALUE=0,1,0) no, if(up.VALUE=-1,1,0) NEVER 
            FROM permission p, user_permissions up
        WHERE p.app_id = ?
            AND up.permission_id = p.id
            AND up.user_id = ?
            ) t
        GROUP BY NAME) t2";
    $params = array($appid, $appid, $id, $appid, $id);
    $rows = PrepareExecSQL($sql, "sssss", $params);
    return $rows;
}
function getTokenForUser($id, $appid, $mastertoken = "")
{
    global $out, $debugValues, $errors;
    $jwt = "";
    try {
        $sql = "select id, email, firstname, lastname, avatar, role_id from user where app_id = ? and id = ?";

        $params = array($appid, $id);
        $row = PrepareExecSQL($sql, "ss", $params);

        array_push($debugValues, array("selectUser" => array("sql" => $sql, "params" => $params)));

        if (empty($row)) {
            array_push($errors, array("message" => "Invalid user email or application."));
        } else {

            $firstname = $row[0]["firstname"];
            $lastname = $row[0]["lastname"];
            $avatar = $row[0]["avatar"];
            $profileid = $row[0]["id"];
            $role_id = $row[0]["role_id"];
            $email = $row[0]["email"];

            $permissions = getUserPermissions($row[0]["id"], $appid);

            $tokenfields = array(
                "id" => $profileid,
                "firstname" => $firstname,
                "lastname" => $lastname,
                "role" => $role_id,
                "permissions" => $permissions
            );
            if ($mastertoken != "") {
                $tokenfields["mastertoken"] = $mastertoken;
            }

            $jwt = createToken($tokenfields);
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
                    "app_id" => $appid,
                    "permissions" => $permissions,
                );
        }

    } catch (Exception $e) {
        array_push($errors, array("message" => $e->getMessage()));
    }
    return $jwt;
}

function getLoginToken($email, $password, $appid)
{
    global $out, $debugValues, $errors, $PASSWORDHASH;
    try {
        $sql = "select id, email, firstname, lastname, avatar, role_id from user where app_id = ? and email = ? and password = ?  ";

        // echo $sql, "\n";
        $password_hash = crypt($password, $PASSWORDHASH);
        $params = array($appid, $email, $password_hash);
        $row = PrepareExecSQL($sql, "sss", $params);

        // $params[2] = "######";
        array_push($debugValues, array("selectAuthUser" => array("sql" => $sql, "params" => $params)));

        if (empty($row)) {
            array_push($errors, array("message" => "Invalid Email or Password."));
        } else {

            $profileid = $row[0]["id"];
            $jwt = getTokenForUser($profileid, $appid);

            // Save login to database
            $ipaddress = $_SERVER['REMOTE_ADDR'];
            if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
                $forwarded_for = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $forwarded_for = "";
            }
            
            $deviceid = getParam("deviceid", "");
            $sql = "insert into auth_login (userid,token, ip_address, forwarded_for, device_id) values (?,?,?,?,?)";
            $params = array($profileid, $jwt, $ipaddress, $forwarded_for, $deviceid);
            $row = PrepareExecSQL($sql, "sssss", $params);
            array_push($debugValues, array("insertAuthLogin" => array("sql" => $sql, "params" => $params)));
        }

    } catch (Exception $e) {
        array_push($errors, array("message" => $e->getMessage()));
    }
}

function getUserEmail($token)
{
    if (validateJwt($token)) {
        $data = get_jwt_payload($token)->data;
        return $data->email;
    }
    return "";
}
function getUserId($token)
{
    if (validateJwt($token)) {
        $data = get_jwt_payload($token)->data;
        return $data->id;
    }
    return "";
}