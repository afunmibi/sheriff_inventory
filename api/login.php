<?php
/**
 * Simple Login Handler - CLI test version
 */

// For testing from CLI
if (php_sapi_name() === 'cli') {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['email'] = 'admin@techapp.com';
    $_POST['password'] = 'Admin@123';
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../app/config/Config.php';
require_once __DIR__ . '/../app/config/DatabaseConnection.php';

Config::load();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $data = $_POST;
}

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password required']);
    exit;
}

try {
    $conn = DatabaseConnection::getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        exit;
    }
    
    if (!password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'data' => [
            'user' => [
                'user_id' => $user['user_id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}