<?php
require_once __DIR__ . '/../corsheaders.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../auth/authfunctions.php';
require_once __DIR__ . '/config.php';

$appId = getAppId();


/**
 * Creates a new order in the database
 * @return array The created order data
 */
function createOrder() {
    $token = getToken();
    $userId = getUserId($token);
    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    // Get JSON data from request body
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);

    // Validate required fields
    if (!isset($data['items']) || empty($data['items'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    // Extract order data
    // User ID is already obtained from the token
    $orderDetails = $data['order_details'] ?? '';
    $orderMonth = date('Y-m-d'); // Default to current date
    $status = $data['status'] ?? 'pending';
    $currency = $data['currency'] ?? 'ZAR';
    
    // Calculate total price from items
    $totalPrice = 0;
    foreach ($data['items'] as $item) {
        $itemPrice = $item['price'] ?? 0;
        $itemQuantity = $item['quantity'] ?? 1;
        $totalPrice += ($itemPrice * $itemQuantity);
    }

    try {
        // Begin transaction (manually handling since executeQuery doesn't support transactions)
        global $pwhost, $pwuser, $pwpassword, $pwdatabase;

        // Create connection
        $conn = new mysqli($pwhost, $pwuser, $pwpassword, $pwdatabase);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->begin_transaction();

        // Insert order
        $insertOrderSql = "INSERT INTO breezo_order (user_id, order_details, order_month, status, total_price, currency) 
                          VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insertOrderSql);
        $stmt->bind_param('isssds', $userId, $orderDetails, $orderMonth, $status, $totalPrice, $currency);
        $stmt->execute();
        
        // Get the new order ID
        $orderId = $conn->insert_id;
        $stmt->close();

        
        // Insert order items
        foreach ($data['items'] as $item) {
            $itemTypeId = $item['item_type_id'];
            $parentId = $item['parent_id'] ?? null;
            $itemId = $item['item_id'];
            $title = $item['title'] ?? '';
            $itemDescription = $item['item_description'] ?? '';
            $supplierId = isset($item['supplier_id']) ? $item['supplier_id'] : 0;
            $price = $item['price'];
            $quantity = $item['quantity'] ?? 1;
            $additional = $item['additional'] ?? '';
            
            $insertItemSql = "INSERT INTO breezo_order_item (order_id, item_type_id, parent_id, item_id, title, 
                             item_description, supplier_id, price, quantity, additional) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($insertItemSql);
            $stmt->bind_param('iiiissidis', $orderId, $itemTypeId, $parentId, $itemId, $title, 
                             $itemDescription, $supplierId, $price, $quantity, $additional);
            $stmt->execute();
            $stmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        $conn->close();
        
        // Return success response
        $response = [
            'success' => true,
            'order_id' => $orderId,
            'total_price' => $totalPrice,
            'currency' => $currency,
            'status' => $status
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($conn) && $conn->ping()) {
            $conn->rollback();
            $conn->close();
        }
        
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create order: ' . $e->getMessage()]);
        exit;
    }
}

?>
