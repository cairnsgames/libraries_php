<?php

include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../utils.php";
include_once dirname(__FILE__) . "/../auth/authfunctions.php";

include_once dirname(__FILE__) . "/../gapiv2/gapi.php";
include_once dirname(__FILE__) . "/../kloko/kloko.php";

/* Find
Lat, Lng (center of search), type (event_type), distance (radius of search), userid current user
*/

$appId = getAppId();
$token = getToken();

function breezoSecure()
{
    global $token, $appId;
    if (!hasValue($token)) {
        sendUnauthorizedResponse("Invalid token");
    }
    if (!hasValue($appId)) {
        sendUnauthorizedResponse("Invalid tenant");
    }
}

$userid = getUserId($token);

include_once dirname(__FILE__) . "/breezoconfig.php";

function breezocreate($endpoint, $data)
{
    global $breezoconfigs;
    return GAPIcreate($breezoconfigs, $endpoint, $data);
}

function checksecurity($config, $data)
{
    global $appId;
    // $config["where"]["app_id"] = $appId;
    return [$config, $data];
}

function setUserId($config, $data)
{
    global $userid;
    $data["user_id"] = $userid;
    return [$config, $data];
}

function getCartForUser($userid)
{
    $cart = breezoselect("user", $userid, "cart");
    if (empty($cart)) {
        $cart = breezocreate("cart", ["user_id" => $userid]);
    } else {
        $cart = $cart;
    }
    return $cart[0];
}
function getCommissionRate($supplier_id, $item_type_id)
{
    $result = breezoselect("supplier", $supplier_id, "commission");
    if (empty($result)) {
        return 10;
    }
    foreach ($result as $row) {
        if ($row["item_type_id"] == $item_type_id) {
            return $row["commission_rate"];
        }
    }
    return 10;
}

function convertCartToOrder($data)
{
    $cartId = $data["cart_id"];
    // Fetch the cart
    $cart = breezoselect("cart", $cartId);
    if (empty($cart)) {
        throw new Exception("Cart not found.");
    }
    $cart = $cart[0];

    // Fetch cart items
    $cartItems = breezoselect("cart", $cartId, "items");
    if (empty($cartItems)) {
        throw new Exception("Cart has no items.");
    }

    // Calculate total price
    $totalPrice = 0;
    foreach ($cartItems as $item) {
        $totalPrice += $item['price'];
    }

    // Create the order
    $orderData = [
        "order_details" => "Cart",
        "user_id" => $cart['user_id'],
        "total_price" => $totalPrice,
        "status" => "pending",
    ];
    $order = breezocreate("order", $orderData);
    $order = $order[0];
    $orderId = $order["id"];

    // Create order items and delete cart items
    foreach ($cartItems as $item) {
        $orderItemData = [
            "order_id" => $orderId,
            "item_type_id" => $item['item_type_id'],
            "parent_id" => $item['parent_id'],
            "item_id" => $item['item_id'],
            "supplier_id" => $item['supplier_id'],
            "price" => $item['price'],
            "commission_rate" => $item['commission_rate'],
            "quantity" => $item['quantity'],
            "booking_id" => $item['booking_id'],
            "title" => $item['title'],
            "item_description" => $item['item_description'],
        ];
        breezocreate("order_item", $orderItemData);

        // Delete cart item
        breezodelete("cart_item", $item['id']);
    }

    return $order;
}

function beforeInsertCartItem($config, $data)
{
    global $userid;
    $cart = getCartForUser($userid);
    $data["cart_id"] = $cart["id"];
    $data["commission_rate"] = getCommissionRate($data["supplier_id"], $data["item_type_id"]);
    return [$config, $data];
}

function processOrderPayment($orderId)
{
    $order = breezoselect("order", $orderId);
    if (empty($order)) {
        throw new Exception("Order not found.");
    }
    $order = $order[0];

    $orderItems = breezoselect("order", $orderId, "items");
    if (empty($orderItems)) {
        throw new Exception("Order has no items.");
    }

    $user = breezoselect("user", $order["user_id"]);
    if (empty($user)) {
        throw new Exception("User not found.");
    }

    $totalPrice = 0;
    foreach ($orderItems as $item) {
        $totalPrice += $item['price'];
    }

    $supplierPayments = [];
    foreach ($orderItems as $item) {
        $supplierId = $item['supplier_id'];
        if (!isset($supplierPayments[$supplierId])) {
            $supplierPayments[$supplierId] = 0;
        }
        $supplierPayments[$supplierId] += $item['price'];
    }

    foreach ($supplierPayments as $supplierId => $amount) {
        $paymentData = [
            "order_id" => $orderId,
            "order_item_id" => "",
            "proof_of_payment" => "",
            "supplier_id" => $supplierId,
            "amount" => $amount,
            "status" => "pending",
        ];
        breezocreate("supplier_payment", $paymentData);
    }

    echo "Updating order status to paid";
    updateOrderStatus($orderId, 'paid');
    markBookingsByOrder($orderId, 'paid');

    return $supplierPayments;
}

