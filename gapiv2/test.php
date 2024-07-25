<?php

function manageSqlStatement($sql, $key, $where)
{
    // Initialize types and params
    $types = '';
    $params = [];

    // Find the position of GROUP BY, ORDER BY, and other clauses
    $clauses = ['GROUP BY', 'ORDER BY', 'LIMIT'];
    $clausePos = strlen($sql);

    foreach ($clauses as $clause) {
        $pos = stripos($sql, $clause);
        if ($pos !== false && $pos < $clausePos) {
            $clausePos = $pos;
        }
    }

    // Extract the main part of the SQL and the clause part
    $mainSql = substr($sql, 0, $clausePos);
    $clauseSql = substr($sql, $clausePos);

    // Initialize the WHERE clause
    $whereClause = '';

    // Check if the SQL statement already contains a WHERE clause
    if (stripos($mainSql, 'WHERE') !== false) {
        $whereClause = ' AND ';
    } else {
        $whereClause = ' WHERE ';
    }

    // Handle the 'key' parameter as a special case
    if (isset($where['key'])) {
        $whereClause .= "$key = ?";
        $types .= 's'; // Assuming the key parameter is a string
        $params[] = $where['key'];
        unset($where['key']); // Remove the key parameter from the where array
    }

    // Add other where conditions
    foreach ($where as $column => $value) {
        if (empty($params)) {
            $whereClause .= "$column = ?";
        } else {
            $whereClause .= " AND $column = ?";
        }
        $types .= 's'; // Assuming all other where parameters are strings
        $params[] = $value;
    }

    // Combine the parts
    $finalSql = $mainSql . $whereClause . ' ' . $clauseSql;

    return [
        'sql' => $finalSql,
        'types' => $types,
        'params' => $params
    ];
}

// Examples
$sql1 = "select id, name from tablex";
$where1 = [
    'id' => 2,
    'age' => 3
];

$result1 = manageSqlStatement($sql1, 'id', $where1);
print_r($result1);

$sql2 = "select id, name, count(records) from tabY group by id";
$where2 = [
    'id' => 2,
    'age' => 3
];

$result2 = manageSqlStatement($sql2, 'id', $where2);
print_r($result2);

$sql3 = "select a.id, a.name, b.id, b.name from A, B where a.id = b.secondid";
$where3 = [
    'a.id' => 2,
    'age' => 3
];

$result3 = manageSqlStatement($sql3, 'a.id', $where3);
print_r($result3);

$sql4 = "SELECT card.id, stamps_collected, firstname, lastname, email FROM loyalty_card card, user WHERE card.user_id = user.id";
$where4 = [
    'key' => 13,
    "app_id" => "12345"
];

$result4 = manageSqlStatement($sql4, 'system_id', $where4);
print_r($result4);
