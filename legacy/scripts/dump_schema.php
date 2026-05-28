<?php
require_once 'c:/xampp/htdocs/opencode_projects/sheriff_inventory/app/config/Config.php';
require_once 'c:/xampp/htdocs/opencode_projects/sheriff_inventory/app/config/DatabaseConnection.php';
Config::load();
$conn = DatabaseConnection::getConnection();
$tables = ['products', 'inventory', 'categories', 'sales_transactions', 'purchase_orders', 'purchase_order_items', 'suppliers', 'users', 'settings'];
foreach ($tables as $table) {
    echo "Table: $table\n";
    $res = $conn->query("DESCRIBE $table");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo "  {$row['Field']} ({$row['Type']})\n";
        }
    } else {
        echo "  Error describing table: " . $conn->error . "\n";
    }
    echo "\n";
}
