<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Reset Password API - Menha Boutique PHP
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = isset($input['action']) ? $input['action'] : '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$pdo = getDBConnection();

if ($action === 'request_reset') {
    $email = isset($input['email']) ? trim($input['email']) : '';
    if (empty($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email address is required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, first_name FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // Security best practice: do not reveal if user exists. Just return success.
        if (!$user) {
            echo json_encode(['success' => true]);
            exit;
        }

        // Generate a 6-character OTP
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $otp = '';
        for ($i = 0; $i < 6; $i++) {
            $otp .= $chars[rand(0, strlen($chars) - 1)];
        }

        $expiresAt = date('Y-m-d H:i:s', time() + 15 * 60); // 15 minutes validity

        // Save in DB
        $updateStmt = $pdo->prepare("
            UPDATE users 
            SET reset_otp = :otp, otp_expires_at = :expires_at 
            WHERE email = :email
        ");
        $updateStmt->execute([
            'otp' => $otp,
            'expires_at' => $expiresAt,
            'email' => $email
        ]);

        // Send Email using Hostinger / Native PHP Mail
        $to = $email;
        $subject = "Menha Boutique - Password Reset OTP";
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Menha Boutique <no-reply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
        
        $messageHtml = "
            <html>
            <body style='font-family: Poppins, sans-serif; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 16px;'>
                    <h2 style='color: #004d40; text-align: center;'>Reset Your Password</h2>
                    <p>Hello " . htmlspecialchars($user['first_name'] ?: 'User') . ",</p>
                    <p>We received a request to reset your password. Please use the following OTP (One-Time Password) to proceed:</p>
                    <div style='background: #f0fdf4; padding: 15px; text-align: center; border-radius: 12px; font-size: 24px; font-weight: 800; color: #004d40; letter-spacing: 4px; margin: 20px 0;'>
                        {$otp}
                    </div>
                    <p style='font-size: 13px; color: #666;'>This OTP is valid for 15 minutes. If you did not request this, you can safely ignore this email.</p>
                    <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                    <p style='text-align: center; font-size: 12px; color: #999;'>&copy; " . date('Y') . " Menha Boutique. All rights reserved.</p>
                </div>
            </body>
            </html>
        ";

        // Mute mail errors so it doesn't crash on local environments without SMTP
        @mail($to, $subject, $messageHtml, $headers);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'verify_otp') {
    $email = isset($input['email']) ? trim($input['email']) : '';
    $otp = isset($input['otp']) ? trim($input['otp']) : '';

    if (empty($email) || empty($otp)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email and OTP are required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT reset_otp, otp_expires_at 
            FROM users 
            WHERE email = :email
        ");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !$user['reset_otp'] || $user['reset_otp'] !== strtoupper($otp)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid OTP code.']);
            exit;
        }

        if (strtotime($user['otp_expires_at']) < time()) {
            http_response_code(400);
            echo json_encode(['error' => 'OTP has expired. Please request a new one.']);
            exit;
        }

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'update_password') {
    $email = isset($input['email']) ? trim($input['email']) : '';
    $otp = isset($input['otp']) ? trim($input['otp']) : '';
    $newPassword = isset($input['new_password']) ? $input['new_password'] : '';

    if (empty($email) || empty($otp) || empty($newPassword)) {
        http_response_code(400);
        echo json_encode(['error' => 'All fields are required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT reset_otp, otp_expires_at 
            FROM users 
            WHERE email = :email
        ");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !$user['reset_otp'] || $user['reset_otp'] !== strtoupper($otp)) {
            http_response_code(400);
            echo json_encode(['error' => 'Verification failed. Invalid OTP.']);
            exit;
        }

        if (strtotime($user['otp_expires_at']) < time()) {
            http_response_code(400);
            echo json_encode(['error' => 'OTP has expired. Please start over.']);
            exit;
        }

        // Hash new password using bcrypt
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);

        // Update password and clear OTP
        $updateStmt = $pdo->prepare("
            UPDATE users 
            SET password_hash = :password_hash, reset_otp = NULL, otp_expires_at = NULL 
            WHERE email = :email
        ");
        $updateStmt->execute([
            'password_hash' => $passwordHash,
            'email' => $email
        ]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
