<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Register API - Menha Boutique PHP
 * Creates a new user account with bcrypt-hashed password.
 * Also saves an initial delivery address when provided.
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

$email     = isset($input['email'])       ? trim($input['email'])       : '';
$password  = isset($input['password'])    ? $input['password']          : '';
$firstName = isset($input['firstName'])   ? trim($input['firstName'])   : '';
$lastName  = isset($input['lastName'])    ? trim($input['lastName'])    : '';
$phone     = isset($input['phoneNumber']) ? trim($input['phoneNumber']) : '';
$address   = isset($input['address'])     ? $input['address']           : null;

if (empty($email) || empty($password) || empty($firstName)) {
    http_response_code(400);
    echo json_encode(['error' => 'First name, email and password are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email address']);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'Password must be at least 6 characters']);
    exit;
}

function generateUUID(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

try {
    $pdo = getDBConnection();

    // Duplicate email check
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'An account with this email already exists']);
        exit;
    }

    $userId       = generateUUID();
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $pdo->prepare("
        INSERT INTO users (id, email, password_hash, first_name, last_name, phone_number, role)
        VALUES (?, ?, ?, ?, ?, ?, 'customer')
    ")->execute([$userId, $email, $passwordHash, $firstName, $lastName ?: null, $phone ?: null]);

    // Optional initial address
    if ($address && !empty($address['line1'])) {
        $pdo->prepare("
            INSERT INTO addresses
                (id, user_id, first_name, last_name, address_line1, address_line2,
                 city, state, zip_code, country, phone_number, is_default)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ")->execute([
            generateUUID(), $userId,
            $firstName, $lastName ?: '',
            $address['line1'],
            $address['line2']     ?? null,
            $address['city']      ?? '',
            $address['state']     ?? '',
            $address['postalCode'] ?? '',
            $address['country']   ?? '',
            $phone ?: null,
        ]);
    }

    // Fetch saved user (without sensitive columns)
    $stmt = $pdo->prepare("SELECT id, email, first_name, last_name, phone_number, role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Establish PHP session
    $_SESSION['user'] = $user;

    $token = 'mock-jwt-token-' . $userId;
    echo json_encode(['success' => true, 'token' => $token, 'user' => $user]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed. Please try again.']);
}
