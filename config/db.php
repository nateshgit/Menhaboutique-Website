<?php
/**
 * Database connection config - Menha Boutique PHP
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'u227935699_admin');
define('DB_PASS', 'Men#@b0ut!que');
define('DB_NAME', 'u227935699_menha');

function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production, log error and show a generic message.
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $pdo;
}
