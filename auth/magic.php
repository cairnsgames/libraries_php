<?php
include_once dirname(__FILE__) . "/../dbutils.php";
include_once dirname(__FILE__) . "/../utils.php";
include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../security/security.config.php";
include_once dirname(__FILE__) . "/authfunctions.php";
include_once dirname(__FILE__) . "/../emailer/sendemail.php";
include_once dirname(__FILE__) . "/../tenant/gettenant.php";
include_once dirname(__FILE__) . "/../permissions/permissionfunctions.php";

$appid = getAppId();
$email = getParam("code", "");
$deviceid = getParam("deviceid", "");

// echo "Email: $email\n";
// echo "DeviceId: $deviceid\n";
// echo "AppId: $appid\n";

$errors = [];
$out = [];

http_response_code(200);

if ($email == "") {
    array_push($errors, array("message" => "Email is Required."));
}

if (count($errors) == 0) {
    try {
        $tenant = getTenant($appid); 
        $homeurl = getProperty("url", null);

        // var_dump($tenant);
        // echo "==================\n";
        // echo  $homeurl;
        // echo "==================\n";
        $magiccode = createMagicLink($email, $appid, $deviceid, $_SERVER['REMOTE_ADDR']);
        $out["result"] = "Magic code has been emailed to you.";

        $htmlContent = '<div>Welcome to <strong style="color:purple">' . $tenant["name"] . '</strong>
                            <div>Click on this link to access the system</div>
                            <div><a href="' . $homeurl . '#magic?code=' . $magiccode . '">Login</a></div>
                            <div>DEVELOPER: <a href="http://localhost:3001#magic?code=' . $magiccode . '">Login to DEV</a></div>
                        </div>';

        sendEmail($appid, $email, "Login to " . $tenant["name"], $htmlContent);

    } catch (Exception $e) {
        array_push($errors, array("message" => $e->getMessage()));
    }
}

if (count($errors) > 0) {
    http_response_code(404);
    $out["errors"] = $errors;
}

die(json_encode($out));