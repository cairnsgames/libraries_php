<?php

function buildQuery($config)
{
    // Initialize components
    $select = isset($config['select']) ? $config['select'] : '';
    $where = isset($config['where']) ? $config['where'] : [];
    $fields = isset($config['fields']) ? $config['fields'] : [];
    $params = [];
    $types = '';

    // Extract query string parameters
    $queryParams = [];
    parse_str($_SERVER['QUERY_STRING'], $queryParams);

    // Extract special parameters from $config
    $specialParams = isset($config['params']) ? $config['params'] : [];

    // Combine query string parameters and special parameters
    $allParams = array_merge($queryParams, $specialParams);

    // Track unresolved placeholders
    $unresolvedParams = [];

    // Replace placeholders in SELECT statement with ?
    preg_match_all('/{(\w+)}/', $select, $matches);
    foreach ($matches[1] as $paramName) {
        if (isset($allParams[$paramName])) {
            $select = str_replace('{' . $paramName . '}', '?', $select);
            $params[] = $allParams[$paramName];
            $types .= 's'; // Assuming all parameters are strings for simplicity
        } else {
            // Track unresolved parameters
            $unresolvedParams[] = $paramName;
        }
    }

    // Throw an error if there are any unresolved placeholders
    if (!empty($unresolvedParams)) {
        $unresolvedList = implode(', ', $unresolvedParams);
        throw new Exception('Error: Unresolved placeholders in query: ' . $unresolvedList);
    }

    // Modify the SELECT statement based on fields and query string
    $whereClauses = [];
    foreach ($fields as $field) {
        foreach ($field as $fieldName => $operator) {
            if (isset($allParams[$fieldName])) {
                $value = $allParams[$fieldName];
                $params[] = $operator === "in" ? "%{$value}%" : $value;
                $types .= $operator === "in" ? 's' : 's'; // Assume all fields are strings for simplicity

                if ($operator === "in") {
                    $whereClauses[] = "{$fieldName} LIKE ?";
                } else {
                    $whereClauses[] = "{$fieldName} = ?";
                }
            }
        }
    }

    // Build WHERE clause
    if (!empty($whereClauses)) {
        $whereClause = implode(' AND ', $whereClauses);
        if (isset($config['select'])) {
            $select = $select . " WHERE " . $whereClause;
        } else {
            $select = "SELECT * FROM {$config['tablename']} WHERE " . $whereClause;
        }
    } else {
        $select = $select . (isset($config['select']) ? "" : " FROM {$config['tablename']}");
    }

    // Add any additional WHERE conditions from the config
    foreach ($where as $column => $value) {
        if ($value !== '') {
            if (empty($whereClauses)) {

                if (preg_match('/\bWHERE\b(?![^()]*\))/', $select)) {
                    $select .= " AND {$column} = ?";
                } else {
                    $select .= " WHERE {$column} = ?";
                }
            } else {
                $select .= " AND {$column} = ?";
            }
            $params[] = $value;
            $types .= 's'; // Assuming all `where` values are strings
        }
    }

    // Add ORDER BY, GROUP BY, LIMIT if present in the SELECT statement
    $finalSql = $select;

    // echo "$finalSql", "\n";
    // var_dump("TYPES", $types,"\n");
    // var_dump("PARAMS", $params,"\n");

    // Return the result
    return [
        'query' => $finalSql,
        'types' => $types,
        'params' => $params
    ];
}
