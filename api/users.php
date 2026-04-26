<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ . '/../app/config/Config.php';
require_once __DIR__ . '/../app/config/DatabaseConnection.php';

Config::load();
$method = $_SERVER['REQUEST_METHOD'];
$conn = DatabaseConnection::getConnection();

try {
    if ($method === 'GET') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if ($id) {
            $stmt = $conn->prepare("SELECT user_id, name, email, role, is_active FROM users WHERE user_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            echo json_encode(['success' => true, 'data' => $result->fetch_assoc()]);
        } else {
            $result = $conn->query("SELECT user_id, name, email, role, is_active FROM users ORDER BY name ASC");
            $users = [];
            while ($row = $result->fetch_assoc()) { $users[] = $row; }
            echo json_encode(['success' => true, 'data' => ['data' => $users]]);
        }
    } 
    elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) { throw new Exception('Invalid input'); }
        
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'Staff';
        
        if (empty($name) || empty($email) || empty($password)) {
            throw new Exception('Name, email and password are required');
        }
        
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, 1)");
        $stmt->bind_param('ssss', $name, $email, $hash, $role);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User created successfully']);
        } else {
            throw new Exception('Email may already exist');
        }
    }
    elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($_GET['id'] ?? $data['user_id'] ?? 0);
        
        if (!$id) throw new Exception('User ID required');
        
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $role = $data['role'] ?? 'Staff';
        
        if ($data['password']) {
            $hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=?, password_hash=? WHERE user_id=?");
            $stmt->bind_param('ssssi', $name, $email, $role, $hash, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=? WHERE user_id=?");
            $stmt->bind_param('sssi', $name, $email, $role, $id);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
        } else {
            throw new Exception($conn->error);
        }
    }
    elseif ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) throw new Exception('User ID required');
        
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        } else {
            throw new Exception($conn->error);
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
