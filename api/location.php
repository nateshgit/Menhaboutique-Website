<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Location API Endpoint - Menha Boutique PHP
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    $db = getDBConnection();
    
    if ($action === 'countries') {
        $stmt = $db->query("SELECT * FROM countries ORDER BY name ASC");
        $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($countries);
        exit;
    } 
    
    if ($action === 'states') {
        $countryId = isset($_GET['country_id']) ? $_GET['country_id'] : '';
        if (empty($countryId)) {
            echo json_encode([]);
            exit;
        }
        $stmt = $db->prepare("SELECT * FROM states WHERE country_id = ? ORDER BY name ASC");
        $stmt->execute([$countryId]);
        $states = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($states);
        exit;
    }
    
    if ($action === 'cities') {
        $stateId = isset($_GET['state_id']) ? $_GET['state_id'] : '';
        if (empty($stateId)) {
            echo json_encode([]);
            exit;
        }
        $stmt = $db->prepare("SELECT * FROM cities WHERE state_id = ? ORDER BY name ASC");
        $stmt->execute([$stateId]);
        $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($cities);
        exit;
    }
    
    // Default fallback
    echo json_encode(['error' => 'Invalid action']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
