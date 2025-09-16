<?php

// Define the configurations
$breezoconfigs = [
    "post" => [
        "test" => "testFunction",
        "placeorder" => "convertCartToOrder",
        "usertickets" => "getUserTickets"
    ],
    "user" => [
        'tablename' => 'user',
        'key' => 'id',
        'select' => "['id', 'app_id', 'firstname', 'lastname', 'email', 'created', 'modified']",
        'beforeselect' => '',
        'afterselect' => '',
        'subkeys' => [
            'cart' => [
                'tablename' => 'breezo_cart',
                'key' => 'user_id',
                'select' => "SELECT id, (SELECT COUNT(*) FROM breezo_cart_item WHERE breezo_cart_item.cart_id = breezo_cart.id) count,
                (SELECT sum(price) FROM breezo_cart_item WHERE breezo_cart_item.cart_id = breezo_cart.id) total
                FROM breezo_cart",
                'beforeselect' => '',
                'afterselect' => ''
            ],
            'orders' => [
                'tablename' => 'breezo_order',
                'key' => 'user_id',
                'select' => ['id', 'user_id', 'status', 'total_price', 'order_details', 'order_month', 'created', 'modified'],
                'beforeselect' => '',
                'afterselect' => ''
            ],
            'invoices' => [
                'tablename' => 'breezo_invoice',
                'key' => 'user_id',
                'select' => ['id', 'order_id', 'user_id', 'file_path', 'created'],
                'beforeselect' => '',
                'afterselect' => ''
            ],
        ]
    ],
    "supplier" => [
        'tablename' => 'supplier',
        'key' => 'id',
        'select' => ['id', 'name', 'created', 'modified'],
        'beforeselect' => '',
        'afterselect' => '',
        'subkeys' => [
            'payments' => [
                'tablename' => 'breezo_supplier_payment',
                'key' => 'supplier_id',
                'select' => ['id', 'order_item_id', 'supplier_id', 'amount', 'status', 'proof_of_payment', 'created'],
                'beforeselect' => '',
                'afterselect' => ''
            ],
            'commission' => [
                'tablename' => 'breezo_supplier_commission',
                'key' => 'supplier_id',
                'select' => ['id', 'supplier_id', 'item_type_id', 'commission_rate'],
                'beforeselect' => '',
                'afterselect' => ''
            ],
            'items' => [
                'tablename' => 'breezo_cart_item',
                'key' => 'supplier_id',
                'select' => ['id', 'cart_id', 'item_type_id', 'parent_id', 'item_id', 'title', 'item_description', 'supplier_id', 'currency', 'price', 'quantity', 'commission_rate', 'booking_id', 'created', 'modified'],
                'beforeselect' => '',
                'afterselect' => ''
            ]
        ]
    ],
    "cart" => [
        'tablename' => 'breezo_cart',
        'key' => 'id',
        'select' => ['id', 'user_id', 'created', 'modified'],
        'create' => ['user_id'],
        'update' => [],
        'delete' => true,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => 'setUserId',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => '',
        'subkeys' => [
            'items' => [
                'tablename' => 'breezo_cart_item',
                'key' => 'cart_id',
                'select' => "SELECT 
                breezo_cart_item.id, 
                breezo_cart_item.currency,
                breezo_cart_item.price,
                item_type_id,
                ifnull(breezo_cart_item.title, kloko_event.title) title,
                parent_id,
                item_id, item_description,
                booking_id,
                supplier_id,
                commission_rate,
                quantity,
                book.status,
                kloko_event.start_time
            FROM 
                breezo_cart_item
            LEFT JOIN 
                kloko_booking book ON breezo_cart_item.booking_id = book.id
            LEFT JOIN 
                kloko_event ON book.event_id = kloko_event.id",
                'beforeselect' => '',
                'afterselect' => ''
            ]
        ]
    ],
    "order" => [
        'tablename' => 'breezo_order',
        'key' => 'id',
        'select' => ['id', 'user_id', 'status', 'total_price', 'order_details', 'order_month', 'created', 'modified'],
        'create' => ['user_id', 'status', 'total_price', 'order_details', 'order_month'],
        'update' => ['status', 'total_price', 'order_details', 'order_month'],
        'delete' => true,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => '',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => '',
        'subkeys' => [
            'order_items' => [
                'tablename' => 'breezo_order_item',
                'key' => 'order_id',
                'select' => ['id', 'order_id', 'item_type_id', 'parent_id', 'item_id', 'title', 'item_description', 'supplier_id', 'currency', 'price', 'quantity', 'commission_rate', 'booking_id', 'created', 'modified'],
                'beforeselect' => '',
                'afterselect' => ''
            ],
            'items' => [
                'tablename' => 'breezo_order_item',
                'key' => 'order_id',
                'select' => ['id', 'order_id', 'item_type_id', 'parent_id', 'item_id', 'title', 'item_description', 'supplier_id', 'price', 'quantity', 'commission_rate', 'booking_id', 'created', 'modified'],
                'beforeselect' => '',
                'afterselect' => ''
            ]
        ]
    ],
    "invoice" => [
        'tablename' => 'breezo_invoice',
        'key' => 'id',
        'select' => ['id', 'order_id', 'user_id', 'file_path', 'created'],
        'create' => ['order_id', 'user_id', 'file_path'],
        'update' => ['file_path'],
        'delete' => true,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => '',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => ''
    ],
    "supplier_payment" => [
        'tablename' => 'breezo_supplier_payment',
        'key' => 'id',
        'select' => ['id', 'order_item_id', 'supplier_id', 'amount', 'status', 'proof_of_payment', 'created'],
        'create' => ['order_item_id', 'supplier_id', 'amount', 'status', 'proof_of_payment'],
        'update' => ['amount', 'status', 'proof_of_payment'],
        'delete' => true,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => '',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => ''
    ],
    "supplier_commission" => [
        'tablename' => 'breezo_supplier_commission',
        'key' => 'id',
        'select' => ['id', 'supplier_id', 'item_type_id', 'commission_rate'],
        'create' => ['supplier_id', 'item_type_id', 'commission_rate'],
        'update' => ['commission_rate'],
        'delete' => true,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => '',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => ''
    ],
    "item_type" => [
        'tablename' => 'breezo_item_type',
        'key' => 'id',
        'select' => ['id', 'name'],
        'create' => ['name'],
        'update' => ['name'],
        'delete' => true,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => '',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => '',
    ],
    "cart_item" => [
        'tablename' => 'breezo_cart_item',
        'key' => 'id',
        'select' => ['id', 'cart_id', 'item_type_id', 'parent_id', 'item_id', 'title', 'item_description', 'supplier_id', 'currency', 'price', 'quantity', 'commission_rate', 'booking_id', 'created', 'modified'],
        'create' => "insertCartItem", //['cart_id', 'item_type_id', 'parent_id', 'item_id', 'title', 'item_description', 'supplier_id', 'currency', 'price', 'quantity', 'commission_rate', 'booking_id'],
        'update' => ['price', 'title', 'item_description', 'currency', 'quantity', 'commission_rate', 'booking_id'],
        'delete' => true,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => 'beforeInsertCartItem',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => 'beforeDeleteCartItem'
    ],
    "order_item" => [
        'tablename' => 'breezo_order_item',
        'key' => 'id',
        'select' => ['id', 'order_id', 'item_type_id', 'parent_id', 'item_id', 'title', 'item_description', 'supplier_id', 'currency', 'price', 'quantity', 'commission_rate', 'booking_id', 'created', 'modified'],
        'create' => ['order_id', 'item_type_id', 'parent_id', 'item_id', 'title', 'item_description', 'supplier_id', 'currency', 'price', 'quantity', 'commission_rate', 'booking_id'],
        'update' => ['title', 'item_description', 'currency', 'price', 'quantity', 'commission_rate', 'booking_id'],
        'delete' => true,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => '',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => ''
    ],
    "revenue" => [
        'tablename' => 'breezo_revenue',
        'key' => 'id',
        'select' => ['id', 'order_id', 'amount', 'created'],
        'create' => ['order_id', 'amount'],
        'update' => ['amount'],
        'delete' => true,
        'beforeselect' => '',
        'afterselect' => '',
        'beforecreate' => '',
        'aftercreate' => '',
        'beforeupdate' => '',
        'afterupdate' => '',
        'beforedelete' => ''
    ]
];

function beforeDeleteCartItem($config, $id)
{
    $item = breezoselect("cart_item", $id);
    // var_dump("Item", $item[0]);
    klokoupdate("booking", $item[0]["booking_id"], ["status" => "pending"]);
    // exit;
    return [$config, $id];
}



function testFunction($data)
{
    return ["function" => "testFunction", "data" => $data];
}

$GLOBAL["breezoconfigs"] = $breezoconfigs;