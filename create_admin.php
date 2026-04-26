<?php
/**
 * Create Admin User Script
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/app/config/Config.php';
require_once __DIR__ . '/app/config/DatabaseConnection.php';

Config::load();

$password = password_hash('Admin@123', PASSWORD_BCRYPT);

$sql = "INSERT IGNORE INTO users (name, email, password_hash, role, is_active, created_at) 
        VALUES ('System Administrator', 'admin@techapp.com', ?, 'admin', 1, NOW())";

$stmt = DatabaseConnection::getConnection()->prepare($sql);
$stmt->bind_param('s', $password);

if ($stmt->execute()) {
    echo "Admin user created successfully!\n";
    echo "Email: admin@techapp.com\n";
    echo "Password: Admin@123\n";
} else {
    echo "Error: " . $stmt->error . "\n";
}