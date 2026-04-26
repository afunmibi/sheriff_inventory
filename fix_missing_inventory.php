<?php
/**
 * One-time script to initialize inventory records for products that don't have one.
 */
require_once __DIR__ . '/app/config/DatabaseConnection.php';

try {
    $conn = DatabaseConnection::getConnection();
    
    // Find products without inventory records
    $sql = "SELECT p.product_id, p.reorder_level 
            FROM products p 
            LEFT JOIN inventory i ON p.product_id = i.product_id 
            WHERE i.inventory_id IS NULL AND p.is_active = 1";
    
    $result = $conn->query($sql);
    $count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $productId = $row['product_id'];
        $reorderLevel = $row['reorder_level'];
        
        // Insert default inventory record
        $stmt = $conn->prepare("INSERT INTO inventory (product_id, quantity_on_hand, status) VALUES (?, 0, 'out_of_stock')");
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $stmt->close();
        $count++;
    }
    
    echo "Successfully initialized inventory records for $count products.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
