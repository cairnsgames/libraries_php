<?php

$datasyncapi = "http://localhost/juztdance-php/datasync/";

function upsertMyPin($locationData) {
    global $datasyncapi;

    // Required fields for location data
    $requiredFields = ['user_id', 'lat', 'lng'];
    $missingFields = [];

    // Validate that all required fields are provided and not empty
    foreach ($requiredFields as $field) {
        if (!isset($locationData[$field]) || $locationData[$field] === '') {
            $missingFields[] = $field;  // Add missing field to the list
        }
    }

    // If there are missing fields, return an error listing all of them
    if (!empty($missingFields)) {
        return ['error' => 'The following fields are required and cannot be empty: ' . implode(', ', $missingFields)];
    }

    // Set default name if not provided
    if (empty($locationData['name'])) {
        $locationData['name'] = "My Pin";
    }

    // Define the API endpoint
    $url = $datasyncapi . "upsertlocation.php";

    // Initialize cURL
    $ch = curl_init($url);

    // Convert the $locationData array to JSON
    $postData = json_encode($locationData);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json'
    ));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    // Execute the cURL request
    $response = curl_exec($ch);

    // Check if any error occurred during the cURL request
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return ['error' => $error_msg];
    }

    // Close the cURL session
    curl_close($ch);

    // Decode and return the response as an associative array
    return json_decode($response, true);
}

function upsertUserApi($userData) {
    global $datasyncapi;

    // Required fields for user data
    $requiredFields = ['first_name', 'last_name', 'email', 'password'];
    $missingFields = [];

    // Validate that all required fields are provided and not empty
    foreach ($requiredFields as $field) {
        if (empty($userData[$field])) {
            $missingFields[] = $field;  // Add missing field to the list
        }
    }

    // If there are missing fields, return an error listing all of them
    if (!empty($missingFields)) {
        return ['error' => 'The following fields are required and cannot be empty: ' . implode(', ', $missingFields)];
    }

    // Define the API endpoint
    $url = $datasyncapi . "upsertuser.php";

    // Initialize cURL
    $ch = curl_init($url);

    // Convert the $userData array to JSON
    $postData = json_encode($userData);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json'
    ));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    // Execute the cURL request
    $response = curl_exec($ch);

    // Check if any error occurred during the cURL request
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return ['error' => $error_msg];
    }

    // Close the cURL session
    curl_close($ch);

    // Decode and return the response as an associative array
    return json_decode($response, true);
}
