<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Update Profile API - Menha Boutique PHP
 * Updates user first name, last name, phone number, and uploads profile image.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized: Logged-in user required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$currentUser = getCurrentUser();
$userId = $currentUser['id'];

$input = json_decode(file_get_contents('php://input'), true);

$firstName = isset($input['firstName']) ? trim($input['firstName']) : '';
$lastName  = isset($input['lastName'])  ? trim($input['lastName'])  : '';
$phone     = isset($input['phoneNumber']) ? trim($input['phoneNumber']) : '';
$avatarData = isset($input['avatarData']) ? $input['avatarData'] : null; // Base64 image data

if (empty($firstName)) {
    http_response_code(400);
    echo json_encode(['error' => 'First name is required']);
    exit;
}

try {
    $pdo = getDBConnection();

    // Auto-alter table to ensure avatar_url exists
    try {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `avatar_url` VARCHAR(255) DEFAULT NULL AFTER `phone_number`");
    } catch (PDOException $e) {
        // Ignore if column already exists
    }

    $avatarUrl = null;
    $hasNewAvatar = false;
    
    if ($avatarData !== null) {
        $hasNewAvatar = true;
        if (!empty($avatarData)) {
            // Process base64 image (Format: data:image/png;base64,iVBORw...)
            if (preg_match('/^data:image\/(\w+);base64,/', $avatarData, $type)) {
                $data = substr($avatarData, strpos($avatarData, ',') + 1);
                $type = strtolower($type[1]); // png, jpg, jpeg, webp

                if (in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $data = base64_decode($data);

                    if ($data !== false) {
                        $targetDir = __DIR__ . '/../assets/images/uploads/';
                        if (!file_exists($targetDir)) {
                            mkdir($targetDir, 0755, true);
                        }

                        $filename = 'avatar_' . $userId . '_' . time() . '.' . $type;
                        $targetFilePath = $targetDir . $filename;

                        if (file_put_contents($targetFilePath, $data)) {
                            $avatarUrl = 'assets/images/uploads/' . $filename;
                        } else {
                            http_response_code(500);
                            echo json_encode(['error' => 'Failed to write avatar file to disk']);
                            exit;
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid base64 encoding']);
                        exit;
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Unsupported image format: ' . $type]);
                    exit;
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid image format data']);
                exit;
            }
        }
    }

    // Update query
    if ($hasNewAvatar) {
        if ($avatarUrl) {
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone_number = ?, avatar_url = ? WHERE id = ?");
            $stmt->execute([$firstName, $lastName ?: null, $phone ?: null, $avatarUrl, $userId]);
        } else {
            // avatarData was empty, meaning remove profile picture
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone_number = ?, avatar_url = NULL WHERE id = ?");
            $stmt->execute([$firstName, $lastName ?: null, $phone ?: null, $userId]);
        }
    } else {
        // Keep existing avatar_url
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone_number = ? WHERE id = ?");
        $stmt->execute([$firstName, $lastName ?: null, $phone ?: null, $userId]);
    }

    // Fetch updated user info
    $stmt = $pdo->prepare("SELECT id, email, first_name, last_name, phone_number, role, avatar_url FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);

    // Update PHP session
    $_SESSION['user'] = $updatedUser;

    echo json_encode(['success' => true, 'user' => $updatedUser]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
