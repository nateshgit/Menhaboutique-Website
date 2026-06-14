<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Generic email sender - Menha Boutique PHP
 * Handles transactional emails (order confirmation, welcome, etc.)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$to    = isset($input['to'])   ? trim($input['to'])   : '';
$type  = isset($input['type']) ? trim($input['type']) : '';
$data  = isset($input['data']) ? $input['data']        : [];

if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid recipient email is required']);
    exit;
}

$subject = 'Menha Boutique';
$body    = '';

switch ($type) {
    case 'order_confirmation':
        $orderNum = $data['order_number'] ?? 'N/A';
        $subject  = "Menha Boutique – Order #{$orderNum} Confirmed";
        $body     = "
            <h2 style='color:#004d40;'>Your order has been confirmed!</h2>
            <p>Order Number: <strong>#{$orderNum}</strong></p>
            <p>Thank you for shopping with Menha Boutique. We'll notify you once your order is shipped.</p>
        ";
        break;

    case 'welcome':
        $name    = htmlspecialchars($data['name'] ?? 'Customer');
        $subject = 'Welcome to Menha Boutique!';
        $body    = "
            <h2 style='color:#004d40;'>Welcome, {$name}!</h2>
            <p>Your account has been created successfully. Start exploring our collection!</p>
        ";
        break;

    default:
        $subject = $data['subject'] ?? 'Menha Boutique';
        $body    = $data['message'] ?? '';
}

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: Menha Boutique <no-reply@{$_SERVER['HTTP_HOST']}>\r\n";

$html = "
<html><body style='font-family:Poppins,sans-serif;color:#333;'>
<div style='max-width:600px;margin:0 auto;padding:20px;border:1px solid #e2e8f0;border-radius:16px;'>
    {$body}
    <hr style='border:none;border-top:1px solid #e2e8f0;margin:20px 0;'>
    <p style='text-align:center;font-size:12px;color:#999;'>&copy; " . date('Y') . " Menha Boutique. All rights reserved.</p>
</div>
</body></html>
";

@mail($to, $subject, $html, $headers);

echo json_encode(['success' => true]);
