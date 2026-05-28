<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../app/config/Config.php';
require_once __DIR__ . '/../app/config/DatabaseConnection.php';

Config::load();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = strtolower($_SESSION['user']['role'] ?? '');
$isAuthorized = in_array($role, ['admin', 'administrator', 'manager']);

try {
    $conn = DatabaseConnection::getConnection();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $productId = (int)($_GET['product_id'] ?? 0);
        if ($productId <= 0) { echo json_encode(['success' => false, 'message' => 'Product ID required']); exit; }
        $stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, image_id ASC");
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $images = [];
        while ($row = $result->fetch_assoc()) { $images[] = $row; }
        echo json_encode(['success' => true, 'data' => $images]);
        exit;
    }

    if ($method === 'POST') {
        if (!$isAuthorized) { echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit; }
        $productId = (int)($_POST['product_id'] ?? 0);
        $label = trim($_POST['label'] ?? '');
        if ($productId <= 0 || empty($_FILES['image'])) { echo json_encode(['success' => false, 'message' => 'Product ID and image required']); exit; }

        $uploadDir = __DIR__ . '/../public/uploads/products/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $allowed)) { echo json_encode(['success' => false, 'message' => 'Invalid file type']); exit; }

        $filename = 'product_' . uniqid() . '_' . time() . '.' . $ext;
        $dest = $uploadDir . $filename;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) { echo json_encode(['success' => false, 'message' => 'Upload failed']); exit; }

        $url = 'uploads/products/' . $filename;

        // Get next sort order
        $sortStmt = $conn->prepare("SELECT COALESCE(MAX(sort_order), -1) + 1 AS next FROM product_images WHERE product_id = ?");
        $sortStmt->bind_param('i', $productId);
        $sortStmt->execute();
        $sortRow = $sortStmt->get_result()->fetch_assoc();
        $sortOrder = (int)$sortRow['next'];

        $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, label, sort_order) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('issi', $productId, $url, $label, $sortOrder);
        $stmt->execute();
        $imageId = $conn->insert_id;

        echo json_encode(['success' => true, 'message' => 'Image uploaded', 'data' => ['image_id' => $imageId, 'image_url' => $url, 'label' => $label]]);
        exit;
    }

    if ($method === 'DELETE') {
        if (!$isAuthorized) { echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit; }
        $imageId = (int)($_GET['id'] ?? 0);
        if ($imageId <= 0) { echo json_encode(['success' => false, 'message' => 'Image ID required']); exit; }

        $stmt = $conn->prepare("SELECT image_url FROM product_images WHERE image_id = ?");
        $stmt->bind_param('i', $imageId);
        $stmt->execute();
        $img = $stmt->get_result()->fetch_assoc();
        if (!$img) { echo json_encode(['success' => false, 'message' => 'Image not found']); exit; }

        $filePath = __DIR__ . '/../public/' . $img['image_url'];
        if (file_exists($filePath)) unlink($filePath);

        $del = $conn->prepare("DELETE FROM product_images WHERE image_id = ?");
        $del->bind_param('i', $imageId);
        $del->execute();

        echo json_encode(['success' => true, 'message' => 'Image deleted']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
