<?php
require_once dirname(__DIR__) . "/dbconfig.php";

// Function to get the MySQLi connection
function getDbConnection() {
    global $dbconfig;

    static $conn = null; // Static variable to hold the connection

    // Create the connection if it does not exist
    if ($conn === null) {
        $conn = new mysqli($dbconfig["server"], $dbconfig["user"], $dbconfig["password"], $dbconfig["database"]);

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
    }

    return $conn;
}