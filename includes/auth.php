<?php
/**
 * Auth Helper - Menha Boutique PHP
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getAuthorizationHeader() {
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } else if (isset($_SERVER['HTTP_X_AUTHORIZATION'])) {
        $headers = trim($_SERVER["HTTP_X_AUTHORIZATION"]);
    } else if (isset($_SERVER['X-Authorization'])) {
        $headers = trim($_SERVER["X-Authorization"]);
    } else if (isset($_SERVER['HTTP_X_AUTH_TOKEN'])) {
        $headers = trim($_SERVER["HTTP_X_AUTH_TOKEN"]);
    } else if (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        } else if (isset($requestHeaders['X-Authorization'])) {
            $headers = trim($requestHeaders['X-Authorization']);
        } else if (isset($requestHeaders['X-Auth-Token'])) {
            $headers = trim($requestHeaders['X-Auth-Token']);
        }
    }
    return $headers;
}

function isLoggedIn() {
    $authHeader = getAuthorizationHeader();
    
    if (isset($_SESSION['user'])) {
        try {
            $db = getDBConnection();
            $stmt = $db->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$_SESSION['user']['id']]);
            if ($stmt->fetch()) {
                // If Authorization header is provided, make sure it matches the session user
                if ($authHeader && preg_match('/Bearer\s+(mock-jwt-token-([\w-]+))/', $authHeader, $matches)) {
                    $userId = $matches[2];
                    if (strcasecmp($_SESSION['user']['id'], $userId) !== 0) {
                        unset($_SESSION['user']);
                        return false;
                    }
                }
                return true;
            }
        } catch (Exception $e) {}
        unset($_SESSION['user']);
    }
    
    // Check Authorization header for mobile token
    if ($authHeader) {
        if (preg_match('/Bearer\s+(mock-jwt-token-([\w-]+))/', $authHeader, $matches)) {
            $userId = $matches[2];
            try {
                $db = getDBConnection();
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    $_SESSION['user'] = $user;
                    return true;
                }
            } catch (Exception $e) {
                return false;
            }
        }
    }
    return false;
}

function getCurrentUser() {
    return isLoggedIn() ? $_SESSION['user'] : null;
}

function isAdmin() {
    $user = getCurrentUser();
    return $user && isset($user['role']) && $user['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header("Location: index.php");
        exit;
    }
}
