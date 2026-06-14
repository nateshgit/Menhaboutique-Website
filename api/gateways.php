<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Payment Gateways API Endpoint - Menha Boutique PHP
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT id, name, type, credentials, is_active, is_test_mode FROM payment_gateways WHERE is_active = 1");
    $gateways = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($gateways as &$gw) {
        if (!empty($gw['credentials'])) {
            $gw['credentials'] = json_decode($gw['credentials'], true);
        } else {
            $gw['credentials'] = [];
        }
    }
    unset($gw);
    
    echo json_encode($gateways);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
