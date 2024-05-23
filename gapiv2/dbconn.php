<?php

include_once "../dbconfig.php";

// Create a global $conn exists for the MySQL connection
global $conn;
$conn = new mysqli($dbconfig["server"], $dbconfig["username"], $dbconfig["password"], $dbconfig["database"]);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}