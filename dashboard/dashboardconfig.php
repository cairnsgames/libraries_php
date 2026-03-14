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
    "news" => [
        'tablename' => 'news',
        'key' => 'id',
        'select' => 'dashboardNewsSelect',
        'create' => [
            'title',
            'body',
            'image_url',
            'overlay_text',
            'date',
            'location',
            'lat',
            'lng',
            'expires',
            'app_id',
            'user_id'
        ],
        'update' => [
            'title',
            'body',
            'image_url',
            'overlay_text',
            'date',
            'location',
            'lat',
            'lng',
            'expires',
            'deleted'
        ],
        'delete' => true,
        'beforeselect' => 'dashboardNewsBeforeSelect',
        'beforecreate' => 'dashboardNewsBeforeCreate',
        'beforeupdate' => 'dashboardNewsBeforeUpdate',
        'beforedelete' => 'dashboardNewsBeforeDelete'
    ],
    "event" => [
        'tablename' => 'kloko_event',
        'key' => 'id',
        'select' => 'dashboardEventSelect',
        'create' => [
            'title',
            'description',
            'image',
            'event_type',
            'keywords',
            'duration',
            'location',
            'lat',
            'lng',
            'max_participants',
            'period_type',
            'start_time',
            'end_time',
            'currency',
            'tickets',
            'tickettypes',
            'options',
            'price',
            'content_id',
            'show_as_news',
            'overlay_text',
            'enable_bookings'
        ],
        'update' => [
            'title',
            'description',
            'image',
            'event_type',
            'keywords',
            'duration',
            'location',
            'lat',
            'lng',
            'max_participants',
            'period_type',
            'start_time',
            'end_time',
            'currency',
            'tickets',
            'tickettypes',
            'options',
            'price',
            'content_id',
            'show_as_news',
            'overlay_text',
            'enable_bookings'
        ],
        'delete' => true,
        'beforeselect' => 'dashboardEventBeforeSelect',
        'beforecreate' => 'dashboardEventBeforeCreate',
        'beforeupdate' => 'dashboardEventBeforeUpdate',
        'beforedelete' => 'dashboardEventBeforeDelete',
        'subkeys' => [
            'tickettypes' => [
                'tablename' => 'kloko_ticket_types',
                'key' => 'event_id',
                'select' => [
                    'id',
                    'event_id',
                    'name',
                    'description',
                    'price',
                    'currency',
                    'created',
                    'modified'
                ]
            ],
            'ticketoptions' => [
                'tablename' => 'kloko_ticket_options',
                'key' => 'event_id',
                'select' => [
                    'id',
                    'event_id',
                    'ticket_type_id',
                    'name',
                    'description',
                    'price',
                    'currency',
                    'created',
                    'modified'
                ]
            ],
            'tickets' => [
                'tablename' => 'kloko_tickets',
                'key' => 'event_id',
                'select' => 'dashboardEventTicketsSelect'
            ]
        ]
    ],
    "tickettype" => [
        'tablename' => 'kloko_ticket_types',
        'key' => 'id',
        'select' => 'dashboardTicketTypeSelect',
        'create' => [
            'event_id',
            'name',
            'description',
            'price',
            'currency'
        ],
        'update' => [
            'name',
            'description',
            'price',
            'currency'
        ],
        'delete' => true,
        'beforecreate' => 'dashboardTicketTypeBeforeCreate',
        'beforeupdate' => 'dashboardTicketTypeBeforeUpdate',
        'beforedelete' => 'dashboardTicketTypeBeforeDelete'
    ],
    "ticketoption" => [
        'tablename' => 'kloko_ticket_options',
        'key' => 'id',
        'select' => 'dashboardTicketOptionSelect',
        'create' => [
            'event_id',
            'ticket_type_id',
            'name',
            'description',
            'price',
            'currency'
        ],
        'update' => [
            'name',
            'description',
            'price',
            'currency'
        ],
        'delete' => true,
        'beforecreate' => 'dashboardTicketOptionBeforeCreate',
        'beforeupdate' => 'dashboardTicketOptionBeforeUpdate',
        'beforedelete' => 'dashboardTicketOptionBeforeDelete'
    ],
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

    // 4) News items count
    $sql_news = "SELECT DATE_FORMAT(created_at, '{$fmt}') AS period, COUNT(*) AS count\n" .
        "FROM news\n" .
        "WHERE app_id = ? AND created_at BETWEEN ? AND ?\n" .
        "GROUP BY period\n" .
        "ORDER BY period";

    $news = executeSQL($sql_news, [$app_id, $start_dt, $end_dt]);

    // 5) Loyalty systems created
    $sql_loyalty_systems = "SELECT DATE_FORMAT(date_created, '{$fmt}') AS period, COUNT(*) AS count\n" .
        "FROM loyalty_system\n" .
        "WHERE app_id = ? AND date_created BETWEEN ? AND ?\n" .
        "GROUP BY period\n" .
        "ORDER BY period";

    $loyalty_systems = executeSQL($sql_loyalty_systems, [$app_id, $start_dt, $end_dt]);

    // 6) Loyalty stamps added
    $sql_loyalty_stamps = "SELECT DATE_FORMAT(date_created, '{$fmt}') AS period, COUNT(*) AS stamps_added\n" .
        "FROM loyalty_stamp\n" .
        "WHERE app_id = ? AND date_created BETWEEN ? AND ?\n" .
        "GROUP BY period\n" .
        "ORDER BY period";

    $loyalty_stamps = executeSQL($sql_loyalty_stamps, [$app_id, $start_dt, $end_dt]);

    // 7) Loyalty rewards earned (date_earned) and redeemed (date_redeemed)
    $sql_rewards_earned = "SELECT DATE_FORMAT(date_earned, '{$fmt}') AS period, COUNT(*) AS earned\n" .
        "FROM loyalty_reward\n" .
        "WHERE app_id = ? AND date_earned BETWEEN ? AND ?\n" .
        "GROUP BY period\n" .
        "ORDER BY period";

    $rewards_earned = executeSQL($sql_rewards_earned, [$app_id, $start_dt, $end_dt]);

    $sql_rewards_redeemed = "SELECT DATE_FORMAT(date_redeemed, '{$fmt}') AS period, COUNT(*) AS redeemed\n" .
        "FROM loyalty_reward\n" .
        "WHERE app_id = ? AND date_redeemed IS NOT NULL AND date_redeemed BETWEEN ? AND ?\n" .
        "GROUP BY period\n" .
        "ORDER BY period";

    $rewards_redeemed = executeSQL($sql_rewards_redeemed, [$app_id, $start_dt, $end_dt]);

    // 8) Loyalty cards created
    $sql_cards_created = "SELECT DATE_FORMAT(date_created, '{$fmt}') AS period, COUNT(*) AS cards_created\n" .
        "FROM loyalty_card\n" .
        "WHERE app_id = ? AND date_created BETWEEN ? AND ?\n" .
        "GROUP BY period\n" .
        "ORDER BY period";

    $cards_created = executeSQL($sql_cards_created, [$app_id, $start_dt, $end_dt]);

    // 9) Loyalty cards that were allocated at least 1 stamp (based on stamps in period)
    $sql_cards_with_stamps = "SELECT DATE_FORMAT(s.date_created, '{$fmt}') AS period, COUNT(DISTINCT s.card_id) AS cards_with_stamps\n" .
        "FROM loyalty_stamp s\n" .
        "WHERE s.app_id = ? AND s.card_id IS NOT NULL AND s.card_id <> 0 AND s.date_created BETWEEN ? AND ?\n" .
        "GROUP BY period\n" .
        "ORDER BY period";

    $cards_with_stamps = executeSQL($sql_cards_with_stamps, [$app_id, $start_dt, $end_dt]);

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
            'tickets' => $tickets,
            'news' => $news,
            'loyalty_systems' => $loyalty_systems,
            'loyalty_stamps' => $loyalty_stamps,
            'rewards_earned' => $rewards_earned,
            'rewards_redeemed' => $rewards_redeemed,
            'cards_created' => $cards_created,
            'cards_with_stamps' => $cards_with_stamps
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

