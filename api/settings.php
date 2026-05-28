<?php
/**
 * Settings API Handler
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../app/config/Config.php';
require_once __DIR__ . '/../app/config/DatabaseConnection.php';

Config::load();

$method = $_SERVER['REQUEST_METHOD'];

try {
    $conn = DatabaseConnection::getConnection();
    
    // GET - Get settings
    if ($method === 'GET') {
        $result = $conn->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $settings
        ]);
        exit;
    }
    
    // POST - Update settings
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $fields = [
            'business_name' => 'business_name',
            'business_address' => 'business_address',
            'business_phone' => 'business_phone',
            'business_email' => 'business_email',
            'currency_symbol' => 'currency_symbol',
            'tax_rate' => 'tax_rate',
            'whatsapp_number' => 'whatsapp_number',
            'storefront_welcome' => 'storefront_welcome'
        ];
        
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        
        foreach ($fields as $key => $dbField) {
            if (isset($input[$dbField])) {
                $value = $input[$dbField];
                $stmt->bind_param('ss', $key, $value);
                $stmt->execute();
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Settings updated']);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
