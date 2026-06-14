<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Order Processing Endpoint - Menha Boutique PHP
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order payload']);
    exit;
}

// Helper to generate UUIDs
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

try {
    $db = getDBConnection();
    
    // Start transaction to keep sequence generation and stock decrements atomic
    $db->beginTransaction();
    
    // 1. Get next order number from order_prefix
    $stmtPrefix = $db->query("SELECT * FROM order_prefix FOR UPDATE");
    $prefixRow = $stmtPrefix->fetch(PDO::FETCH_ASSOC);
    
    $prefix = $prefixRow ? $prefixRow['prefix'] : 'ORD';
    $sequence = $prefixRow ? (int)$prefixRow['next_sequence'] : 1000;
    $orderNumber = $prefix . '-' . $sequence;
    
    // Increment prefix sequence
    $stmtInc = $db->prepare("UPDATE order_prefix SET next_sequence = next_sequence + 1 WHERE id = ?");
    $stmtInc->execute([$prefixRow['id']]);
    
    // 2. Map payload fields
    $orderId = generateUUID();
    $userId = isset($input['user_id']) ? $input['user_id'] : (isLoggedIn() ? $_SESSION['user']['id'] : null);
    $email = isset($input['email']) ? trim($input['email']) : '';
    $totalPrice = isset($input['total_price']) ? (float)$input['total_price'] : 0.0;
    $paymentStatus = isset($input['payment_status']) ? trim($input['payment_status']) : 'unpaid';
    $paymentMethod = isset($input['payment_method']) ? trim($input['payment_method']) : 'cod';
    $deliveryCharge = isset($input['delivery_charge']) ? (float)$input['delivery_charge'] : 0.0;
    $addressId = isset($input['address_id']) ? trim($input['address_id']) : null;
    $comments = isset($input['comments']) ? trim($input['comments']) : '';
    $transactionId = isset($input['gateway_transaction_id']) ? trim($input['gateway_transaction_id']) : null;
    $courierId = isset($input['courier_id']) ? trim($input['courier_id']) : null;
    
    // Fetch courier name if courier ID is provided
    $courierName = null;
    if ($courierId) {
        $stmtCourier = $db->prepare("SELECT name FROM couriers WHERE id = ?");
        $stmtCourier->execute([$courierId]);
        $courierName = $stmtCourier->fetchColumn() ?: null;
    }
    
    // 3. Create Address if a new Address is submitted
    if (empty($addressId) && isset($input['newAddress']) && !empty($input['newAddress'])) {
        $newAddr = $input['newAddress'];
        $addressId = generateUUID();
        $addrUserId = $userId ?: 'GUEST';
        
        $stmtNewAddr = $db->prepare("
            INSERT INTO addresses (id, user_id, first_name, last_name, address_line1, address_line2, city, state, zip_code, country, phone_number, is_default)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
        ");
        $stmtNewAddr->execute([
            $addressId,
            $addrUserId,
            $newAddr['first_name'] ?? '',
            $newAddr['last_name'] ?? '',
            $newAddr['address_line1'] ?? '',
            $newAddr['address_line2'] ?? null,
            $newAddr['city'] ?? '',
            $newAddr['state'] ?? '',
            $newAddr['zip_code'] ?? '',
            $newAddr['country'] ?? 'India',
            $newAddr['phone_number'] ?? ''
        ]);
    }
    
    // 4. Insert Order
    $stmtOrder = $db->prepare("
        INSERT INTO orders (id, user_id, order_number, email, total_price, status, payment_status, payment_method, delivery_charge, address_id, comments, payment_link, courier_id, courier_name)
        VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtOrder->execute([
        $orderId,
        $userId,
        $orderNumber,
        $email,
        $totalPrice,
        $paymentStatus,
        $paymentMethod,
        $deliveryCharge,
        $addressId,
        $comments,
        $transactionId,
        $courierId,
        $courierName
    ]);
    
    // 5. Insert Items & Decrement Stock
    if (isset($input['items']) && is_array($input['items'])) {
        $stmtItem = $db->prepare("
            INSERT INTO order_items (id, order_id, product_id, quantity, unit_price, total_price, attribute_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmtVariantStock = $db->prepare("UPDATE product_attributes SET stock_quantity = GREATEST(0, stock_quantity - ?) WHERE id = ?");
        $stmtProductStock = $db->prepare("UPDATE products SET stock_quantity = GREATEST(0, stock_quantity - ?), status = IF(stock_quantity - ? <= 0, 'Out of Stock', 'In Stock') WHERE id = ? AND status != 'Coming Soon'");
        
        foreach ($input['items'] as $item) {
            $itemId = generateUUID();
            $productId = $item['product_id'] ?? $item['productId'] ?? null;
            $qty = (int)($item['quantity'] ?? 1);
            $price = (float)($item['price'] ?? $item['unit_price'] ?? 0.0);
            $variantId = $item['variant_id'] ?? $item['variantId'] ?? null;
            
            if (!$productId) continue;
            
            $stmtItem->execute([
                $itemId,
                $orderId,
                $productId,
                $qty,
                $price,
                $qty * $price,
                $variantId ?: null
            ]);
            
            // Decrement variant stock
            if ($variantId) {
                $stmtVariantStock->execute([$qty, $variantId]);
            }
            
            // Decrement master product stock
            $stmtProductStock->execute([$qty, $qty, $productId]);
        }
    }
    
    $db->commit();
    
    // Fetch newly created order to send back
    $stmtCheck = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmtCheck->execute([$orderId]);
    $createdOrder = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'order' => $createdOrder
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Order placement failed: ' . $e->getMessage()]);
}
