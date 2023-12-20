<?php
include_once "jwt.php";

$JWTSECRET = "Juzt.Dance's super secret key that no one can guess because it is so long";
$SSLSECRET = "Juzt.Dance's super secret certificate that no one can guess because it is so long";
$PASSWORDHASH = 'justdancefordanaandwilliam';
$defaultConfig = array("issuer"=>"Juzt.Dance","subject"=>"just dance token","audience"=>"just dance client");

// echo "PASSORDHASH****", $PASSWORDHASH, "\n";

function createToken($payload) {    
    global $JWTSECRET;
    jwt_set_secret($JWTSECRET);
    jwt_set_payload($payload); 
    $jwt = jwt_token();
    return $jwt;
}
function validateJwt($token,$time=false,$aud=NULL) { 
    global $JWTSECRET;
    jwt_set_secret($JWTSECRET);
    // var_dump(validate_jwt($token,$time,$aud));
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