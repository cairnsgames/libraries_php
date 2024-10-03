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
 * @return mixed The result of the executed query.
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
function db_query($dbconn, $sql, $params_types, $params)
{ // pack dynamic number of remaining arguments into array
  // GET QUERY TYPE
  $query_type = strtoupper(substr(trim($sql), 0, 4));

  $stmt = mysqli_stmt_init($dbconn);
  if (mysqli_stmt_prepare($stmt, $sql)) {
    if ($params_types != "") {
      mysqli_stmt_bind_param($stmt, $params_types, ...$params); // unpack
    }
    mysqli_stmt_execute($stmt) or die("Failed: " . mysqli_error($dbconn));

    if ('SELE' == $query_type || '(SEL' == $query_type) {
      $result = mysqli_stmt_result_metadata($stmt);
      list($columns, $columns_vars) = array(array(), array());
      while ($field = mysqli_fetch_field($result)) {
        $columns[] = $field->name;
        $columns_vars[] = &${$field->name};
      }
      call_user_func_array('mysqli_stmt_bind_result', array_merge(array($stmt), $columns_vars));
      $return_array = array();
      while (mysqli_stmt_fetch($stmt)) {
        $row = array();
        foreach ($columns as $col) {
          $row[$col] = ${$col};
        }
        $return_array[] = $row;
      }

      return $return_array;
    } // end query_type SELECT
    else if ('INSE' == $query_type) {
      return mysqli_insert_id($dbconn);
    }
    return 1;
  }
}


?>