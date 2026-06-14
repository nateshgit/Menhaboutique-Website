<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Delivery Charge Calculation API - Menha Boutique PHP
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['state']) || !isset($input['items'])) {
    echo json_encode(['delivery_charge' => 0]);
    exit;
}

$stateCode = trim($input['state']);
$items = $input['items'];

if (empty($stateCode) || empty($items)) {
    echo json_encode(['delivery_charge' => 0]);
    exit;
}

try {
    $db = getDBConnection();
    
    // 1. Determine Zone for the State
    $stmtState = $db->prepare("SELECT zone FROM states WHERE code = ? OR name = ? LIMIT 1");
    $stmtState->execute([$stateCode, $stateCode]);
    $zone = $stmtState->fetchColumn() ?: 'REST';
    
    // 2. Fetch Calculation Mode
    $mode = $db->query("SELECT calculation_mode FROM delivery_config LIMIT 1")->fetchColumn() ?: 'WEIGHT';
    
    // 3. Compute total weight or total rate (using DB prices and weights to prevent client spoofing)
    $thresholdValue = 0.0;
    
    if ($mode === 'RATE') {
        foreach ($items as $item) {
            $productId = $item['product_id'] ?? $item['productId'] ?? '';
            $qty = (int)($item['quantity'] ?? 1);
            if (empty($productId)) continue;

            $stmtProd = $db->prepare("SELECT new_price FROM products WHERE id = ?");
            $stmtProd->execute([$productId]);
            $prod = $stmtProd->fetch(PDO::FETCH_ASSOC);
            if ($prod) {
                $thresholdValue += (float)($prod['new_price'] ?? 0) * $qty;
            }
        }
    } else {
        // WEIGHT mode
        foreach ($items as $item) {
            $productId = $item['product_id'] ?? $item['productId'] ?? '';
            $qty = (int)($item['quantity'] ?? 1);
            if (empty($productId)) continue;
            
            $stmtProd = $db->prepare("SELECT weight FROM products WHERE id = ?");
            $stmtProd->execute([$productId]);
            $weightStr = $stmtProd->fetchColumn() ?: '0g';
            // Extract numerical weight in grams
            $weightVal = (int)preg_replace('/[^0-9]/', '', $weightStr) ?: 0;
            $thresholdValue += $weightVal * $qty;
        }
    }
    
    // 4. Fetch all tariffs
    $stmtTariffs = $db->query("SELECT max_weight, tariff_type, prices FROM delivery_tariffs ORDER BY max_weight ASC");
    $allTariffs = $stmtTariffs->fetchAll(PDO::FETCH_ASSOC);
    
    // Filter by type
    $modeTariffs = array_filter($allTariffs, function($t) use ($mode) {
        $type = $t['tariff_type'] ?: 'WEIGHT';
        return $type === $mode;
    });
    
    // Fallback if no matching tariff type found
    if (empty($modeTariffs)) {
        $modeTariffs = $allTariffs;
    }
    
    if (empty($modeTariffs)) {
        echo json_encode(['delivery_charge' => 0]);
        exit;
    }
    
    // Find the correct tariff tier
    $selectedTariff = null;
    foreach ($modeTariffs as $tariff) {
        if ((float)$tariff['max_weight'] >= $thresholdValue) {
            $selectedTariff = $tariff;
            break;
        }
    }

    // If threshold exceeds the largest tier:
    // RATE mode → free delivery (order value qualifies for free shipping)
    // WEIGHT mode → use the last (heaviest) tier
    if (!$selectedTariff) {
        if ($mode === 'RATE') {
            echo json_encode(['delivery_charge' => 0]);
            exit;
        }
        $selectedTariff = end($modeTariffs);
    }
    
    $prices = json_decode($selectedTariff['prices'], true) ?: [];
    $charge = isset($prices[$zone]) ? (float)$prices[$zone] : (isset($prices['REST']) ? (float)$prices['REST'] : 0.0);
    
    echo json_encode(['delivery_charge' => $charge]);
    
} catch (Exception $e) {
    echo json_encode(['delivery_charge' => 0, 'error' => $e->getMessage()]);
}
