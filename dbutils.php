<?php
include_once dirname(__FILE__)."/corsheaders.php";
include_once dirname(__FILE__)."/dbconfig.php";

$mysqli = null;
$writeStatementLog = false;

if ($mysqli == null) {
  $mysqli = mysqli_connect($dbconfig["server"], $dbconfig["username"], $dbconfig["password"], $dbconfig["database"]);
  if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
  }
}

function lastError()
{
  global $mysqli;
  return mysqli_error($mysqli);
}
/**
 * Executes a SQL query using the global MySQLi connection and optionally logs the executed statement.
 *
 * @param string $sql The SQL query string to be executed.
 * @param string $pars (Optional) A string indicating the types of parameters ('s', 'i', 'd', etc.) to bind to the query.
 * @param array $params (Optional) An array of parameters to bind to the SQL query.
 *
 * @global mysqli $mysqli The MySQLi connection object.
 * @global bool $writeStatementLog A flag indicating whether to log executed SQL statements.
 *
 * @return mixed The result of the executed query as a php array of records.
 */
function PrepareExecSQL($sql, $pars = '', $params = [])
{
  global $mysqli, $writeStatementLog;
  $result = db_query($mysqli, $sql, $pars, $params);

  if ($writeStatementLog) {
    $logsql = "insert into statementlog (sqlstr, sss, params) values (?,?,?)";
    $logparams = [$sql, $pars, json_encode($params)];
    $logparams = str_replace("\"", "'", $logparams);
    $logresult = db_query($mysqli, $logsql, 'sss', $logparams);
  }


  return $result;
}

// https://stackoverflow.com/questions/24363755/mysqli-bind-results-to-an-array
function db_query($dbconn, $sql, $params_types = '', $params = [])
{
    // Determine query type
    $query_type = strtoupper(substr(trim($sql), 0, 4));

    $stmt = mysqli_stmt_init($dbconn);
    if (!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Failed to prepare statement: " . mysqli_error($dbconn));
    }

    if (!empty($params_types)) {
        if (!is_array($params) || strlen($params_types) !== count($params)) {
            throw new Exception("Parameter types and parameter count mismatch");
        }
        mysqli_stmt_bind_param($stmt, $params_types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Query execution failed: " . mysqli_error($dbconn));
    }

    if ($query_type === 'SELE' || $query_type === '(SEL') {
        $result = mysqli_stmt_result_metadata($stmt);
        $columns = [];
        $row = [];
        $return_array = [];

        while ($field = mysqli_fetch_field($result)) {
            $columns[] = $field->name;
            $row[] = null;
        }

        // Bind results to the array
        mysqli_stmt_bind_result($stmt, ...$row);

        while (mysqli_stmt_fetch($stmt)) {
            $assoc_row = [];
            foreach ($columns as $index => $col) {
                $assoc_row[$col] = $row[$index];
            }
            $return_array[] = $assoc_row;
        }

        return $return_array;
    } elseif ($query_type === 'INSE') {
        return mysqli_insert_id($dbconn);
    } else {
        return mysqli_stmt_affected_rows($stmt);
    }
}



?>