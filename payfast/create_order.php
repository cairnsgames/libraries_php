<?php
require_once 'config.php';

// Example usage
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'];
    $itemName = $_POST['item_name'];
    createOrder($amount, $itemName);
}
?>
