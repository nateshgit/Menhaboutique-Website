<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Order History API Endpoint - Menha Boutique PHP
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();

try {
    $db = getDBConnection();
    
    // Fetch all orders for this user
    $stmtOrders = $db->prepare("
        SELECT o.*, 
               a.first_name AS addr_first_name, a.last_name AS addr_last_name, 
               a.address_line1, a.address_line2, a.city, a.state, a.zip_code, a.country, a.phone_number AS addr_phone
        FROM orders o
        LEFT JOIN addresses a ON o.address_id = a.id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmtOrders->execute([$user['id']]);
    $orders = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);
    
    $response = [];
    
    foreach ($orders as $order) {
        // Fetch order items with product details
        $stmtItems = $db->prepare("
            SELECT oi.*, p.title, p.new_price, p.old_price, p.primary_image
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmtItems->execute([$order['id']]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
        
        $orderItemsMapped = [];
        foreach ($items as $item) {
            $orderItemsMapped[] = [
                'id' => $item['id'],
                'order_id' => $item['order_id'],
                'product_id' => $item['product_id'],
                'quantity' => (int)$item['quantity'],
                'unit_price' => (float)$item['unit_price'],
                'total_price' => (float)$item['total_price'],
                'attribute_id' => $item['attribute_id'],
                'product' => [
                    'id' => $item['product_id'],
                    'title' => $item['title'],
                    'price' => (float)$item['new_price'],
                    'sale_price' => $item['old_price'] !== null ? (float)$item['old_price'] : null,
                    'image_url' => $item['primary_image']
                ]
            ];
        }
        
        // Map address structure
        $addressMapped = null;
        if ($order['address_id']) {
            $addressMapped = [
                'id' => $order['address_id'],
                'first_name' => $order['addr_first_name'],
                'last_name' => $order['addr_last_name'],
                'address_line1' => $order['address_line1'],
                'address_line2' => $order['address_line2'],
                'city' => $order['city'],
                'state' => $order['state'],
                'zip_code' => $order['zip_code'],
                'country' => $order['country'],
                'phone_number' => $order['addr_phone']
            ];
        }
        
        $response[] = [
            'id' => $order['id'],
            'user_id' => $order['user_id'],
            'order_number' => $order['order_number'],
            'email' => $order['email'],
            'total_price' => (float)$order['total_price'],
            'status' => $order['status'],
            'payment_status' => $order['payment_status'],
            'payment_method' => $order['payment_method'],
            'delivery_charge' => (float)$order['delivery_charge'],
            'address_id' => $order['address_id'],
            'comments' => $order['comments'],
            'payment_link' => $order['payment_link'],
            'created_at' => $order['created_at'],
            'items' => $orderItemsMapped,
            'address' => $addressMapped
        ];
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
