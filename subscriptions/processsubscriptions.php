<?php
/* Do subscription work here, as opposed to ticket work currently in breezo.php */

function processSubscriptionOrder($orderId, $order, $orderitems, $user)
{

  echo "Order: " . json_encode($order) . "<br/>\n";
  echo "Order Items: " . json_encode($orderitems) . "<br/>\n";

  $log=[];
  $log[] = "Order: " . json_encode($order);
  $log[] = "Order Items: " . json_encode($orderitems);

  foreach ($orderitems as $orderitem) {
    $sql = "select * from subscription_property where subscription_id = ?";
    $params = array($orderitem["item_id"]);
    $properties = PrepareExecSQL($sql, "s", $params);
    foreach ($properties as $property) {
      if ($property["name"] == "days" && $property["value"] > 0) {
        echo "Adding subscription for " . $property["value"] . " days<br/>\n";
        $log[] = "Adding subscription for " . $property["value"] . " days";
        $sql = "insert into subscription_user (subscription_id, user_id, days, started, active) values (?,?,?,?,?)";
        $params = array($orderitem["item_id"], $user["id"], $property["value"], 0, 0);
        $id = PrepareExecSQL($sql, "ssiii", $params);

        $sql = "select * from subscription_user where user_id = ? and active = 1";
        $params = array($user["id"]);
        $activesubscriptions = PrepareExecSQL($sql, "i", $params);
        if (count($activesubscriptions) == 0) {
          $sql = "update subscription_user set active = 1 where id = ?";
          $params = array($id);
          PrepareExecSQL($sql, "i", $params);
        }
      } else {
        if ($property["name"] != "days") {
          echo "Adding credits for " . $property["value"] . " " . $property["name"] . "<br/>\n";
          $log[] = "Adding credits for " . $property["value"] . " " . $property["name"];
          $sql = "insert into subscription_user_credits (user_id, name, value) values (?,?,?) on duplicate key update value = value + ?";
          $params = array($user["id"], $property["name"], $property["value"], $property["value"]);
          PrepareExecSQL($sql, "issi", $params);
        }
      }
    }
  }
  /* 5. mark order complete */
  updateOrderStatus($orderId, "completed");
  $log[] = "Order marked as completed";

  PrepareExecSQL("insert into webhook_logs (data) values (?)", "s", array(json_encode($log)));

}