function dashboardNewsBeforeSelect($config, $data)
{
    global $appId;

    $config['params']['app_id'] = $appId;
    $config['params']['start_dt'] = getParam('start_date', date('Y-m-d', strtotime('-14 days'))) . ' 00:00:00';
    $config['params']['now_dt'] = date('Y-m-d H:i:s');

    return [$config, $data];
}

function dashboardNewsBeforeCreate($config, $data)
{
    global $appId, $userid;

    $data['app_id'] = $appId;
    $data['user_id'] = $userid;

    return [$config, $data];
}

function dashboardNewsBeforeUpdate($config, $id, $data)
{
    global $appId;

    $existing = PrepareExecSQL(
        "SELECT id FROM news WHERE id = ? AND app_id = ? LIMIT 1",
        'is',
        [(int) $id, $appId]
    );

    if (empty($existing)) {
        sendUnauthorizedResponse('News item not found for this tenant');
    }

    return [$config, $data];
}

function dashboardNewsBeforeDelete($config, $id)
{
    global $appId;

    $existing = PrepareExecSQL(
        "SELECT id FROM news WHERE id = ? AND app_id = ? LIMIT 1",
        'is',
        [(int) $id, $appId]
    );

    if (empty($existing)) {
        sendUnauthorizedResponse('News item not found for this tenant');
    }

    return [$config, $id];
}

