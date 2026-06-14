<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo = getDBConnection();
    
    // Check if reset_otp already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'reset_otp'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN reset_otp VARCHAR(10) DEFAULT NULL AFTER role");
        echo "Column reset_otp added successfully.\n";
    } else {
        echo "Column reset_otp already exists.\n";
    }

    // Check if otp_expires_at already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'otp_expires_at'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN otp_expires_at TIMESTAMP NULL DEFAULT NULL AFTER reset_otp");
        echo "Column otp_expires_at added successfully.\n";
    } else {
        echo "Column otp_expires_at already exists.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
