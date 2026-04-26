<?php
/**
 * Tech App Computer House - Inventory Management System
 * Main Entry Point
 */

// Autoload classes
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/app/config/',
        __DIR__ . '/app/core/',
        __DIR__ . '/app/exceptions/',
        __DIR__ . '/app/helpers/',
        __DIR__ . '/app/models/',
    ];
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

Config::load();

// Handle API requests
$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Get the path from query param (set by .htaccess) or use REQUEST_URI
$route = $_GET['route'] ?? '';
if ($route) {
    $path = '/' . $route;
} else {
    // Fallback: detect from REQUEST_URI
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);  
    $fullPath = parse_url($requestUri, PHP_URL_PATH);  
    $path = $scriptDir === '\\' || $scriptDir === '/' ? $fullPath : str_replace($scriptDir, '', $fullPath);
}
if ($path === '') $path = '/';

// API routes
if (str_starts_with($path, '/api/')) {
    $endpoint = str_replace('/api/', '', $path);
    $parts = explode('/', $endpoint);
    $resource = $parts[0] ?? '';
    $id = $parts[1] ?? null;
    
    try {
        switch ($resource) {
            case 'auth':
                require_once __DIR__ . '/app/controllers/AuthController.php';
                $ctrl = new AuthController();
                if ($method === 'POST' && $id === 'login') $ctrl->login();
                elseif ($method === 'POST' && $id === 'logout') $ctrl->logout();
                elseif ($method === 'GET' && $id === 'me') $ctrl->me();
                break;
            case 'products':
                require_once __DIR__ . '/app/controllers/ProductController.php';
                $ctrl = new ProductController();
                if ($method === 'GET' && $id) $ctrl->show((int)$id);
                elseif ($method === 'GET') $ctrl->index();
                elseif ($method === 'POST') $ctrl->store();
                elseif ($method === 'PUT' && $id) $ctrl->update((int)$id);
                elseif ($method === 'DELETE' && $id) $ctrl->destroy((int)$id);
                break;
            case 'inventory':
                require_once __DIR__ . '/app/controllers/InventoryController.php';
                $ctrl = new InventoryController();
                if ($method === 'GET') $ctrl->index();
                elseif ($method === 'PUT' && $id) $ctrl->update((int)$id);
                elseif ($method === 'POST') $ctrl->adjust((int)$id);
                break;
            case 'sales':
                require_once __DIR__ . '/app/controllers/SaleController.php';
                $ctrl = new SaleController();
                if ($method === 'GET' && $id) $ctrl->show((int)$id);
                elseif ($method === 'GET') $ctrl->index();
                elseif ($method === 'POST') $ctrl->store();
                elseif ($method === 'GET' && $id === 'daily') $ctrl->daily();
                break;
            case 'suppliers':
                require_once __DIR__ . '/app/controllers/SupplierController.php';
                $ctrl = new SupplierController();
                if ($method === 'GET' && $id) $ctrl->show((int)$id);
                elseif ($method === 'GET') $ctrl->index();
                elseif ($method === 'POST') $ctrl->store();
                elseif ($method === 'PUT' && $id) $ctrl->update((int)$id);
                elseif ($method === 'DELETE' && $id) $ctrl->destroy((int)$id);
                break;
            case 'dashboard':
                require_once __DIR__ . '/app/controllers/DashboardController.php';
                $ctrl = new DashboardController();
                if ($method === 'GET') $ctrl->index();
                break;
            case 'purchase-orders':
                require_once __DIR__ . '/app/controllers/PurchaseOrderController.php';
                $ctrl = new PurchaseOrderController();
                if ($method === 'GET' && $id) $ctrl->show((int)$id);
                elseif ($method === 'GET') $ctrl->index();
                elseif ($method === 'POST') $ctrl->store();
                break;
            case 'reports':
                require_once __DIR__ . '/app/controllers/ReportsController.php';
                $ctrl = new ReportsController();
                if ($method === 'GET') $ctrl->index();
                break;
            default:
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'API endpoint not found', 'timestamp' => date('c')]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage(), 'timestamp' => date('c')]);
    }
    exit;
}

// Serve HTML files for non-API requests
// Root - serve login
if ($path === '/' || $path === '/index.php') {
    readfile('login.html');
    exit;
}

// Serve other HTML files
$htmlFiles = ['login.html', 'dashboard.html', 'products.html', 'inventory.html', 'sales.html', 'suppliers.html', 'purchase-orders.html', 'reports.html', 'settings.html'];

foreach ($htmlFiles as $file) {
    if (strpos($path, '/' . $file) !== false && file_exists($file)) {
        readfile($file);
        exit;
    }
}

// Serve static CSS/JS
$ext = pathinfo($path, PATHINFO_EXTENSION);
$file = ltrim($path, '/');

if (file_exists($file) && is_file($file)) {
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'html' => 'text/html',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon'
    ];
    header('Content-Type: ' . ($mimeTypes[$ext] ?? 'text/html'));
    readfile($file);
    exit;
}

// 404
http_response_code(404);
echo '<!DOCTYPE html><html><head><title>404</title></head><body><h1>404 - Not Found</h1></body></html>';