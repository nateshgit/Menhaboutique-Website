<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Login API - Menha Boutique PHP
 * Verifies credentials server-side with bcrypt; returns user + session token.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$identifier = isset($input['identifier']) ? trim($input['identifier']) : '';
$password   = isset($input['password'])   ? $input['password']          : '';

if (empty($identifier) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email/phone and password are required']);
    exit;
}

try {
    $pdo  = getDBConnection();
    
    // Clean identifier to search phone numbers robustly (remove all non-digits)
    $cleanIdentifier = preg_replace('/[^0-9]/', '', $identifier);
    $likeClean = '%' . $cleanIdentifier;
    
    // Query users comparing email, exact cleaned phone, or suffix matched phone (for 10-digit login inputs)
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE email = ? 
           OR REPLACE(REPLACE(REPLACE(phone_number, '+', ''), ' ', ''), '-', '') = ?
           OR (LENGTH(?) >= 10 AND REPLACE(REPLACE(REPLACE(phone_number, '+', ''), ' ', ''), '-', '') LIKE ?)
        LIMIT 1
    ");
    $stmt->execute([$identifier, $cleanIdentifier, $cleanIdentifier, $likeClean]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }

    $valid = password_verify($password, $user['password_hash'])
          || $user['password_hash'] === $password; // legacy plain-text fallback

    if (!$valid) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }

    // Migrate plain-text password to bcrypt on first successful login
    if ($user['password_hash'] === $password) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $user['id']]);
        $user['password_hash'] = $newHash;
    }

    // Establish PHP session; clear any lingering guest cart so logged-in
    // cart (DB-backed) and guest cart remain fully separate.
    $_SESSION['user'] = $user;
    unset($_SESSION['guest_cart']);

    // Return safe user object (no password hash)
    unset($user['password_hash'], $user['reset_otp'], $user['otp_expires_at']);

    $token = 'mock-jwt-token-' . $user['id'];
    echo json_encode(['success' => true, 'token' => $token, 'user' => $user]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
