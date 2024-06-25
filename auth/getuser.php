<?php

function getUserByEmail($email, $appid)
{
    $sql = "select id, email, firstname, lastname, avatar, role_id from user where app_id = ? and email = ?";
    $params = array($appid, $email);
    $row = PrepareExecSQL($sql, "ss", $params);
    return $row;
}