function dashboardNewsSelect($config, $id = null)
{
    $app_id = isset($config['params']['app_id']) ? $config['params']['app_id'] : '';
    $start_dt = isset($config['params']['start_dt']) ? $config['params']['start_dt'] : date('Y-m-d', strtotime('-14 days')) . ' 00:00:00';
    $now_dt = isset($config['params']['now_dt']) ? $config['params']['now_dt'] : date('Y-m-d H:i:s');

    if (!$app_id) {
        return [];
    }

    $sql = "SELECT n.id, n.title, n.app_id, n.body, n.user_id,\n" .
        "       TRIM(CONCAT(COALESCE(u.firstname, ''), IF(u.lastname IS NULL OR u.lastname = '', '', CONCAT(' ', u.lastname)))) AS user_name,\n" .
        "       n.image_url, n.overlay_text, n.deleted, n.date, n.location, n.lat, n.lng, n.expires, n.created_at, n.updated_at\n" .
        "FROM news n\n" .
        "LEFT JOIN user u ON u.id = n.user_id AND u.app_id = n.app_id\n" .
        "WHERE n.app_id = ?\n" .
        "  AND n.deleted = 'N'\n" .
        "  AND n.date >= ?\n" .
        "  AND n.expires >= ?";

    $params = [$app_id, $start_dt, $now_dt];
    $types = 'sss';

    if ($id !== null && $id !== '') {
        $sql .= "\n  AND n.id = ?";
        $params[] = (int) $id;
        $types .= 'i';
    }

    $sql .= "\nORDER BY date DESC, id DESC";

    return PrepareExecSQL($sql, $types, $params);
}

function dashboardEventBeforeSelect($config, $data)
{
    global $appId;

    $config['params']['app_id'] = $appId;

    return [$config, $data];
}

function dashboardEventBeforeCreate($config, $data)
{
    global $appId, $userid;

    $data['app_id'] = $appId;
    $data['user_id'] = $userid;
    $data['calendar_id'] = $data['calendar_id'] ?? 1; // Default calendar_id
    $data['parent_id'] = $data['parent_id'] ?? 0; // Default parent_id

    return [$config, $data];
}

function dashboardEventBeforeUpdate($config, $id, $data)
{
    global $appId;

    // Verify the event exists and belongs to this tenant
    $existing = PrepareExecSQL(
        "SELECT id FROM kloko_event WHERE id = ? AND app_id = ? LIMIT 1",
        'is',
        [(int) $id, $appId]
    );

    if (empty($existing)) {
        sendUnauthorizedResponse('Event not found for this tenant');
    }

    // Remove any fields that should not be updated
    unset($data['user_id']);
    unset($data['user_name']);
    unset($data['created']);
    unset($data['modified']);
    unset($data['app_id']);
    unset($data['calendar_id']);
    unset($data['parent_id']);
    unset($data['event_template_id']);

    return [$config, $data];
}

function dashboardEventBeforeDelete($config, $id)
{
    global $appId;

    // Verify the event exists and belongs to this tenant
    $existing = PrepareExecSQL(
        "SELECT id FROM kloko_event WHERE id = ? AND app_id = ? LIMIT 1",
        'is',
        [(int) $id, $appId]
    );

    if (empty($existing)) {
        sendUnauthorizedResponse('Event not found for this tenant');
    }

    return [$config, $id];
}

