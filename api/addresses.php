<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * User Addresses API Endpoint - Menha Boutique PHP
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
$method = $_SERVER['REQUEST_METHOD'];

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
    
    if ($method === 'GET') {
        // List user addresses
        $stmt = $db->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
        $stmt->execute([$user['id']]);
        $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($addresses);
        exit;
    }
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }
        
        $action = isset($input['action']) ? $input['action'] : 'upsert';
        
        if ($action === 'delete') {
            $addressId = isset($input['id']) ? $input['id'] : '';
            if (empty($addressId)) {
                http_response_code(400);
                echo json_encode(['error' => 'Address ID required']);
                exit;
            }
            
            $stmt = $db->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
            $stmt->execute([$addressId, $user['id']]);
            echo json_encode(['success' => true]);
            exit;
        }
        
        // Upsert address
        $addressId = isset($input['id']) ? trim($input['id']) : '';
        $firstName = isset($input['first_name']) ? trim($input['first_name']) : $user['first_name'];
        $lastName = isset($input['last_name']) ? trim($input['last_name']) : $user['last_name'];
        $addressLine1 = isset($input['address_line1']) ? trim($input['address_line1']) : '';
        $addressLine2 = isset($input['address_line2']) ? trim($input['address_line2']) : null;
        $city = isset($input['city']) ? trim($input['city']) : '';
        $state = isset($input['state']) ? trim($input['state']) : '';
        $zipCode = isset($input['zip_code']) ? trim($input['zip_code']) : '';
        $country = isset($input['country']) ? trim($input['country']) : 'India';
        $phoneNumber = isset($input['phone_number']) ? trim($input['phone_number']) : $user['phone_number'];
        $isDefault = isset($input['is_default']) ? ($input['is_default'] ? 1 : 0) : 0;
        
        if (empty($addressLine1) || empty($city) || empty($state) || empty($zipCode)) {
            http_response_code(400);
            echo json_encode(['error' => 'Required address fields missing']);
            exit;
        }
        
        if ($isDefault) {
            // Unset other defaults for user
            $stmtClear = $db->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
            $stmtClear->execute([$user['id']]);
        }
        
        if (!empty($addressId)) {
            // Update
            $stmtUpdate = $db->prepare("
                UPDATE addresses 
                SET first_name = ?, last_name = ?, address_line1 = ?, address_line2 = ?, city = ?, state = ?, zip_code = ?, country = ?, phone_number = ?, is_default = ?
                WHERE id = ? AND user_id = ?
            ");
            $stmtUpdate->execute([$firstName, $lastName, $addressLine1, $addressLine2, $city, $state, $zipCode, $country, $phoneNumber, $isDefault, $addressId, $user['id']]);
            
            // Retrieve updated address
            $stmtGet = $db->prepare("SELECT * FROM addresses WHERE id = ?");
            $stmtGet->execute([$addressId]);
            $address = $stmtGet->fetch(PDO::FETCH_ASSOC);
        } else {
            // Insert new
            $addressId = generateUUID();
            $stmtInsert = $db->prepare("
                INSERT INTO addresses (id, user_id, first_name, last_name, address_line1, address_line2, city, state, zip_code, country, phone_number, is_default)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtInsert->execute([$addressId, $user['id'], $firstName, $lastName, $addressLine1, $addressLine2, $city, $state, $zipCode, $country, $phoneNumber, $isDefault]);
            
            // Retrieve new address
            $stmtGet = $db->prepare("SELECT * FROM addresses WHERE id = ?");
            $stmtGet->execute([$addressId]);
            $address = $stmtGet->fetch(PDO::FETCH_ASSOC);
        }
        
        echo json_encode($address);
        exit;
    }
    
    if ($method === 'DELETE') {
        // Extract address ID from URL or JSON payload
        $input = json_decode(file_get_contents('php://input'), true);
        $addressId = isset($input['id']) ? $input['id'] : '';
        if (empty($addressId) && isset($_GET['id'])) {
            $addressId = $_GET['id'];
        }
        
        if (empty($addressId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Address ID required']);
            exit;
        }
        
        $stmt = $db->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
        $stmt->execute([$addressId, $user['id']]);
        echo json_encode(['success' => true]);
        exit;
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
