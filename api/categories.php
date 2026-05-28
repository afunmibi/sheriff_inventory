<?php
/**
 * Categories API Handler
 */
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../app/config/Config.php';
require_once __DIR__ . '/../app/config/DatabaseConnection.php';

Config::load();
$role = strtolower($_SESSION['user']['role'] ?? '');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// RESTRICTION: Admins and Managers can manage categories
$isAuthorized = (in_array($role, ['admin', 'administrator', 'manager']));
if (in_array($method, ['POST', 'PUT', 'DELETE'], true) && !$isAuthorized) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Only Admins and Managers can manage categories']);
    exit;
}

try {
    $conn = DatabaseConnection::getConnection();
    
    // Auto-create table if missing
    $conn->query("CREATE TABLE IF NOT EXISTS categories (
        category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

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

        case 'PUT':
            $id = (int)($_GET['id'] ?? 0);
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$id || empty($data['name'])) {
                echo json_encode(['success' => false, 'message' => 'ID and name are required']);
                exit;
            }
            $newName = trim((string)$data['name']);
            $description = trim((string)($data['description'] ?? ''));

            // Get old name first
            $oldStmt = $conn->prepare("SELECT name FROM categories WHERE category_id = ?");
            $oldStmt->bind_param('i', $id);
            $oldStmt->execute();
            $oldRes = $oldStmt->get_result()->fetch_assoc();
            $oldName = $oldRes['name'] ?? '';
            $oldStmt->close();

            if (!$oldName) {
                echo json_encode(['success' => false, 'message' => 'Category not found']);
                exit;
            }

            $conn->begin_transaction();
            try {
                // Update category
                $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE category_id = ?");
                $stmt->bind_param('ssi', $newName, $description, $id);
                $stmt->execute();
                $stmt->close();

                // Update products using this category name
                if ($newName !== $oldName) {
                    $updateProducts = $conn->prepare("UPDATE products SET category = ? WHERE category = ?");
                    $updateProducts->bind_param('ss', $newName, $oldName);
                    $updateProducts->execute();
                    $updateProducts->close();
                }

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Category updated']);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Failed to update: ' . $e->getMessage()]);
            }
            break;

        case 'DELETE':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID is required']);
                exit;
            }
            
            // Get category name
            $nameStmt = $conn->prepare("SELECT name FROM categories WHERE category_id = ?");
            $nameStmt->bind_param('i', $id);
            $nameStmt->execute();
            $nameRes = $nameStmt->get_result()->fetch_assoc();
            $catName = $nameRes['name'] ?? '';
            $nameStmt->close();

            if (!$catName) {
                echo json_encode(['success' => false, 'message' => 'Category not found']);
                exit;
            }

            $conn->begin_transaction();
            try {
                // Reassign products to 'Uncategorized'
                $reassignStmt = $conn->prepare("UPDATE products SET category = 'Uncategorized' WHERE category = ?");
                $reassignStmt->bind_param('s', $catName);
                $reassignStmt->execute();
                $reassignStmt->close();

                // Delete the category
                $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Category deleted and products reassigned to Uncategorized']);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Failed to delete: ' . $e->getMessage()]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