function dashboardEventSelect($config, $id = null)
{
    $app_id = isset($config['params']['app_id']) ? $config['params']['app_id'] : '';

    if (!$app_id) {
        return [];
    }

    $sql = "SELECT e.id, e.title, e.app_id, e.description, e.user_id,\n" .
        "       TRIM(CONCAT(COALESCE(u.firstname, ''), IF(u.lastname IS NULL OR u.lastname = '', '', CONCAT(' ', u.lastname)))) AS user_name,\n" .
        "       e.image, e.event_type, e.keywords, e.duration, e.location, e.lat, e.lng,\n" .
        "       e.max_participants, e.period_type, e.start_time, e.end_time, e.currency,\n" .
        "       e.tickets, e.tickettypes, e.options, e.price, e.content_id,\n" .
        "       e.show_as_news, e.overlay_text, e.enable_bookings,\n" .
        "       COALESCE(SUM(t.quantity), 0) AS total_tickets_sold,\n" .
        "       COALESCE(SUM(t.price * t.quantity), 0) AS total_tickets_value,\n" .
        "       e.created, e.modified\n" .
        "FROM kloko_event e\n" .
        "LEFT JOIN user u ON u.id = e.user_id AND u.app_id = e.app_id\n" .
        "LEFT JOIN kloko_tickets t ON t.event_id = e.id\n" .
        "WHERE e.app_id = ?\n";

    $params = [$app_id];
    $types = 's';

    if ($id !== null && $id !== '') {
        $sql .= "  AND e.id = ?";
        $params[] = (int) $id;
        $types .= 'i';
    }

    $sql .= "\nGROUP BY e.id\n" .
        "ORDER BY e.start_time DESC, e.id DESC";

    return PrepareExecSQL($sql, $types, $params);
}

function dashboardEventTicketsSelect($config, $id = null)
{
    // For subkey requests, event_id is provided in $config['where']['event_id'] by v2apicore.
    $event_id = null;
    if ($id !== null && $id !== '') {
        $event_id = (int) $id;
    } elseif (isset($config['where']['event_id']) && $config['where']['event_id'] !== '') {
        $event_id = (int) $config['where']['event_id'];
    }

    if (!$event_id) {
        return [];
    }

    $sql = "SELECT t.id, t.user_id,\n" .
        "       TRIM(CONCAT(COALESCE(u.firstname, ''), IF(u.lastname IS NULL OR u.lastname = '', '', CONCAT(' ', u.lastname)))) AS user_name,\n" .
        "       t.event_id, t.ticket_type_id,\n" .
        "       tt.name AS ticket_type_name,\n" .
        "       t.ticket_option_id, t.title, t.description,\n" .
        "       t.quantity, t.currency, t.price, t.order_item_id,\n" .
        "       t.created, t.modified\n" .
        "FROM kloko_tickets t\n" .
        "LEFT JOIN user u ON u.id = t.user_id\n" .
        "LEFT JOIN kloko_ticket_types tt ON tt.id = t.ticket_type_id\n" .
        "WHERE t.event_id = ?\n" .
        "ORDER BY t.id DESC";

    $types = 'i';
    $params = [$event_id];

    if ((string) getParam('debug_sql', '0') === '1') {
        return [[
            'debug_sql' => $sql,
            'debug_types' => $types,
            'debug_params' => $params
        ]];
    }

    return PrepareExecSQL($sql, $types, $params);
}

function dashboardTicketTypeBeforeCreate($config, $data)
{
    global $appId;

    // Verify event exists and belongs to this tenant
    if (!isset($data['event_id']) || !$data['event_id']) {
        sendErrorResponse('event_id is required');
    }

    $event = PrepareExecSQL(
        "SELECT id FROM kloko_event WHERE id = ? AND app_id = ? LIMIT 1",
        'is',
        [(int) $data['event_id'], $appId]
    );

    if (empty($event)) {
        sendUnauthorizedResponse('Event not found for this tenant');
    }

    return [$config, $data];
}

function dashboardTicketTypeBeforeUpdate($config, $id, $data)
{
    global $appId;

    // Verify ticket type exists
    $existing = PrepareExecSQL(
        "SELECT event_id FROM kloko_ticket_types WHERE id = ? LIMIT 1",
        'i',
        [(int) $id]
    );

    if (empty($existing)) {
        sendUnauthorizedResponse('Ticket type not found');
    }

    $event_id = $existing[0]['event_id'];

    // Verify the associated event belongs to this tenant
    $event = PrepareExecSQL(
        "SELECT id FROM kloko_event WHERE id = ? AND app_id = ? LIMIT 1",
        'is',
        [(int) $event_id, $appId]
    );

    if (empty($event)) {
        sendUnauthorizedResponse('Event not found for this tenant');
    }

    // Remove protected fields
    unset($data['event_id']);
    unset($data['created']);
    unset($data['modified']);

    return [$config, $data];
}

