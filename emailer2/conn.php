<?php

include_once("emailerconfig.php");

global $conn;

function getConnection() {
    global $conn;
    
    if (!isset($conn)) {
        global $emailerconfig;
        $conn = new mysqli($emailerconfig["server"], $emailerconfig["username"], $emailerconfig["password"], $emailerconfig["database"]);

        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        // Set the character set to UTF-8
        if (!$conn->set_charset("utf8")) {
            throw new Exception("Error setting charset: " . $conn->error);
        }
    }
    
    return $conn;
}

function executeSQL($sql, $params = [], $config = []) {
  $conn = getConnection();
  $stmt = $conn->prepare($sql);

  if (!$stmt) {
    throw new Exception("Query preparation failed: " . $conn->error);
  }

  if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
  }

  if (!$stmt->execute()) {
    throw new Exception("Query execution failed: " . $stmt->error);
  }

  // Check if this is a SELECT statement
  if (stripos(trim($sql), 'select') === 0) {
    $result = $stmt->get_result();
    $rows = [];
    $json_fields = isset($config["json_fields"]) ? $config["json_fields"] : [];
    while ($row = $result->fetch_assoc()) {
      foreach ($json_fields as $field) {
        if (isset($row[$field])) {
          $decoded = json_decode($row[$field], true);
          if (json_last_error() === JSON_ERROR_NONE) {
            $row[$field] = $decoded;
          } else {
            $row[$field] = new stdClass();
          }
        }
      }
      $rows[] = $row;
    }
    $stmt->close();
    return $rows;
  }

  // If this is an INSERT statement, return the insert id
  if (stripos(trim($sql), 'insert') === 0) {
    $insert_id = $stmt->insert_id;
    $stmt->close();
    return $insert_id;
  }

  $stmt->close();
  return true;
}