<?php
require_once "../utils.php";
require_once "../dbutils.php";

/**
 * Get start and end date for a specific or previous month.
 * 
 * @param string|null $month A specific month in 'YYYY-MM' format. Defaults to previous month if null.
 * @return array Returns an array with 'start_date' and 'end_date' for the specified or previous month.
 */
function getDateRange($month = null) {
    if (!$month) {
        $month = date('Y-m', strtotime('first day of last month'));
    }
    $start_date = date('Y-m-01', strtotime($month));
    $end_date = date('Y-m-t', strtotime($month));
    return [$start_date, $end_date];
}

/**
 * Updates or inserts loyalty summary data for a given month and optional venue_id.
 * 
 * @param string|null $month A specific month in 'YYYY-MM' format, defaults to previous month.
 * @param int|null $venue_id Specific venue_id to filter by, defaults to all venues.
 */
function updateLoyaltySummary($month = null, $venue_id = null) {
    list($start_date, $end_date) = getDateRange($month);
    
    // SQL query to fetch aggregated data
    $sql = "
        SELECT 
            ls.venue_id AS venue_id,
            ls.id AS system_id,
            COUNT(lstamp.id) AS stamps,
            COUNT(DISTINCT lr.id) AS earned,
            COUNT(DISTINCT CASE WHEN lr.date_redeemed IS NOT NULL THEN lr.id END) AS redeemed
        FROM 
            loyalty_system ls
        LEFT JOIN 
            loyalty_card lc ON lc.system_id = ls.id
        LEFT JOIN 
            loyalty_stamp lstamp ON lstamp.card_id = lc.id AND lstamp.date_created BETWEEN ? AND ?
        LEFT JOIN 
            loyalty_reward lr ON lr.system_id = ls.id AND lr.date_earned BETWEEN ? AND ?
        WHERE 
            (? IS NULL OR ls.venue_id = ?)
        GROUP BY 
            ls.venue_id, ls.id;
    ";
    
    // Execute the query with the specified date range and optional venue_id
    $params = [$start_date, $end_date, $start_date, $end_date, $venue_id, $venue_id];
    $data = PrepareExecSQL($sql, 'sssiii', $params);
    
    foreach ($data as $row) {
        // Check if a record exists in loyalty_summary for the date range and venue_id/system_id
        $checkSql = "
            SELECT id FROM loyalty_summary 
            WHERE venue_id = ? AND system_id = ? AND start_date = ? AND end_date = ?
        ";
        $checkParams = [$row['venue_id'], $row['system_id'], $start_date, $end_date];
        $existingRecord = PrepareExecSQL($checkSql, 'iiss', $checkParams);
        
        if ($existingRecord) {
            // Update existing record
            $updateSql = "
                UPDATE loyalty_summary 
                SET stamps = ?, earned = ?, redeemed = ?, date_modified = CURRENT_TIMESTAMP 
                WHERE id = ?
            ";
            $updateParams = [$row['stamps'], $row['earned'], $row['redeemed'], $existingRecord[0]['id']];
            PrepareExecSQL($updateSql, 'iiii', $updateParams);
        } else {
            // Insert new record
            $insertSql = "
                INSERT INTO loyalty_summary (venue_id, system_id, start_date, end_date, stamps, earned, redeemed, date_created, date_modified) 
                VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ";
            $insertParams = [$row['venue_id'], $row['system_id'], $start_date, $end_date, $row['stamps'], $row['earned'], $row['redeemed']];
            PrepareExecSQL($insertSql, 'iissiii', $insertParams);
        }
    }
}

// Retrieve parameters from the request
$month = getParam('month', date('Y-m', strtotime('first day of last month')));  // Default to last month
$venue_id = getParam('venue_id', null);  // Specific venue_id or null for all venues

// Run the update
updateLoyaltySummary($month, $venue_id);

// Send a response
echo json_encode([
    "status" => "success",
    "message" => "Loyalty summary updated successfully.",
    "month" => $month,
    "venue_id" => $venue_id
]);

?>
