<?php
/**
 * Drop problematic triggers
 */
header('Content-Type: application/json');

require_once __DIR__ . '/app/config/Config.php';
require_once __DIR__ . '/app/config/DatabaseConnection.php';

Config::load();

try {
    $conn = DatabaseConnection::getConnection();
    
    // Drop triggers - ignore errors if they don't exist
    $conn->query("DROP TRIGGER IF EXISTS tr_inventory_after_update");
    $conn->query("DROP TRIGGER IF EXISTS tr_product_after_insert");
    $conn->query("DROP TRIGGER IF EXISTS tr_sale_after_insert");
    
    echo json_encode(['success' => true, 'message' => 'Triggers dropped']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}