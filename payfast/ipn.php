<?php
require_once 'config.php';

// Handle IPN
$data = $_POST;
if (verifyIpn($data)) {
    // Payment is valid, update order status in the database
    $orderId = $data['order_id'];
    updateOrderStatus($orderId, 'completed');
} else {
    // Invalid payment
    $orderId = $data['order_id'];
    updateOrderStatus($orderId, 'failed');
}
?>
