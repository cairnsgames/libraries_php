<?php

include_once dirname(__FILE__)."/../dbutils.php";
include_once dirname(__FILE__)."/../utils.php";

$appid = getAppId();
$permissions;

function getPermissions($id) {
    global $appid, $permissions;
    $sql = "SELECT name, IF(never>0,'NEVER', if(yes>0,'YES', 'NO')) permission FROM (
        SELECT name, SUM(yes) yes, SUM(NO) no, SUM(NEVER) never FROM (
        SELECT 'Application' role, NAME, if(VALUE=1,1,0) yes, if(VALUE=0,1,0) no, if(VALUE=-1,1,0) never FROM permission
        WHERE app_id = ?
        UNION
        SELECT r.name role, p.name name, if(rp.VALUE=1,1,0) yes, if(rp.VALUE=0,1,0) no, if(rp.VALUE=-1,1,0) NEVER
            FROM permission p, role_permissions rp, role r, user_role ur
        WHERE p.app_id = ?
            AND rp.permission_id = p.id
            AND rp.role_id = r.id
            AND ur.user_id = ?
            AND ur.role_id = r.id
        UNION
        SELECT 'User' role, p.name name, if(up.VALUE=1,1,0) yes, if(up.VALUE=0,1,0) no, if(up.VALUE=-1,1,0) NEVER 
            FROM permission p, user_permissions up
        WHERE p.app_id = ?
            AND up.permission_id = p.id
            AND up.user_id = ?
            ) t
        GROUP BY NAME) t2";
    $params = [$appid, $appid, $id, $appid, $id];
    $sss = "sssss";
    $permissions = PrepareExecSQL($sql, $sss, $params);
}

function hasAccess($id, $permission) {
    global $permissions;
    if (!isset($permissions)) {
        getPermissions($id);
    }
    // var_dump($permissions);
    foreach ($permissions as $p) {
        if ($p["name"] == $permission) {
            return $p["permission"] == "YES";
        }
    }
    return false;
}
