<?php
include_once dirname(__FILE__)."/jwt.php";
include_once dirname(__FILE__)."/../permissions/permissionfunctions.php";

$issuer = getSecret("jwt_issuer", "cairnsgames.co.za");
$subject = getSecret("jwt_subject", "cairnsgames token");
$audience = getSecret("jwt_audience", "cairnsgames client");

$defaultConfig = array("issuer"=>$issuer,"subject"=>$subject,"audience"=>$audience);

$JWTSECRET = getSecret("SECURE_SECRET","cairnsgameSUPERsecretPASSWORD");
// echo "SECRET:".$JWTSECRET;"<br/>\n";
$SSLSECRET = $JWTSECRET;
$PASSWORDHASH = $JWTSECRET;

function createToken($payload) {
    global $JWTSECRET;
    jwt_set_secret($JWTSECRET);
    jwt_set_payload($payload);
    $jwt = jwt_token();
    return $jwt;
}
function validateJwt($token,$time=false,$aud=NULL) {
    global $JWTSECRET;
    if (!isset($J))
    jwt_set_secret($JWTSECRET);
    return validate_jwt($token,$time,$aud);
}

function randomPassword($len) {
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < $len; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}

?>