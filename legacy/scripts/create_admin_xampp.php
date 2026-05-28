<?php
/**
 * Create Admin - Use XAMPP MySQL
 */

// Settings for XAMPP MySQL
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'shevvy_sheriff_inventory';

// Connect
$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Hash password
$password = password_hash('Admin@123', PASSWORD_BCRYPT);

// Insert admin user
$sql = "INSERT INTO users (name, email, password_hash, role, is_active) 
        VALUES ('System Administrator', 'admin@techapp.com', ?, 'admin', 1)
        ON DUPLICATE KEY UPDATE password_hash = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ss', $password, $password);

if (mysqli_stmt_execute($stmt)) {
    echo "Admin user created/updated successfully!\n";
    echo "Email: admin@techapp.com\n";
    echo "Password: Admin@123\n";
} else {
    echo "Error: " . mysqli_stmt_error($stmt) . "\n";
}

mysqli_close($conn);
