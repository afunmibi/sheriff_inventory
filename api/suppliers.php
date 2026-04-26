<?php
/**
 * Suppliers API Handler
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once __DIR__ . '/../app/config/Config.php';
require_once __DIR__ . '/../app/config/DatabaseConnection.php';

Config::load();

try {
    $conn = DatabaseConnection::getConnection();
    $method = $_SERVER['REQUEST_METHOD'];
    
    // GET suppliers
    if ($method === 'GET') {
        $result = $conn->query("SELECT * FROM suppliers WHERE status = 'active' ORDER BY company_name ASC");
        $suppliers = [];
        while ($row = $result->fetch_assoc()) {
            $suppliers[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => ['data' => $suppliers]]);
        exit;
    }
    
    // POST - Create supplier
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $company_name = $data['company_name'] ?? '';
        
        if (empty($company_name)) {
            echo json_encode(['success' => false, 'message' => 'Company name required']);
            exit;
        }
        
        $contact_person = $data['contact_person'] ?? '';
        $email = $data['email'] ?? '';
        $phone = $data['phone'] ?? '';
        $city = $data['city'] ?? '';
        $payment_terms = $data['payment_terms'] ?? 'COD';
        
        $stmt = $conn->prepare("INSERT INTO suppliers (company_name, contact_person, email, phone, city, payment_terms, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param('ssssss', $company_name, $contact_person, $email, $phone, $city, $payment_terms);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Supplier created', 'data' => ['supplier_id' => $conn->insert_id]]);
        exit;
    }

    // PUT - Update supplier
    if ($method === 'PUT') {
        $id = (int)($_GET['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$id && isset($data['supplier_id'])) $id = (int)$data['supplier_id'];
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Supplier ID required']);
            exit;
        }
        
        $company_name = $data['company_name'] ?? '';
        $contact_person = $data['contact_person'] ?? '';
        $email = $data['email'] ?? '';
        $phone = $data['phone'] ?? '';
        $city = $data['city'] ?? '';
        $payment_terms = $data['payment_terms'] ?? 'COD';

        $stmt = $conn->prepare("UPDATE suppliers SET company_name = ?, contact_person = ?, email = ?, phone = ?, city = ?, payment_terms = ? WHERE supplier_id = ?");
        $stmt->bind_param('ssssssi', $company_name, $contact_person, $email, $phone, $city, $payment_terms, $id);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Supplier updated']);
        exit;
    }

    // DELETE - Delete supplier
    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Supplier ID required']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE suppliers SET status = 'inactive' WHERE supplier_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Supplier deleted']);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}