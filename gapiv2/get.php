<?php

// Function to handle limit and order by from query string
function getLimitAndOrderBy()
{
    $limit = '';
    $orderBy = '';

    $page = getParam('page', 1);
    $pageSize = getParam('pageSize', 20);
    $order = getParam('order', null);

    if ($page && $pageSize) {
        $offset = ($page - 1) * $pageSize;
        $limit = "LIMIT $offset, $pageSize";
    }

    if ($order) {
        $orderDirection = strtoupper(getParam('orderDirection', "ASC")) === 'DESC' ? 'DESC' : 'ASC';
        $orderBy = "ORDER BY $order $orderDirection";
    }

    return [$limit, $orderBy];
}

function SelectData($config, $id = null)
{
    global $conn;

    // echo "Selecting data: ";
    // var_dump($config);

    if (isset($config['beforeselect']) && function_exists($config['beforeselect'])) {
        $config = call_user_func($config['beforeselect'], $config);
    }

    if (is_string($config['select'])) {
        $query = strtr($config['select'], $config['values']);
    } else {
        $fields = implode(", ", $config['select']);
        $where = "1=1";
        if (isset($config['where'])) {
            foreach ($config['where'] as $key => $value) {
                $where .= " AND $key = ?";
            }
        }
        if ($id) {
            $where .= " AND " . $config['key'] . " = ?";
        }
        $query = "SELECT $fields FROM " . $config['tablename'] . " WHERE $where " . $config['orderBy'] . " " . $config['limit'];
    }

    // echo "SQL: $query";

    $stmt = $conn->prepare($query);

    if (isset($config['where'])) {
        $types = str_repeat('s', count($config['where']));
        $params = array_values($config['where']);
        if ($id) {
            $types .= 's';
            $params[] = $id;
        }
        $stmt->bind_param($types, ...$params);
    } else if ($id){
        $types = 's';
        $params[] = $id;
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);

    if (isset($config['afterselect']) && function_exists($config['afterselect'])) {
        $rows = call_user_func($config['afterselect'], $config, $rows);
    }

    $stmt->close();

    return $rows;
}