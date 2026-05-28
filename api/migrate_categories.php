<?php
$conn = new mysqli('localhost', 'root', '', 'shevvy_sheriff_inventory');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$sql1 = "CREATE TABLE IF NOT EXISTS categories (
    category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB";

$sql2 = "INSERT IGNORE INTO categories (name) VALUES 
('Chargers'), ('Cables'), ('Adapters'), ('Power Supplies'), ('Hubs'), ('Other')";

// Check if we need to migrate products table
$sql3 = "ALTER TABLE products MODIFY COLUMN category VARCHAR(100)";

if ($conn->query($sql1) === TRUE && $conn->query($sql2) === TRUE && $conn->query($sql3) === TRUE) {
    echo "Categories table created and products migrated";
} else {
    echo "Error: " . $conn->error;
}
$conn->close();
?>
