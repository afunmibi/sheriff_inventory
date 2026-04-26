<?php
/**
 * Tech Application - Direct API Entry Point
 */

// Set content type
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request info
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove /api/ prefix
$endpoint = str_replace('/api/', '', $uri);

// Include required files
require_once __DIR__ . '/../app/config/Config.php';
require_once __DIR__ . '/../app/config/DatabaseConnection.php';
require_once __DIR__ . '/../app/core/Logger.php';
require_once __DIR__ . '/../app/exceptions/AppException.php';

Config::load();

try {
    // Route: /api/auth/login
    if ($method === 'POST' && $endpoint === 'auth/login') {
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        $ctrl = new AuthController();
        $ctrl->login();
        exit;
    }
    
    // Route: /api/auth/logout
    if ($method === 'POST' && $endpoint === 'auth/logout') {
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        $ctrl = new AuthController();
        $ctrl->logout();
        exit;
    }
    
    // Route: /api/products
    if ($endpoint === 'products') {
        require_once __DIR__ . '/../app/controllers/ProductController.php';
        $ctrl = new ProductController();
        if ($method === 'GET') $ctrl->index();
        elseif ($method === 'POST') $ctrl->store();
        exit;
    }
    
    // Route: /api/inventory
    if ($endpoint === 'inventory') {
        require_once __DIR__ . '/../app/controllers/InventoryController.php';
        $ctrl = new InventoryController();
        if ($method === 'GET') $ctrl->index();
        exit;
    }
    
    // Route: /api/sales
    if ($endpoint === 'sales' || $endpoint === 'sales/daily') {
        require_once __DIR__ . '/../app/controllers/SaleController.php';
        $ctrl = new SaleController();
        if ($method === 'GET') $ctrl->index();
        elseif ($method === 'POST') $ctrl->store();
        elseif ($endpoint === 'sales/daily' && $method === 'GET') $ctrl->daily();
        exit;
    }
    
    // Route: /api/dashboard
    if ($endpoint === 'dashboard') {
        require_once __DIR__ . '/../app/controllers/DashboardController.php';
        $ctrl = new DashboardController();
        if ($method === 'GET') $ctrl->index();
        exit;
    }
    
    // Route: /api/suppliers
    if ($endpoint === 'suppliers') {
        require_once __DIR__ . '/../app/controllers/SupplierController.php';
        $ctrl = new SupplierController();
        if ($method === 'GET') $ctrl->index();
        elseif ($method === 'POST') $ctrl->store();
        exit;
    }
    
    // Route: /api/purchase-orders
    if ($endpoint === 'purchase-orders') {
        require_once __DIR__ . '/../app/controllers/PurchaseOrderController.php';
        $ctrl = new PurchaseOrderController();
        if ($method === 'GET') $ctrl->index();
        elseif ($method === 'POST') $ctrl->store();
        exit;
    }
    
    // Route: /api/reports
    if ($endpoint === 'reports') {
        require_once __DIR__ . '/../app/controllers/ReportsController.php';
        $ctrl = new ReportsController();
        if ($method === 'GET') $ctrl->index();
        exit;
    }
    
    // No route found
    echo json_encode([
        'success' => false,
        'message' => 'API endpoint not found: ' . $endpoint,
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}