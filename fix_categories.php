<?php
require_once 'app/config/Config.php';
require_once 'app/config/DatabaseConnection.php';
Config::load();
try {
    $conn = DatabaseConnection::getConnection();
    $sql = "CREATE TABLE IF NOT EXISTS categories (
        category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if ($conn->query($sql)) {
        echo "Table created or already exists.\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
?>
