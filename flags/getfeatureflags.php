<?php

include_once dirname(__FILE__)."/../dbutils.php";
include_once dirname(__FILE__)."/../utils.php";


$appid = getAppId();
$out = ["message" => "featureflags"];
$steps = [];

if ($appid != "") {
    $sql = "SELECT
    `name`, seq, fieldname, fieldvalues, val
   FROM flags, flagrules, flagvalues
   WHERE flags.app_id = ?
   AND flagrules.flagid = flags.id
   AND flagvalues.id = flagrules.valueid
   UNION
   SELECT `name`, 1000, \"\", \"\", val
   FROM flags, flagvalues
   WHERE flags.app_id = ?
   AND flagvalues.flagid = flags.id
   AND flagvalues.def = 1
   ORDER BY 2";
   
    $params = array($appid, $appid);
    $rowresult = PrepareExecSQL($sql, "ss", $params);
    // $out = array("message" => "featureflags", "data" => $rowresult);
    $res = [];
    foreach ($rowresult as $row) {
        $row["fieldvalues"] = explode(",", $row["fieldvalues"]);

        $steps[]= "found new row" . $row["name"] . "\n";
        $steps[]=  "-------------\n";
        $steps[]= ($row);
        $steps[]= "-------------\n";
        if (!isset($res[$row["name"]])) {
            $steps[]=  "found flag" . $row["name"] . "\n";
            if ($row["fieldname"] == "") {
                $steps[]= "found blank fieldname - default: " . $row["val"] . "\n";
                $res[$row["name"]] = $row["val"];
            } else {
                $value = getParam($row["fieldname"], "");

                $steps[]= "found parameter: " . $row["fieldname"] . " = " . $value . "\n";
                if ($value != "") {
                    $options = $row["fieldvalues"];
                    if (!is_array($options)) {
                        $options = [$options];
                    }
                    $steps[]= ($options);
                    $steps[]= "looking for " . $value . "in " . json_encode($row["fieldvalues"]) . "\n";
                    if (in_array($value, $options)) {
                        $res[$row["name"]] = $row["val"];
                    }
                }
            }
        }
    }
    ;
    $out["result"] = $res;
    // $out["steps"] = $steps;
} else {
    $out = array(
        "error" => "All parameters are required!"
    );
}

http_response_code(200);
echo json_encode($out);