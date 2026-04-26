<?php
require_once __DIR__ . '/app/config/Config.php';
require_once __DIR__ . '/app/config/DatabaseConnection.php';

Config::load();
$conn = DatabaseConnection::getConnection();

$conn->query("DROP TRIGGER IF EXISTS tr_inventory_after_update");
$conn->query("DROP TRIGGER IF EXISTS tr_product_after_insert");
$conn->query("DROP TRIGGER IF EXISTS tr_sale_after_insert");

echo "Triggers dropped successfully\n";