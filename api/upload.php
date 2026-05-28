<?php
/**
 * Image Upload API Handler
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// Check authorization
$role = strtolower($_SESSION['user']['role'] ?? '');
if (!in_array($role, ['admin', 'administrator', 'manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'message' => 'No image uploaded']);
    exit;
}

$file = $_FILES['image'];
$fileName = $file['name'];
$fileTmpName = $file['tmp_name'];
$fileSize = $file['size'];
$fileError = $file['error'];

$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

if (in_array($fileExt, $allowed)) {
    if ($fileError === 0) {
        if ($fileSize < 5000000) { // 5MB limit
            $fileNewName = "product_" . uniqid('', true) . "." . $fileExt;
            $uploadDir = '../uploads/products/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileDestination = $uploadDir . $fileNewName;
            
            if (move_uploaded_file($fileTmpName, $fileDestination)) {
                $publicPath = 'uploads/products/' . $fileNewName;
                echo json_encode(['success' => true, 'url' => $publicPath]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'File too large (Max 5MB)']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'File error code: ' . $fileError]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, WEBP, GIF allowed.']);
}
