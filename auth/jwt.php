<?php
include "base64url.php";
include_once "security.config.php";


$jwtError = array();

function jwt_header() {
    return [
        "alg" => "HS256",
        "typ" => "JWT"
    ];
}

function jwt_set_secret($secret) {
    global $SecretKey;
    $SecretKey = $secret;
}


function jwt_secret() {
    global $SecretKey;
    return $SecretKey;
}

$jwtpayload = array();
function jwt_set_payload($payload, $config = NULL) {   
  make_payload($payload, $config);
}

function jwt_payload() {
    global $jwtpayload;
    return $jwtpayload;
}

function getGUID(){
    if (function_exists('com_create_guid')){
        return com_create_guid();
    }else{
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = chr(123)// "{"
            .substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12)
            .chr(125);// "}"
        return $uuid;
    }
}

function make_payload($payload, $config = NULL) {
    if ($config == NULL) {
        global $defaultConfig;
        $config = $defaultConfig;
    };
    array_key_exists("issuer",$config) ? $iss = $config["issuer"] : $iss = "";
    array_key_exists("subject",$config) ? $sub = $config["subject"] : $sub = "";
    array_key_exists("audience",$config) ? $aud = $config["audience"] : $aud = "";
    $datetime = new DateTime();
    $nbf = $datetime->getTimestamp();
    $iat = $datetime->getTimestamp();
    if (array_key_exists("expiryperiod", $config)) {
        $exp = $datetime->getTimestamp() + $config["expiryperiod"];
    } else {
        $exp = $datetime->getTimestamp() + 60480000; // 2 years

    }
    $jti = getGUID();
    
    global $jwtpayload;
    $jwtpayload = array("iss" => $iss, "sub" => $sub, "aud" => $aud, "exp" => $exp, "nbf" => $nbf, "iat" => $iat, "jti" => $jti, "data" => $payload);
    return $jwtpayload;
}

function jwt_token() {
    $header = base64url_encode(json_encode(jwt_header()));
    $payload = base64url_encode(json_encode(jwt_payload()));
    $secret = jwt_secret();
    $raw = $header.".".$payload;
    $signature = hash_hmac("sha256",$raw,$secret);
    $jwt_token = $raw.".".base64url_encode($signature);
    return $jwt_token;
}

function jwt_error() {
    global $jwtError;
    return $jwtError;
}

function validate_jwt($token,$time=false,$aud=NULL) {
    global $jwtError;
    $jwtError = array();
    $section = explode('.', $token);
    $secret = jwt_secret();
    $header = "";
    if (isset($section[0])) {
        $header = $section[0];
    } 
    $payload = "";
    if (isset($section[1])) {
        $payload = $section[1];
    }
    $tokensignature = "";
    if (isset($section[2])) {
        $tokensignature = $section[2];
    }
    
    $raw = $header.".".$payload;
    //echo "Secret=".$secret." + + + +";
    $signature = base64url_encode(hash_hmac("sha256",$raw,$secret));    
    //echo ($header."==============".$payload."==================".$tokensignature."====================".$signature);
    if ($signature == $tokensignature) {
        if ($time) {
            $payload = json_decode(base64url_decode($section[1]));
            $now = new DateTime();
            if ($payload->exp < $now->getTimestamp()) {
                $jwtError[] = "Token has expired";
                return false;
            }
        }
        if ($aud != NULL) {
            $payload = json_decode(base64url_decode($section[1]));
            if ($payload->aud != $aud) {
                $jwtError[] = "Invalid Audience";
                return false;
            }
        }
        return true;
    } else {
        $jwtError[] = "Signature does not match";
        return false;
    }
}

function get_jwt_payload($token) {
    $section = explode('.', $token);
    $payload = base64url_decode($section[1]);
    return json_decode($payload);
}


?>