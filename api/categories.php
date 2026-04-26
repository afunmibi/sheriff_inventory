<?php
/**
 * Categories API Handler
 */
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../app/config/Config.php';
require_once __DIR__ . '/../app/config/DatabaseConnection.php';

Config::load();

try {
    $conn = DatabaseConnection::getConnection();
    
    // Auto-create table if missing
    $conn->query("CREATE TABLE IF NOT EXISTS categories (
        category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    switch($method) {
        case 'GET':
            $sql = "SELECT * FROM categories ORDER BY name ASC";
            $result = $conn->query($sql);
            $categories = [];
            while($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
            echo json_encode(['success' => true, 'data' => ['data' => $categories]]);
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
            if (empty($data['name'])) {
                echo json_encode(['success' => false, 'message' => 'Category name is required']);
                exit;
            }
            $name = trim((string)$data['name']);
            $description = trim((string)($data['description'] ?? ''));
            
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->bind_param('ss', $name, $description);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Category created', 'data' => ['category_id' => $conn->insert_id]]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Category name must be unique']);
            }
            $stmt->close();
            break;

        case 'DELETE':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID is required']);
                exit;
            }
            
            // Check if categories are in use
            $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category = (SELECT name FROM categories WHERE category_id = ?)");
            $checkStmt->bind_param('i', $id);
            $checkStmt->execute();
            $res = $checkStmt->get_result()->fetch_assoc();
            
            if (($res['count'] ?? 0) > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete: Category is currently in use by products']);
                exit;
            }

            $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Category deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete category']);
            }
            $stmt->close();
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
