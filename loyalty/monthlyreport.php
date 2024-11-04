<?php
require_once "../utils.php";
require_once "../dbutils.php";
require_once "../settings/settingsfunctions.php";

/**
 * Get start and end date for a specific or previous month.
 * 
 * @param string|null $month A specific month in 'YYYY-MM' format. Defaults to previous month if null.
 * @return array Returns an array with 'start_date' and 'end_date' for the specified or previous month.
 */
function getDateRange($month = null)
{
  if (!$month) {
    $month = date('Y-m', strtotime('first day of last month'));
  }
  $start_date = date('Y-m-01', strtotime($month));
  $end_date = date('Y-m-t', strtotime($month));
  return [$start_date, $end_date];
}

/**
 * Adds a new order and line item for stamps if an order for the specified month does not already exist,
 * or updates an existing order if its status is not "paid" or "completed".
 *
 * @param int $user_id User ID to associate the order with.
 * @param string $order_month Month of the order in 'YYYY-MM' format.
 * @param int $stamp_quantity Number of stamps to include in the order.
 */
function createOrUpdateOrderForStamps($user_id, $order_month, $stamp_quantity)
{
  // Convert order month to start of the month for consistency
  $order_month_start = date('Y-m-01', strtotime($order_month));

  // Get the app ID for use with settings
  $app_id = getAppId();

  // Retrieve stamp price from settings
  $stamp_price = getSettingValueForUser($app_id, $user_id, 'stamp_price');
  if (!$stamp_price) {
    $stamp_price = 3; // Default price if setting is not found
  }

  // Check for existing order for the user in the specified month with description "Loyalty Invoice"
  $checkOrderSql = "
        SELECT id, status 
        FROM breezo_order 
        WHERE user_id = ? AND order_month = ? AND order_details = 'Loyalty Invoice'
    ";
  $existingOrder = PrepareExecSQL($checkOrderSql, 'is', [$user_id, $order_month_start]);

  // If order exists and status is "paid" or "completed", exit without changes
  if ($existingOrder && in_array($existingOrder[0]['status'], ['paid', 'completed'])) {
    echo json_encode(["status" => "no_update", "message" => "Order already completed or paid for the month."]);
    return;
  }

  // Calculate total price based on retrieved or default price per stamp
  $total_price = $stamp_quantity * $stamp_price;

  if ($existingOrder) {
    // Update existing order if it exists and status is not "paid" or "completed"
    $order_id = $existingOrder[0]['id'];
    $updateOrderSql = "
            UPDATE breezo_order 
            SET total_price = ?, modified = CURRENT_TIMESTAMP 
            WHERE id = ?
        ";
    PrepareExecSQL($updateOrderSql, 'di', [$total_price, $order_id]);

    // Remove existing line items and add a new one for stamps
    $deleteItemsSql = "DELETE FROM breezo_order_item WHERE order_id = ?";
    PrepareExecSQL($deleteItemsSql, 'i', [$order_id]);

  } else {
    // Insert a new order if no matching record exists
    $insertOrderSql = "
            INSERT INTO breezo_order (user_id, order_details, order_month, status, total_price, created, modified)
            VALUES (?, 'Loyalty Invoice', ?, 'pending', ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ";
    PrepareExecSQL($insertOrderSql, 'isd', [$user_id, $order_month_start, $total_price]);

    // Retrieve the new order ID
    $order_id = PrepareExecSQL("SELECT LAST_INSERT_ID() AS id", '')[0]['id'];
  }

  // Insert line item for stamps with item_type = 2, price from settings or default, and the specified quantity
  $insertItemSql = "
        INSERT INTO breezo_order_item (order_id, item_type_id, item_id, item_description, supplier_id, price, quantity, commission_rate, created, modified)
        VALUES (?, 2, 1, 'Loyalty Stamp', 1, ?, ?, 10.00, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ";
  PrepareExecSQL($insertItemSql, 'iid', [$order_id, $stamp_price, $stamp_quantity]);

  return [
    "status" => "success",
    "message" => "Order for stamps created or updated.",
    "user_id" => $user_id,
    "order_id" => $order_id,
    "price" => $stamp_price,
    "quantity" => $stamp_quantity,
    "total_price" => $total_price
  ];
}

/**
 * Updates or inserts loyalty summary data for a given month and optional venue_id.
 * 
 * @param string|null $month A specific month in 'YYYY-MM' format, defaults to previous month.
 * @param int|null $venue_id Specific venue_id to filter by, defaults to all venues.
 */
function updateLoyaltySummary($month = null, $venue_id = null)
{
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

  $out = [];

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

    $out[] = createOrUpdateOrderForStamps($row['venue_id'], $start_date, $row['stamps']);
  }
  return $out;
}

// Retrieve parameters from the request
$month = getParam('month', date('Y-m', strtotime('first day of last month')));  // Default to last month
$venue_id = getParam('venue_id', null);  // Specific venue_id or null for all venues

// Run the update
$out = updateLoyaltySummary($month, $venue_id);

// Send a response
echo json_encode([
  "status" => "success",
  "message" => "Loyalty summary updated successfully.",
  "month" => $month,
  "venue_id" => $venue_id,
  "items" => $out
]);


