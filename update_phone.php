<?php
/**
 * Script to update business phone number
 */
require_once __DIR__ . '/app/config/DatabaseConnection.php';

try {
    $conn = DatabaseConnection::getConnection();
    $phone = '+2348032488020';
    
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, category) VALUES ('business_phone', ?, 'business') 
                           ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $category = 'business';
    $stmt->bind_param('s', $phone);
    $stmt->execute();
    
    echo "Business phone updated to $phone\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
