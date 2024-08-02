<?php

require_once "dbconnection.php";

// Function to create a new record or update if exists
function create($orderid, $paymentid, $eccode, $webhookData = null) {
    $conn = getDbConnection();

    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO payment_progress (order_id, payment_id, eccode, status, webhook_data) VALUES (?, ?, ?, 'pending', ?) ON DUPLICATE KEY UPDATE eccode = VALUES(eccode), status = 'pending', webhook_data = VALUES(webhook_data), updated_at = NOW()");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param('ssss', $orderid, $paymentid, $eccode, $webhookData);

    if ($stmt->execute()) {
        // echo "Record created/updated successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Function to mark a payment as completed
function success($paymentid, $webhookData = null) {
    $conn = getDbConnection();

    // Prepare the SQL statement
    // $stmt = $conn->prepare("update payment_progress (SET status = 'completed', webhook_data = ?, updated_at = NOW() WHERE payment_id = ?");
    $stmt = $conn->prepare("INSERT INTO payment_progress (order_id, payment_id, eccode, status, webhook_data) 
       VALUES (?, ?, ?, 'completed', ?) ON DUPLICATE KEY UPDATE eccode = VALUES(eccode), status = 'pending', webhook_data = VALUES(webhook_data), updated_at = NOW()
       on duplicate key update status = 'completed', webhook_data = ?, updated_at = NOW()");

    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param('ssss', $orderid, $paymentid, $eccode, $webhookData, $webhookData);

    if ($stmt->execute()) {
        // echo "Payment marked as completed.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Function to mark a payment as failed
function failed($paymentid, $webhookData = null) {
    $conn = getDbConnection();

    // Prepare the SQL statement
    $stmt = $conn->prepare("UPDATE payment_progress SET status = 'failed', webhook_data = ?, updated_at = NOW() WHERE payment_id = ?");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param('ss', $webhookData, $paymentid);

    if ($stmt->execute()) {
        // echo "Payment marked as failed.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>