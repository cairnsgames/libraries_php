<?php

include_once dirname(__FILE__) . "/dbconnection.php";

function registerScript($triggerType, $action, $scriptPath, $functionName) {
    $mysqli = getDBConnection();
    $stmt = $mysqli->prepare("INSERT INTO trigger_subscriptions (trigger_type, action, script_path, function_name) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $triggerType, $action, $scriptPath, $functionName);
    $stmt->execute();
    $stmt->close();
    $mysqli->close();
}

function unregisterScript($triggerType, $action, $scriptPath, $functionName) {
    $mysqli = getDBConnection();
    $stmt = $mysqli->prepare("
        DELETE FROM trigger_subscriptions 
        WHERE trigger_type = ? AND action = ? AND script_path = ? AND function_name = ?
    ");
    $stmt->bind_param('ssss', $triggerType, $action, $scriptPath, $functionName);
    $stmt->execute();
    $stmt->close();
    $mysqli->close();
}

function triggerHandler($triggerType, $action, $data) {
    $mysqli = getDBConnection();
    $stmt = $mysqli->prepare("
        SELECT id, script_path, function_name 
        FROM trigger_subscriptions 
        WHERE trigger_type = ? AND (action = ? OR action = '*')
    ");
    $stmt->bind_param('ss', $triggerType, $action);
    $stmt->execute();
    $stmt->bind_result($subscriptionId, $scriptPath, $functionName);
    
    while ($stmt->fetch()) {
        $cleanData = stripPII($data);
        callScriptFunctionAsync($subscriptionId, $scriptPath, $functionName, $triggerType, $action, $cleanData);
    }
    
    $stmt->close();
    $mysqli->close();
}

function callScriptFunctionAsync($subscriptionId, $scriptPath, $functionName, $triggerType, $action, $data) {
    // Define the base path to the parent directory of the current file's directory
    $basePath = dirname(__DIR__);

    // Ensure the script path is relative to the base path
    $fullScriptPath = $basePath . '/' . $scriptPath;

    // Use shell_exec or another method to call the script asynchronously
    $command = "php -r \"require '$fullScriptPath'; $functionName('$triggerType', '$action', json_encode($data));\" > /dev/null 2>/dev/null &";
    shell_exec($command);
    
    // Log the trigger call
    logTriggerCall($subscriptionId, $triggerType, $action, $data);
}

function logTriggerCall($subscriptionId, $triggerType, $action, $data) {
    $mysqli = getDBConnection();
    $stmt = $mysqli->prepare("
        INSERT INTO trigger_logs (subscription_id, trigger_type, action, data)
        VALUES (?, ?, ?, ?)
    ");
    $dataJson = json_encode($data);
    $stmt->bind_param('isss', $subscriptionId, $triggerType, $action, $dataJson);
    $stmt->execute();
    $stmt->close();
    $mysqli->close();
}

function stripPII($data) {
    // Define keys to replace with [$key], example: 'email', 'phone', 'address'
    $keysToReplace = ['email', 'phone', 'address', 'lat', 'lng', 'location'];

    // Recursive function to process each key in the array
    function processArray(&$data, $keysToReplace) {
        foreach ($data as $key => &$value) {
            if (in_array($key, $keysToReplace)) {
                // Replace the value with [$key]
                $data[$key] = "[$key]";
            } elseif (is_array($value)) {
                // Recursively process sub-arrays
                processArray($value, $keysToReplace);
            }
        }
    }

    // Process the input data
    processArray($data, $keysToReplace);

    return $data;
}