function markBookingsByOrder($orderId, $newStatus)
{
    $updatedBookings = [];

    $sql = "SELECT booking_id FROM breezo_order_item WHERE item_type_id = 1 and order_id = ? AND booking_id IS NOT NULL";
    $params = [$orderId];

    $orderItems = PrepareExecSQL($sql, 'i', $params);

    if (!empty($orderItems)) {
        $updateSQL = "UPDATE kloko_booking SET status = ? WHERE id = ?";

        foreach ($orderItems as $item) {
            $bookingId = $item['booking_id'];
            PrepareExecSQL($updateSQL, 'si', [$newStatus, $bookingId]);
        }

        $updatedBookingIds = array_column($orderItems, 'booking_id');
        $placeholders = implode(',', array_fill(0, count($updatedBookingIds), '?'));
        $fetchUpdatedSQL = "SELECT * FROM kloko_booking WHERE id IN ($placeholders)";

        $updatedBookings = PrepareExecSQL($fetchUpdatedSQL, str_repeat('i', count($updatedBookingIds)), $updatedBookingIds);
    }

    $ticketSql = "SELECT boi.*, bo.user_id 
                  FROM breezo_order_item boi 
                  JOIN breezo_order bo ON bo.id = boi.order_id 
                  WHERE boi.item_type_id IN (3, 4) AND boi.order_id = ?";
    $ticketItems = PrepareExecSQL($ticketSql, 'i', [$orderId]);

    if (!empty($ticketItems)) {
        foreach ($ticketItems as $item) {
            $ticketTypeId = $item['item_type_id'] == 3 ? $item['item_id'] : 0;
            $ticketOptionId = $item['item_type_id'] == 4 ? $item['item_id'] : 0;

            $insertTicketSQL = "INSERT INTO kloko_tickets 
                               (user_id, event_id, ticket_type_id, ticket_option_id, title, description, quantity, currency, price, order_item_id) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, 'ZAR', ?, ?)";

            PrepareExecSQL($insertTicketSQL, 'iiiissidd', [
                $item['user_id'],
                $item['parent_id'],
                $ticketTypeId,
                $ticketOptionId,
                $item['title'],
                $item['item_description'],
                $item['quantity'],
                $item['price'],
                $item['id']
            ]);
        }
    }

    return $updatedBookings;
}

function breezoselect($endpoint, $id = null, $subkey = null, $where = [], $orderBy = '', $page = null, $limit = null)
{
    global $breezoconfigs;
    return GAPIselect($breezoconfigs, $endpoint, $id, $subkey, $where, $orderBy, $page, $limit);

}

function breezoupdate($endpoint, $id, $data)
{
    global $breezoconfigs;
    return GAPIupdate($breezoconfigs, $endpoint, $id, $data);

}

function breezodelete($endpoint, $id)
{
    global $breezoconfigs;
    return GAPIdelete($breezoconfigs, $endpoint, $id);
}

// New function to update order status
function updateOrderStatus($orderId, $status)
{
    if (empty($orderId) || empty($status)) {
        throw new Exception("Order ID and status must be provided.");
    }

    $order = breezoselect("order", $orderId);
    if (empty($order)) {
        throw new Exception("Order not found.");
    }

    $data = ["status" => $status];
    breezoupdate("order", $orderId, $data);
    return true;
}

function subscribeOrder($app_id, $option, $price)
{
    global $token;
    $userId = getUserId($token);
    $orderData = [
        "user_id" => $userId,
        "order_details" => $option,
        "total_price" => $price,
        "status" => "pending",
        "order_month" => date("Y-m-d")
    ];
    $order = breezocreate("order", $orderData);
    $order = $order[0];
    $orderId = $order["id"];

    $orderItemData = [
        "order_id" => $orderId,
        "item_type_id" => 1,
        "item_id" => 0,
        "item_description" => $option,
        "supplier_id" => 0,
        "price" => $price,
        "commission_rate" => 10,
        "quantity" => 1,
        "booking_id" => null,
    ];
    breezocreate("order_item", $orderItemData);
    $orderItems = breezoselect("order", $orderId, "items");
    $order["items"] = $orderItems;
    return $order;
}

function getUserTickets($data)
{
    $userId = $data["user"];
    $sql = "SELECT e.app_id,  e.id event_id, 
            t.id ticket_id, t.ticket_type_id, t.ticket_option_id, e.title event_title, e.description event_description, 
            t.description, t.quantity, t.currency, t.price, 
            e.keywords, e.duration, e.location, e.lat, e.lng, e.start_time, e.end_time
        FROM kloko_tickets t, kloko_event e
        WHERE t.event_id = e.id
        AND t.user_id = ?";
    return PrepareExecSQL($sql, 'i', [$userId]);
}