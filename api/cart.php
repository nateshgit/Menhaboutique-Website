<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Cart AJAX API - Menha Boutique PHP
 * Substitutes SupabaseCartSync operations with local MySQL calls.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$db = getDBConnection();

// Read input payload for POST requests
$input = json_decode(file_get_contents('php://input'), true);

function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function touchCart($db, $cartId) {
    $stmt = $db->prepare("UPDATE carts SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$cartId]);
}

// ── Session-aware cart helpers ────────────────────────────────
function sessionGetOrCreateCart($db, $userId) {
    $stmt = $db->prepare("SELECT id FROM carts WHERE user_id = ? AND status = 'active' ORDER BY created_at ASC LIMIT 1");
    $stmt->execute([$userId]);
    $cartId = $stmt->fetchColumn();
    if (!$cartId) {
        $cartId = generateUUID();
        $db->prepare("INSERT INTO carts (id, user_id, status) VALUES (?, ?, 'active')")->execute([$cartId, $userId]);
    }
    return $cartId;
}

function sessionGetCartItems($db, $cartId) {
    $stmt = $db->prepare("
        SELECT ci.*, p.title, p.new_price AS product_price, p.primary_image
        FROM cart_items ci JOIN products p ON ci.product_id = p.id
        WHERE ci.cart_id = ?
    ");
    $stmt->execute([$cartId]);
    $items = [];
    foreach ($stmt->fetchAll() as $row) {
        $snap = json_decode($row['product_snapshot'], true);
        if (!$snap) $snap = ['id' => $row['product_id'], 'title' => $row['title'], 'new_price' => (float)$row['product_price'], 'primary_image' => $row['primary_image']];
        $items[] = ['product' => $snap, 'quantity' => (int)$row['quantity']];
    }
    return $items;
}
// ─────────────────────────────────────────────────────────────

try {
    switch ($action) {

        // ── Session-based actions (no auth token needed) ──────
        case 'my_cart':
            if (isLoggedIn()) {
                $user   = getCurrentUser();
                $cartId = sessionGetOrCreateCart($db, $user['id']);
                $items  = sessionGetCartItems($db, $cartId);
                echo json_encode(['items' => $items, 'source' => 'db']);
            } else {
                if (!isset($_SESSION['guest_cart'])) $_SESSION['guest_cart'] = [];
                echo json_encode(['items' => $_SESSION['guest_cart'], 'source' => 'session']);
            }
            break;

        case 'add':
            $product  = $input['product'] ?? null;
            $quantity = max(1, (int)($input['quantity'] ?? 1));
            if (!$product || empty($product['id'])) { echo json_encode(['error' => 'Missing product']); exit; }
            if (isLoggedIn()) {
                $user      = getCurrentUser();
                $cartId    = sessionGetOrCreateCart($db, $user['id']);
                $variantId = $product['variant_id'] ?? null;
                $chk = $db->prepare("SELECT id FROM cart_items WHERE cart_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))");
                $chk->execute([$cartId, $product['id'], $variantId, $variantId]);
                $existId = $chk->fetchColumn();
                if ($existId) {
                    $db->prepare("UPDATE cart_items SET quantity = quantity + ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$quantity, $existId]);
                } else {
                    $price = (float)($product['price'] ?? $product['new_price'] ?? 0);
                    $db->prepare("INSERT INTO cart_items (id, cart_id, product_id, variant_id, quantity, unit_price, product_snapshot) VALUES (?, ?, ?, ?, ?, ?, ?)")
                       ->execute([generateUUID(), $cartId, $product['id'], $variantId, $quantity, $price, json_encode($product)]);
                }
                touchCart($db, $cartId);
                echo json_encode(['success' => true, 'source' => 'db']);
            } else {
                if (!isset($_SESSION['guest_cart'])) $_SESSION['guest_cart'] = [];
                $variantId = $product['variant_id'] ?? null;
                $found = false;
                foreach ($_SESSION['guest_cart'] as &$item) {
                    if ($item['product']['id'] === $product['id'] && ($item['product']['variant_id'] ?? null) === $variantId) {
                        $item['quantity'] += $quantity; $found = true; break;
                    }
                }
                unset($item);
                if (!$found) $_SESSION['guest_cart'][] = ['product' => $product, 'quantity' => $quantity];
                echo json_encode(['success' => true, 'source' => 'session']);
            }
            break;

        case 'update':
            $productId = $input['productId'] ?? null;
            $quantity  = (int)($input['quantity'] ?? 0);
            $variantId = $input['variantId'] ?? null;
            if (!$productId) { echo json_encode(['error' => 'Missing productId']); exit; }
            if (isLoggedIn()) {
                $user   = getCurrentUser();
                $cartId = sessionGetOrCreateCart($db, $user['id']);
                if ($quantity <= 0) {
                    $db->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))")->execute([$cartId, $productId, $variantId, $variantId]);
                } else {
                    $db->prepare("UPDATE cart_items SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE cart_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))")->execute([$quantity, $cartId, $productId, $variantId, $variantId]);
                }
                touchCart($db, $cartId);
                echo json_encode(['success' => true]);
            } else {
                if (!isset($_SESSION['guest_cart'])) $_SESSION['guest_cart'] = [];
                if ($quantity <= 0) {
                    $_SESSION['guest_cart'] = array_values(array_filter($_SESSION['guest_cart'], fn($i) => !($i['product']['id'] === $productId && ($i['product']['variant_id'] ?? null) === $variantId)));
                } else {
                    foreach ($_SESSION['guest_cart'] as &$item) {
                        if ($item['product']['id'] === $productId && ($item['product']['variant_id'] ?? null) === $variantId) { $item['quantity'] = $quantity; break; }
                    }
                    unset($item);
                }
                echo json_encode(['success' => true]);
            }
            break;

        case 'remove_item':
            $productId = $input['productId'] ?? null;
            $variantId = $input['variantId'] ?? null;
            if (!$productId) { echo json_encode(['error' => 'Missing productId']); exit; }
            if (isLoggedIn()) {
                $user   = getCurrentUser();
                $cartId = sessionGetOrCreateCart($db, $user['id']);
                $db->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))")->execute([$cartId, $productId, $variantId, $variantId]);
                touchCart($db, $cartId);
                echo json_encode(['success' => true]);
            } else {
                if (isset($_SESSION['guest_cart'])) {
                    $_SESSION['guest_cart'] = array_values(array_filter($_SESSION['guest_cart'], fn($i) => !($i['product']['id'] === $productId && ($i['product']['variant_id'] ?? null) === $variantId)));
                }
                echo json_encode(['success' => true]);
            }
            break;

        case 'clear_cart':
            if (isLoggedIn()) {
                $user   = getCurrentUser();
                $cartId = sessionGetOrCreateCart($db, $user['id']);
                $db->prepare("DELETE FROM cart_items WHERE cart_id = ?")->execute([$cartId]);
                touchCart($db, $cartId);
                echo json_encode(['success' => true]);
            } else {
                $_SESSION['guest_cart'] = [];
                echo json_encode(['success' => true]);
            }
            break;
        // ─────────────────────────────────────────────────────

        case 'get_or_create':
            $userId = isset($_GET['userId']) ? $_GET['userId'] : null;
            if (!$userId) {
                echo json_encode(['error' => 'User ID is required']);
                exit;
            }
            
            // Fetch active cart
            $stmt = $db->prepare("SELECT * FROM carts WHERE user_id = ? AND status = 'active' ORDER BY created_at ASC");
            $stmt->execute([$userId]);
            $carts = $stmt->fetchAll();
            
            if (count($carts) > 0) {
                // If multiple carts exist, we could merge, but for now take the oldest active one
                $masterCart = $carts[0];
                echo json_encode($masterCart);
                exit;
            }
            
            // Create a new cart
            $cartId = generateUUID();
            $stmtInsert = $db->prepare("INSERT INTO carts (id, user_id, status) VALUES (?, ?, 'active')");
            $stmtInsert->execute([$cartId, $userId]);
            
            $stmtSelect = $db->prepare("SELECT * FROM carts WHERE id = ?");
            $stmtSelect->execute([$cartId]);
            echo json_encode($stmtSelect->fetch());
            break;
            
        case 'get_items':
            $cartId = isset($_GET['cartId']) ? $_GET['cartId'] : null;
            if (!$cartId) {
                echo json_encode(['error' => 'Cart ID is required']);
                exit;
            }
            
            // Fetch cart items joined with products info
            $stmt = $db->prepare("
                SELECT ci.*, p.title, p.sku, p.new_price as product_price, p.primary_image
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                WHERE ci.cart_id = ?
            ");
            $stmt->execute([$cartId]);
            $items = $stmt->fetchAll();
            
            // Reformat items to match Supabase response format (product:products(*))
            $formattedItems = [];
            foreach ($items as $item) {
                $snapshot = json_decode($item['product_snapshot'], true);
                if (!$snapshot) {
                    $snapshot = [
                        'id' => $item['product_id'],
                        'title' => $item['title'],
                        'sku' => $item['sku'],
                        'new_price' => $item['product_price'],
                        'primary_image' => $item['primary_image']
                    ];
                }
                
                $formattedItems[] = [
                    'id' => $item['id'],
                    'cart_id' => $item['cart_id'],
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'quantity' => (int)$item['quantity'],
                    'unit_price' => (float)$item['unit_price'],
                    'product' => $snapshot,
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at']
                ];
            }
            echo json_encode($formattedItems);
            break;
            
        case 'upsert':
            $cartId = isset($input['cartId']) ? $input['cartId'] : null;
            $product = isset($input['product']) ? $input['product'] : null;
            $quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;
            
            if (!$cartId || !$product) {
                echo json_encode(['error' => 'Missing parameters']);
                exit;
            }
            
            $productId = $product['id'];
            $variantId = isset($product['variant_id']) ? $product['variant_id'] : (isset($input['variantId']) ? $input['variantId'] : null);
            
            // Fetch existing items for this cart+product+variant
            $stmtCheck = $db->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))");
            $stmtCheck->execute([$cartId, $productId, $variantId, $variantId]);
            $match = $stmtCheck->fetch();
            
            if ($match) {
                $stmtUpdate = $db->prepare("UPDATE cart_items SET quantity = quantity + ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmtUpdate->execute([$quantity, $match['id']]);
            } else {
                $itemId = generateUUID();
                $unitPrice = isset($product['price']) ? (float)$product['price'] : (isset($product['new_price']) ? (float)$product['new_price'] : 0.0);
                
                $stmtInsert = $db->prepare("
                    INSERT INTO cart_items (id, cart_id, product_id, variant_id, quantity, unit_price, product_snapshot) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtInsert->execute([
                    $itemId,
                    $cartId,
                    $productId,
                    $variantId,
                    $quantity,
                    $unitPrice,
                    json_encode($product)
                ]);
            }
            
            touchCart($db, $cartId);
            echo json_encode(['success' => true]);
            break;
            
        case 'set_quantity':
            $cartId = isset($input['cartId']) ? $input['cartId'] : null;
            $productId = isset($input['productId']) ? $input['productId'] : null;
            $variantId = isset($input['variantId']) ? $input['variantId'] : null;
            $quantity = isset($input['quantity']) ? (int)$input['quantity'] : 0;
            
            if (!$cartId || !$productId) {
                echo json_encode(['error' => 'Missing parameters']);
                exit;
            }
            
            if ($quantity <= 0) {
                $stmtDelete = $db->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))");
                $stmtDelete->execute([$cartId, $productId, $variantId, $variantId]);
            } else {
                $stmtUpdate = $db->prepare("UPDATE cart_items SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE cart_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))");
                $stmtUpdate->execute([$quantity, $cartId, $productId, $variantId, $variantId]);
            }
            
            touchCart($db, $cartId);
            echo json_encode(['success' => true]);
            break;
            
        case 'remove':
            $cartId = isset($input['cartId']) ? $input['cartId'] : null;
            $productId = isset($input['productId']) ? $input['productId'] : null;
            $variantId = isset($input['variantId']) ? $input['variantId'] : null;
            
            if (!$cartId || !$productId) {
                echo json_encode(['error' => 'Missing parameters']);
                exit;
            }
            
            $stmtDelete = $db->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))");
            $stmtDelete->execute([$cartId, $productId, $variantId, $variantId]);
            
            touchCart($db, $cartId);
            echo json_encode(['success' => true]);
            break;
            
        case 'clear':
            $cartId = isset($input['cartId']) ? $input['cartId'] : null;
            if (!$cartId) {
                echo json_encode(['error' => 'Cart ID is required']);
                exit;
            }
            
            $stmtClear = $db->prepare("DELETE FROM cart_items WHERE cart_id = ?");
            $stmtClear->execute([$cartId]);
            
            touchCart($db, $cartId);
            echo json_encode(['success' => true]);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
