<?php
include_once "../api/dbutils.php";
include_once "security.config.php";
include_once "../api/sendemail.php";

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	header("Access-Control-Allow-Origin: *");
	header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, OPTIONS');
	header('Access-Control-Allow-Headers: token, Content-Type');
	header('Access-Control-Max-Age: 0');
	header('Content-Length: 0');
	header('Content-Type: application/json');
	header("Access-Control-Allow-Headers: token, Origin, X-Requested-With, Content-Type, Accept");
	die();
}
else
{
	header("Access-Control-Allow-Origin: *");		
	header('Access-Control-Max-Age: 86400');    // cache for 1 day
	header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, OPTIONS');
	header("Access-Control-Allow-Headers: token, X-Requested-With, Content-Type, Origin, Cache-Control, Pragma, Authorization, Accept, Accept-Encoding");
    header('Access-Control-Allow-Credentials: true');
}

// DONOT do validation as login sets validation token

$id = '';
$force = '';
$pwd = '';
$confirm = '';
$conn = null;

$res = "";
$errors = array();
$input = file_get_contents("php://input");
$data = json_decode($input);

if (isset($_GET["id"])) $id = $_GET["id"];
if (isset( $data->id)) $id = $data->id;
if (isset($_GET["force"])) $force = $_GET["force"];
if (isset( $data->force)) $force = $data->force;
if (isset($_GET["pwd"])) $pwd = $_GET["pwd"];
if (isset( $data->pwd)) $pwd = $data->pwd;
if (isset($_GET["confirm"])) $confirm = $_GET["confirm"];
if (isset( $data->confirm)) $confirm = $data->confirm;
$password = $pwd;

if ($pwd !== $confirm)
{
    array_push($errors,array("message" => "Passwords do not match."));
    $canRegister = false;
}
else
try {
    // Check valid ForceKey
    $sql = "select * from auth_forgot where forcekey = ? and state = 'request'";
    $params = array($force);
    $row = PrepareExecSQL($sql,"s",$params);
    if (!isset($row[0])) {
        array_push($errors,array("message" => "Passwords do not match."));
    } else {
        $email = $row[0]["email"];
        
        // Change Password
        $password_hash = crypt($password, $PASSWORDHASH);
        $sql = "update Users set password = ? where email = ?";
        $params = array($password_hash, $email);
        $row = PrepareExecSQL($sql,"ss",$params);

        // Log in user
        $table_name = 'Users';
        //$sql = "SELECT id, profileid, first_name, last_name, password, (select accountlevel from profile where id = profileid) as accountlevel FROM " . $table_name . " WHERE email = ? LIMIT 0,1";
        $sql = "SELECT Users.id, profileid, first_name, last_name, phonenumber, password, profile.accountlevel, profile.admin, profile.teacherexpiry, profile.lat, profile.lng, avatar, teacherexpiry, ";
        $sql .= "(SELECT GROUP_CONCAT(distinct concat(properties.property,'=',properties.value)  SEPARATOR ', ') FROM properties WHERE properties.profileid = profile.id) props ";
        $sql .= "FROM Users, profile WHERE Users.email = ? and profile.id = Users.profileid LIMIT 0,1";
        $params = array($email);	
        $row = PrepareExecSQL($sql,"s",$params);
        //echo $sql;
        // var_dump($row);
        // echo count($row);
        try {
            if (count($row) == 1) {
                $row = $row[0];
                $profileid = $row['profileid'];
                $admin = $row["admin"];
                $firstname = $row['first_name'];
                $lastname = $row['last_name'];
                $phonenumber = $row['phonenumber'];
                $accountlevel = $row["accountlevel"];
                $teacherexpiry = $row["teacherexpiry"];
                $avatar = $row["avatar"];
                $password2 = $row['password'];
                $lat = $row['lat'];
                $lng = $row['lng'];
                $props = $row['props'];

                $password_hash = crypt($password, $PASSWORDHASH);
                //echo $password_hash;

                if ($password_hash === $password2) {
                    $_SESSION['email'] = $email;
                    $jwt = createToken(array("id" => $profileid,"firstname" => $firstname,"lastname" => $lastname,"accountlevel" => $accountlevel)); 
                    $res = json_encode(array("message" => "Login succeded.","id" => $profileid,"firstname" => $firstname,"lastname" => $lastname,"phonenumber" => $phonenumber,
                        "avatar" => $avatar,"token" => $jwt,"accountlevel" => $accountlevel, "teacherexpiry" => $teacherexpiry,  "lat" => $lat, "lng" => $lng, 
                        "props" => $props, "admin" => $admin));
                    // TODO: Record the key so that we can use it for future auto-login
                } else {
                    array_push($errors,array("message" => "Login failed, invalid email or password"));
                }        
            } else {
                array_push($errors,array("message" => "Login failed, invalid email or password"));
            }
        
        } catch(Exception $e) {
            array_push($errors,array("message" => $e->getMessage()));
        }    
    }
} catch(Exception $e) {
    array_push($errors,array("message" => $e->getMessage()));
}    

if (count($errors) > 0) {
    $res = json_encode(array("errors" => $errors));
} 

echo $res;

?>