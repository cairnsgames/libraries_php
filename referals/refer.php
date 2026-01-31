<?php
// Endpoint to record a referral: expects `refer` (user_id) and `t` (type)
include_once dirname(__FILE__)."/../dbutils.php";

header('Content-Type: application/json');

try {
    // Read params (GET or POST)
    $user_id = 0;
    if (isset($_REQUEST['refer'])) {
        $user_id = intval($_REQUEST['refer']);
    }

    $referal_type = '';
    if (isset($_REQUEST['t'])) {
        $referal_type = trim($_REQUEST['t']);
    }

    if ($user_id <= 0) {
        throw new Exception('Invalid or missing refer parameter');
    }

    // Determine requester IP
    $ip = '0';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($parts[0]);
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    $ip = substr($ip, 0, 50);

    // Limit referal_type length to 50
    $referal_type = substr($referal_type, 0, 50);

    // If a record for this user_id+ip exists, update last_date_used; otherwise insert
    $sql = "SELECT id FROM user_referals WHERE user_id = ? AND referee_ip_address = ?";
    $existing = PrepareExecSQL($sql, 'is', [$user_id, $ip]);

    if (is_array($existing) && count($existing) > 0) {
        $id = intval($existing[0]['id']);
        $sql = "UPDATE user_referals SET last_date_used = NOW(), referal_type = ? WHERE id = ?";
        $affected = PrepareExecSQL($sql, 'si', [$referal_type, $id]);
        echo json_encode(['status' => 'ok', 'action' => 'updated', 'id' => $id, 'affected' => $affected]);
        exit;
    } else {
        $sql = "INSERT INTO user_referals (user_id, referee_ip_address, last_date_used, referal_type) VALUES (?,?,NOW(),?)";
        $insertId = PrepareExecSQL($sql, 'iss', [$user_id, $ip, $referal_type]);
        echo json_encode(['status' => 'ok', 'action' => 'inserted', 'id' => $insertId]);
        exit;
    }

} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode(['error' => $ex->getMessage()]);
    exit;
}
