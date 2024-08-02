<?php


$dbconfig = array(
    "server" => 'cairns.co.za',
    "username" => 'cairnsco_cairnsgames',
    "password" => 'cairnsco_cairnsgames',
    "database" => 'cairnsco_cairnsgames'
);

$returnUrl = "http://localhost/cairnsgames/paypal/success.php?success=true";
$cancelUrl = "http://localhost/cairnsgames/paypal/success.php?success=false";

// localhost/
// cairnsgames.co.za/dev/php

// $server = $_SERVER['SERVER_NAME'];

// if ($server == "localhost") {
//     $dbconfig = array(
//         "server" => 'localhost',
//         "username" => 'membership',
//         "password" => 'membership',
//         "database" => 'membership'
//     );
// }

?>