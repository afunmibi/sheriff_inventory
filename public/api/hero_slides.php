<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../app/config/Config.php';
require_once __DIR__ . '/../../app/config/DatabaseConnection.php';

Config::load();
session_start();
$role = strtolower($_SESSION['user']['role'] ?? '');
$isAuthorized = in_array($role, ['admin', 'administrator', 'manager']);

try {
    $conn = DatabaseConnection::getConnection();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $onlyActive = !$isAuthorized || !isset($_GET['all']);
        $sql = $onlyActive
            ? "SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY sort_order ASC, slide_id ASC"
            : "SELECT * FROM hero_slides ORDER BY sort_order ASC, slide_id ASC";
        $stmt = $conn->query($sql);
        $slides = [];
        while ($row = $stmt->fetch_assoc()) { $slides[] = $row; }
        echo json_encode(['success' => true, 'data' => $slides]);
        exit;
    }

    if ($method === 'POST') {
        $overrideMethod = strtoupper($_POST['_method'] ?? '');
        if ($overrideMethod === 'PUT') {
            if (!$isAuthorized) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit; }
            $slideId = (int)($_POST['slide_id'] ?? 0);
            if ($slideId <= 0) { echo json_encode(['success' => false, 'message' => 'Slide ID required']); exit; }
            $imageUrl = trim($_POST['image_url'] ?? '');
            if (!empty($_FILES['image'])) {
                $uploadDir = __DIR__ . '/../uploads/slides/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','gif','webp'];
                if (!in_array($ext, $allowed)) { echo json_encode(['success' => false, 'message' => 'Invalid file type']); exit; }
                $filename = 'slide_' . uniqid() . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
                $imageUrl = 'uploads/slides/' . $filename;
            }
            $fields = []; $params = []; $types = '';
            $title = trim($_POST['title'] ?? ''); if ($title !== '') { $fields[] = "title = ?"; $params[] = $title; $types .= 's'; }
            $subtitle = trim($_POST['subtitle'] ?? ''); $fields[] = "subtitle = ?"; $params[] = $subtitle; $types .= 's';
            if ($imageUrl !== '') { $fields[] = "image_url = ?"; $params[] = $imageUrl; $types .= 's'; }
            $ctaText = trim($_POST['cta_text'] ?? ''); if ($ctaText !== '') { $fields[] = "cta_text = ?"; $params[] = $ctaText; $types .= 's'; }
            $ctaLink = trim($_POST['cta_link'] ?? ''); if ($ctaLink !== '') { $fields[] = "cta_link = ?"; $params[] = $ctaLink; $types .= 's'; }
            if (isset($_POST['sort_order'])) { $fields[] = "sort_order = ?"; $params[] = (int)$_POST['sort_order']; $types .= 'i'; }
            if (!empty($fields)) {
                $params[] = $slideId; $types .= 'i';
                $stmt = $conn->prepare("UPDATE hero_slides SET " . implode(', ', $fields) . " WHERE slide_id = ?");
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
            }
            echo json_encode(['success' => true, 'message' => 'Slide updated']);
            exit;
        }

        if (!$isAuthorized) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit; }
        if (!empty($_FILES['image'])) {
            $uploadDir = __DIR__ . '/../uploads/slides/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (!in_array($ext, $allowed)) { echo json_encode(['success' => false, 'message' => 'Invalid file type']); exit; }
            $filename = 'slide_' . uniqid() . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
            $imageUrl = 'uploads/slides/' . $filename;
        } else {
            $imageUrl = trim($_POST['image_url'] ?? '');
        }
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $ctaText = trim($_POST['cta_text'] ?? '');
        $ctaLink = trim($_POST['cta_link'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $stmt = $conn->prepare("INSERT INTO hero_slides (image_url, title, subtitle, cta_text, cta_link, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssi', $imageUrl, $title, $subtitle, $ctaText, $ctaLink, $sortOrder);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Slide created', 'data' => ['slide_id' => $conn->insert_id]]);
        exit;
    }

    if ($method === 'PUT') {
        if (!$isAuthorized) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit; }
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $slideId = (int)(($_GET['id'] ?? $data['slide_id'] ?? 0));
        if ($slideId <= 0) { echo json_encode(['success' => false, 'message' => 'Slide ID required']); exit; }
        $fields = []; $params = []; $types = '';
        foreach (['title', 'subtitle', 'image_url', 'cta_text', 'cta_link'] as $f) {
            if (isset($data[$f])) { $fields[] = "$f = ?"; $params[] = trim($data[$f]); $types .= 's'; }
        }
        if (isset($data['sort_order'])) { $fields[] = "sort_order = ?"; $params[] = (int)$data['sort_order']; $types .= 'i'; }
        if (isset($data['is_active'])) { $fields[] = "is_active = ?"; $params[] = (int)$data['is_active']; $types .= 'i'; }
        if (empty($fields)) { echo json_encode(['success' => false, 'message' => 'No fields to update']); exit; }
        $params[] = $slideId; $types .= 'i';
        $stmt = $conn->prepare("UPDATE hero_slides SET " . implode(', ', $fields) . " WHERE slide_id = ?");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Slide updated']); exit;
    }

    if ($method === 'DELETE') {
        if (!$isAuthorized) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit; }
        $slideId = (int)($_GET['id'] ?? 0);
        if ($slideId <= 0) { echo json_encode(['success' => false, 'message' => 'Slide ID required']); exit; }
        $stmt = $conn->prepare("SELECT image_url FROM hero_slides WHERE slide_id = ?");
        $stmt->bind_param('i', $slideId);
        $stmt->execute();
        $slide = $stmt->get_result()->fetch_assoc();
        if (!$slide) { echo json_encode(['success' => false, 'message' => 'Slide not found']); exit; }
        $filePath = __DIR__ . '/../' . $slide['image_url'];
        if (file_exists($filePath)) unlink($filePath);
        $del = $conn->prepare("DELETE FROM hero_slides WHERE slide_id = ?");
        $del->bind_param('i', $slideId);
        $del->execute();
        echo json_encode(['success' => true, 'message' => 'Slide deleted']); exit;
    }

    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
