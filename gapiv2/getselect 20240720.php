<?php
function manageSqlStatement($sql, $key, $where)
{
    // Initialize types and params
    $types = '';
    $params = [];

    // Replace {name} variables in the SQL statement
    $sql = preg_replace_callback('/\{(\w+)\}/', function ($matches) use (&$types, &$params) {
        $paramName = $matches[1];
        $paramValue = getParam($paramName, '');

        // Assuming all parameters are strings for simplicity
        $types .= 's'; 
        $params[] = $paramValue;

        return '?';
    }, $sql);

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

    echo "$sql", "\n";
    var_dump($types);
    var_dump($params);

    return [
        'query' => $finalSql,
        'types' => $types,
        'params' => $params
    ];
}
