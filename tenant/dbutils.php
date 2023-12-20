<?php
$mysqli = null;
// $config = array(
//   "database" => array(
//     "server" => 'cairns.co.za',
//     "username" => 'cairnsco_justdance',
//     "password" => '4pyNU}G_wWIf',
//     "database" => 'cairnsco_justdance'
//   )
// );

$config = array(
  "database" => array(
    "server" => 'cairns.co.za',
    "username" => 'cairnsco_cairnsgames',
    "password" => 'cairnsco_cairnsgames',
    "database" => 'cairnsco_cairnsgames'
  )
);

// localhost/
// cairnsgames.co.za/dev/php

$server = $_SERVER['SERVER_NAME'];

// if ($server == "localhost") {
//   $config = array(
//     "database" => array(
//       "server" => 'localhost',
//       "username" => 'membership',
//       "password" => 'membership',
//       "database" => 'membership'
//     )
//   );
// }

if ($mysqli == null) {
  $mysqli = mysqli_connect($config["database"]["server"], $config["database"]["username"], $config["database"]["password"], $config["database"]["database"]);
  if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
  }
}


function lastError()
{
  global $mysqli;
  return mysqli_error($mysqli);
}

function PrepareExecSQL($sql, $pars = '', $params = [])
{
  global $mysqli;
  $result = db_query($mysqli, $sql, $pars, $params);

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