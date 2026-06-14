<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Couriers API Endpoint - Menha Boutique PHP
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT * FROM couriers WHERE is_active = 1 ORDER BY name ASC");
    $couriers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($couriers);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
