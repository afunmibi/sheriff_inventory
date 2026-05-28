<?php
/**
 * One-time migration: creates store_customers table
 * Access via browser: http://localhost/.../api/migrate.php
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../bootstrap.php';

$config = Core\Config::get('db');
$conn = new mysqli($config['host'], $config['username'], $config['password'], $config['database'], (int)$config['port']);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed: ' . $conn->connect_error]);
    exit;
}

$sql = "CREATE TABLE IF NOT EXISTS store_customers (
    customer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$result = [];
if ($conn->query($sql)) {
    $result = ['success' => true, 'message' => 'store_customers table created'];
} else {
    $result = ['success' => false, 'message' => $conn->error];
}
$conn->close();
echo json_encode($result);
exit;
