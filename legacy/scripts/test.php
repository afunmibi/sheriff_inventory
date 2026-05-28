<?php
/**
 * Quick Test Script
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/app/config/',
        __DIR__ . '/app/core/',
        __DIR__ . '/app/exceptions/',
        __DIR__ . '/app/helpers/',
        __DIR__ . '/app/models/',
    ];
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

Config::load();

echo "=== Tech Application Test ===\n\n";

// Test 1: Database Connection
echo "1. Testing Database Connection...\n";
try {
    $db = DatabaseConnection::getConnection();
    echo "   [OK] Database connected\n";
} catch (Exception $e) {
    echo "   [FAIL] Database: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check users table
echo "2. Testing Users Table...\n";
try {
    $result = $db->query("SELECT COUNT(*) as cnt FROM users");
    $row = $result->fetch_assoc();
    echo "   [OK] Users table exists, " . $row['cnt'] . " users\n";
} catch (Exception $e) {
    echo "   [FAIL] Users: " . $e->getMessage() . "\n";
}

// Test 3: Check products table  
echo "3. Testing Products Table...\n";
try {
    $result = $db->query("SELECT COUNT(*) as cnt FROM products");
    $row = $result->fetch_assoc();
    echo "   [OK] Products table exists, " . $row['cnt'] . " products\n";
} catch (Exception $e) {
    echo "   [FAIL] Products: " . $e->getMessage() . "\n";
}

// Test 4: Authenticate
echo "4. Testing Authentication...\n";
try {
    require_once __DIR__ . '/app/models/User.php';
    $userModel = new User();
    $user = $userModel->authenticate('admin@techapp.com', 'Admin@123');
    if ($user) {
        echo "   [OK] Login successful: " . $user['name'] . " (" . $user['role'] . ")\n";
    } else {
        echo "   [FAIL] Invalid credentials\n";
    }
} catch (Exception $e) {
    echo "   [FAIL] Auth: " . $e->getMessage() . "\n";
}

echo "\n=== Tests Complete ===\n";
