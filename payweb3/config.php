<?php

$PAYREQUESTID = "5CEA1629-858B-8D48-C6DD-FF66B717812C";
$CHECKSUM = "d416087fc3c68b9d183d67060fe28d77";

$PAYGATE_ID_DEFAULT = 10011072130;
$PAYGATE_SECRET = "secret";
$REFERENCE = 'pgtest_123456789';
$host = 'cairns.co.za'; // Database host
$user = 'cairnsco_cairnsgames'; // Database username
$password = 'cairnsco_cairnsgames'; // Database password
$database = 'cairnsco_cairnsgames'; // Database name
$encryptionKey = 'secret';

// Function to execute SQL statements
function executeQuery($sql, $params = []) {
    global $host, $user, $password, $database;

    // Create connection
    $conn = new mysqli($host, $user, $password, $database);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // echo "Number of params", count($params), "<br/>";
    // Prepare statement
    $stmt = $conn->prepare($sql);
    if ($params) {
        // Bind parameters
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }

    // Execute the statement
    $stmt->execute();

    // Get results for SELECT statements
    if (strpos($sql, 'SELECT') === 0) {
        // Alternative approach without using get_result() (which requires mysqlnd)
        $meta = $stmt->result_metadata();
        $fields = array();
        $parameters = array();
        
        while ($field = $meta->fetch_field()) {
            $parameters[] = &$row[$field->name];
            $fields[] = $field->name;
        }
        
        call_user_func_array(array($stmt, 'bind_result'), $parameters);
        
        $data = array();
        while ($stmt->fetch()) {
            $rowData = array();
            foreach ($row as $key => $val) {
                $rowData[$key] = $val;
            }
            $data[] = $rowData;
        }
        
        $stmt->close();
        $conn->close();
        return $data;
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}

// Function to execute cURL requests
function executeCurlRequest($url, $method = 'GET', $data = null) {
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    }

    // Execute cURL request
    $response = curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        die("cURL error: $error_msg");
    }

    curl_close($ch);
    return $response;
}
?>
