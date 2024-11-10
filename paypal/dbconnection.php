<?php
require_once dirname(__DIR__) . "/dbconfig.php";

// Function to get the MySQLi connection
function getDbConnection() {
    global $host, $user, $password, $database;

    static $conn = null; // Static variable to hold the connection

    // Create the connection if it does not exist
    if ($conn === null) {
        $conn = new mysqli($host, $user, $password, $database);

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
    }

    return $conn;
}