function dashboardTicketTypeBeforeDelete($config, $id)
{
    global $appId;

    // Verify ticket type exists
    $existing = PrepareExecSQL(
        "SELECT event_id FROM kloko_ticket_types WHERE id = ? LIMIT 1",
        'i',
        [(int) $id]
    );

    if (empty($existing)) {
        sendUnauthorizedResponse('Ticket type not found');
    }

    $event_id = $existing[0]['event_id'];

    // Verify the associated event belongs to this tenant
    $event = PrepareExecSQL(
        "SELECT id FROM kloko_event WHERE id = ? AND app_id = ? LIMIT 1",
        'is',
        [(int) $event_id, $appId]
    );

    if (empty($event)) {
        sendUnauthorizedResponse('Event not found for this tenant');
    }

    return [$config, $id];
}

function dashboardTicketTypeSelect($config, $id = null)
{
    $sql = "SELECT id, event_id, name, description, price, currency, created, modified\n" .
        "FROM kloko_ticket_types\n";

    $params = [];
    $types = '';

    if ($id !== null && $id !== '') {
        $sql .= "WHERE id = ?\n";
        $params[] = (int) $id;
        $types = 'i';
    }

    $sql .= "ORDER BY id DESC";

    return PrepareExecSQL($sql, $types, $params);
}

function dashboardTicketOptionBeforeCreate($config, $data)
{
    global $appId;

    // Verify event exists and belongs to this tenant
    if (!isset($data['event_id']) || !$data['event_id']) {
        sendErrorResponse('event_id is required');
    }

    $event = PrepareExecSQL(
        "SELECT id FROM kloko_event WHERE id = ? AND app_id = ? LIMIT 1",
        'is',
        [(int) $data['event_id'], $appId]
    );

    if (empty($event)) {
        sendUnauthorizedResponse('Event not found for this tenant');
    }

    // Verify ticket type exists if provided
    if (isset($data['ticket_type_id']) && $data['ticket_type_id']) {
        $ticketType = PrepareExecSQL(
            "SELECT id FROM kloko_ticket_types WHERE id = ? AND event_id = ? LIMIT 1",
            'ii',
            [(int) $data['ticket_type_id'], (int) $data['event_id']]
        );

        if (empty($ticketType)) {
            sendUnauthorizedResponse('Ticket type not found for this event');
        }
    }

    return [$config, $data];
}

function dashboardTicketOptionBeforeUpdate($config, $id, $data)
{
    global $appId;

    // Verify ticket option exists
    $existing = PrepareExecSQL(
        "SELECT event_id FROM kloko_ticket_options WHERE id = ? LIMIT 1",
        'i',
        [(int) $id]
    );

    if (empty($existing)) {
        sendUnauthorizedResponse('Ticket option not found');
    }

    $event_id = $existing[0]['event_id'];

    // Verify the associated event belongs to this tenant
    $event = PrepareExecSQL(
        "SELECT id FROM kloko_event WHERE id = ? AND app_id = ? LIMIT 1",
        'is',
        [(int) $event_id, $appId]
    );

    if (empty($event)) {
        sendUnauthorizedResponse('Event not found for this tenant');
    }

    // Remove protected fields
    unset($data['event_id']);
    unset($data['ticket_type_id']);
    unset($data['created']);
    unset($data['modified']);

    return [$config, $data];
}

function dashboardTicketOptionBeforeDelete($config, $id)
{
    global $appId;

    // Verify ticket option exists
    $existing = PrepareExecSQL(
        "SELECT event_id FROM kloko_ticket_options WHERE id = ? LIMIT 1",
        'i',
        [(int) $id]
    );

    if (empty($existing)) {
        sendUnauthorizedResponse('Ticket option not found');
    }

    $event_id = $existing[0]['event_id'];

    // Verify the associated event belongs to this tenant
    $event = PrepareExecSQL(
        "SELECT id FROM kloko_event WHERE id = ? AND app_id = ? LIMIT 1",
        'is',
        [(int) $event_id, $appId]
    );

    if (empty($event)) {
        sendUnauthorizedResponse('Event not found for this tenant');
    }

    return [$config, $id];
}

function dashboardTicketOptionSelect($config, $id = null)
{
    $sql = "SELECT id, event_id, ticket_type_id, name, description, price, currency, created, modified\n" .
        "FROM kloko_ticket_options\n";

    $params = [];
    $types = '';

    if ($id !== null && $id !== '') {
        $sql .= "WHERE id = ?\n";
        $params[] = (int) $id;
        $types = 'i';
    }

    $sql .= "ORDER BY id DESC";

    return PrepareExecSQL($sql, $types, $params);
}
