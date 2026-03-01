<?php

$appId = getAppId();
$token = getToken();

if (!hasValue($token)) {
    sendUnauthorizedResponse("Invalid token");
}
if (!hasValue($appId)) {
    sendUnauthorizedResponse("Invalid tenant");
}

$userid = getUserId($token);
if (!$userid) {
    sendUnauthorizedResponse("User not found");
}

// Define the configurations
$dashboardconfigs = [
    
    "post" => [
        "toggle" => "toggleOffering",
        "stats" => "getDashboardStats"
    ]
];



function getDashboardStats($data)
{
    global $appId;

    $app_id = isset($data['app_id']) && $data['app_id'] !== '' ? $data['app_id'] : $appId;
    if (!$app_id) {
        return ['success' => false, 'message' => 'app_id required'];
    }

    $start_date = isset($data['start_date']) ? $data['start_date'] : date('Y-m-d', strtotime('-30 days'));
    $end_date = isset($data['end_date']) ? $data['end_date'] : date('Y-m-d');
    $interval = isset($data['interval']) ? strtolower($data['interval']) : 'daily';

    $start_dt = $start_date . ' 00:00:00';
    $end_dt = $end_date . ' 23:59:59';

    switch ($interval) {
        case 'weekly':
            $fmt = '%x-%v';
            break;
        case 'monthly':
            $fmt = '%Y-%m';
            break;
        case 'daily':
        default:
            $fmt = '%Y-%m-%d';
            break;
    }

    // 1) New users by type (partner if has any user_role record)
    $sql_users = "SELECT DATE_FORMAT(u.created, '{$fmt}') AS period,\n" .
        "SUM(CASE WHEN (SELECT COUNT(*) FROM user_role ur WHERE ur.user_id = u.id) = 0 THEN 1 ELSE 0 END) AS normal,\n" .
        "SUM(CASE WHEN (SELECT COUNT(*) FROM user_role ur WHERE ur.user_id = u.id) > 0 THEN 1 ELSE 0 END) AS partner\n" .
        "FROM `user` u\n" .
        "WHERE u.app_id = ? AND u.created BETWEEN ? AND ?\n" .
        "GROUP BY period\n" .
        "ORDER BY period";

    $users = executeSQL($sql_users, [$app_id, $start_dt, $end_dt]);

    // 2) Events by event_type (empty/null => 'event')
    $sql_events = "SELECT DATE_FORMAT(start_time, '{$fmt}') AS period, COALESCE(NULLIF(event_type, ''), 'event') AS event_type, COUNT(*) AS count\n" .
        "FROM kloko_event\n" .
        "WHERE app_id = ? AND start_time BETWEEN ? AND ?\n" .
        "GROUP BY period, event_type\n" .
        "ORDER BY period";

    $events = executeSQL($sql_events, [$app_id, $start_dt, $end_dt]);

    // 3) Tickets sold and value (join to events to filter by app_id)
    $sql_tickets = "SELECT DATE_FORMAT(t.created, '{$fmt}') AS period, COUNT(*) AS tickets_sold, COALESCE(SUM(t.price),0) AS total_value\n" .
        "FROM kloko_tickets t\n" .
        "JOIN kloko_event e ON e.id = t.event_id\n" .
        "WHERE e.app_id = ? AND t.created BETWEEN ? AND ?\n" .
        "GROUP BY period\n" .
        "ORDER BY period";

    $tickets = executeSQL($sql_tickets, [$app_id, $start_dt, $end_dt]);

    return [
        'success' => true,
        'params' => [
            'app_id' => $app_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'interval' => $interval
        ],
        'data' => [
            'users' => $users,
            'events' => $events,
            'tickets' => $tickets
        ]
    ];
}

function toggleOffering($data)
{
    global $userid;
    $user_id = isset($data['user_id']) ? (int)$data['user_id'] : (isset($userid) ? (int)$userid : 0);
    $offering_id = isset($data['offering_id']) ? (int)$data['offering_id'] : 0;

    if (!$user_id || !$offering_id) {
        return ['success' => false, 'message' => 'Invalid user_id or offering_id'];
    }

    // Check if the record exists
    $sql = "SELECT id FROM user_offerings WHERE user_id = ? AND offering_id = ?";
    $result = executeSQL($sql, [$user_id, $offering_id]);

    if (!empty($result)) {
        // Record exists, delete it
        $sql = "DELETE FROM user_offerings WHERE user_id = ? AND offering_id = ?";
        executeSQL($sql, [$user_id, $offering_id]);
        return ['success' => true, 'action' => 'deleted', 'record' => null];
    } else {
        // Record does not exist, insert it
        $sql = "INSERT INTO user_offerings (user_id, offering_id, active) VALUES (?, ?, 1)";
        executeSQL($sql, [$user_id, $offering_id]);
        // Fetch the newly inserted record
        $sql = "SELECT id, user_id, offering_id, active FROM user_offerings WHERE user_id = ? AND offering_id = ? ORDER BY id DESC LIMIT 1";
        $newRecord = executeSQL($sql, [$user_id, $offering_id]);
        return [
            'success' => true,
            'action' => 'inserted',
            'record' => !empty($newRecord) ? $newRecord[0] : null
        ];
    }
}
