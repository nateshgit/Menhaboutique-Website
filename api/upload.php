<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Local File Upload API - Menha Boutique PHP
 * Handles saving uploaded files to assets/images/uploads/ instead of storing Base64 in database.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

// Only allow authenticated admins to upload files
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: Admin access required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

if (!isset($_FILES['images']) && !isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No files uploaded']);
    exit;
}

$uploadedFiles = [];
$targetDir = __DIR__ . '/../assets/images/uploads/';

// Create target directory if it doesn't exist
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0755, true);
}

// Normalize single file to list format
$filesToProcess = [];
if (isset($_FILES['image'])) {
    $filesToProcess[] = $_FILES['image'];
} elseif (isset($_FILES['images'])) {
    // If multiple files are uploaded under 'images[]'
    if (is_array($_FILES['images']['name'])) {
        $numFiles = count($_FILES['images']['name']);
        for ($i = 0; $i < $numFiles; $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $filesToProcess[] = [
                    'name' => $_FILES['images']['name'][$i],
                    'type' => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error' => $_FILES['images']['error'][$i],
                    'size' => $_FILES['images']['size'][$i]
                ];
            }
        }
    } else {
        $filesToProcess[] = $_FILES['images'];
    }
}

$allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$allowedVideoExtensions = ['mp4', 'webm', 'mov', 'avi'];
$allowedExtensions = array_merge($allowedImageExtensions, $allowedVideoExtensions);

foreach ($filesToProcess as $file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        continue;
    }

    $filename = basename($file['name']);
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExtensions)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file extension. Allowed: JPG, PNG, GIF, WebP, MP4, WebM, MOV, AVI.']);
        exit;
    }

    $prefix = in_array($ext, $allowedVideoExtensions) ? 'vid_' : 'img_';
    // Generate unique name
    $newFilename = uniqid($prefix, true) . '.' . $ext;
    $targetFilePath = $targetDir . $newFilename;

    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        // Return relative path from root directory
        $uploadedFiles[] = 'assets/images/uploads/' . $newFilename;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to move uploaded file.']);
        exit;
    }
}

echo json_encode([
    'success' => true,
    'urls' => $uploadedFiles
]);
