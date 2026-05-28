<?php
/**
 * SHERIFF SHEVVY ENTERPRISES
 * Single Entry Point - Front Controller
 */

// ── Bootstrap ───────────────────────────────────────────────
require_once __DIR__ . '/../bootstrap.php';

use Core\Router;
use Core\Config;

$router = new Router();

// ── API Routes ──────────────────────────────────────────────

// Auth (no auth middleware on login)
$router->post('/api/auth/login', ['Auth\AuthController', 'login']);

// Auth (authenticated)
$router->group('/api', ['Auth\AuthMiddleware::authenticated'], function ($r) {
    $r->post('/auth/logout', ['Auth\AuthController', 'logout']);
    $r->get('/auth/me', ['Auth\AuthController', 'me']);
});

// Inventory Module Routes
// IMPORTANT: Specific routes must come BEFORE parameterized routes
$router->group('/api', ['Auth\AuthMiddleware::authenticated'], function ($r) {

    // Products - specific routes first
    $r->get('/products/search', ['Inventory\ProductController', 'search']);
    $r->get('/products/stats', ['Inventory\ProductController', 'stats']);
    $r->get('/products/active', ['Inventory\ProductController', 'active']);
    $r->get('/products/category/{category}', ['Inventory\ProductController', 'byCategory']);
    // Products - parameterized routes
    $r->get('/products/{id}', ['Inventory\ProductController', 'show']);
    $r->post('/products', ['Inventory\ProductController', 'store']);
    $r->put('/products/{id}', ['Inventory\ProductController', 'update']);
    $r->delete('/products/{id}', ['Inventory\ProductController', 'destroy']);
    $r->post('/products/{id}/image', ['Inventory\ProductController', 'uploadImage']);

    // Inventory - specific routes first
    $r->get('/inventory/low-stock', ['Inventory\InventoryController', 'lowStock']);
    $r->get('/inventory/movement', ['Inventory\InventoryController', 'movement']);
    $r->post('/inventory/{id}/adjust', ['Inventory\InventoryController', 'adjust']);
    $r->get('/inventory/{id}', ['Inventory\InventoryController', 'show']);
    $r->get('/inventory', ['Inventory\InventoryController', 'index']);
    $r->put('/inventory/{id}', ['Inventory\InventoryController', 'update']);

    // Sales - specific routes first
    $r->get('/sales/daily', ['Inventory\SaleController', 'daily']);
    $r->get('/sales/monthly', ['Inventory\SaleController', 'monthly']);
    $r->get('/sales/recent', ['Inventory\SaleController', 'recent']);
    $r->get('/sales/top-products', ['Inventory\SaleController', 'topProducts']);
    $r->get('/sales/{id}', ['Inventory\SaleController', 'show']);
    $r->get('/sales', ['Inventory\SaleController', 'index']);
    $r->post('/sales', ['Inventory\SaleController', 'store']);

    // Returns
    $r->get('/returns', ['Inventory\ReturnController', 'index']);
    $r->post('/returns', ['Inventory\ReturnController', 'store']);

    // Categories
    $r->get('/categories', ['Inventory\CategoryController', 'index']);
    $r->post('/categories', ['Inventory\CategoryController', 'store']);
    $r->put('/categories/{id}', ['Inventory\CategoryController', 'update']);
    $r->delete('/categories/{id}', ['Inventory\CategoryController', 'destroy']);

    // Suppliers - specific routes first
    $r->get('/suppliers/search', ['Inventory\SupplierController', 'search']);
    $r->get('/suppliers/{id}/performance', ['Inventory\SupplierController', 'performance']);
    $r->get('/suppliers/{id}', ['Inventory\SupplierController', 'show']);
    $r->get('/suppliers', ['Inventory\SupplierController', 'index']);
    $r->post('/suppliers', ['Inventory\SupplierController', 'store']);
    $r->put('/suppliers/{id}', ['Inventory\SupplierController', 'update']);
    $r->delete('/suppliers/{id}', ['Inventory\SupplierController', 'destroy']);

    // Purchase Orders - specific routes first
    $r->get('/purchase-orders/pending', ['Inventory\PurchaseOrderController', 'pending']);
    $r->post('/purchase-orders/{id}/submit', ['Inventory\PurchaseOrderController', 'submit']);
    $r->post('/purchase-orders/{id}/approve', ['Inventory\PurchaseOrderController', 'approve']);
    $r->post('/purchase-orders/{id}/receive', ['Inventory\PurchaseOrderController', 'receive']);
    $r->get('/purchase-orders/{id}', ['Inventory\PurchaseOrderController', 'show']);
    $r->get('/purchase-orders', ['Inventory\PurchaseOrderController', 'index']);
    $r->post('/purchase-orders', ['Inventory\PurchaseOrderController', 'store']);
    $r->put('/purchase-orders/{id}', ['Inventory\PurchaseOrderController', 'update']);

    // Dashboard
    $r->get('/dashboard', ['Inventory\DashboardController', 'index']);
    $r->get('/dashboard/metrics', ['Inventory\DashboardController', 'metrics']);

    // Reports
    $r->get('/reports', ['Inventory\ReportsController', 'index']);
    $r->get('/reports/sales-summary', ['Inventory\ReportsController', 'salesSummary']);
    $r->get('/reports/export', ['Inventory\ReportsController', 'export']);

    // Settings
    $r->get('/settings', ['Inventory\SettingsController', 'index']);
    $r->post('/settings', ['Inventory\SettingsController', 'update']);

    // Users
    $r->get('/users', ['Inventory\UserController', 'index']);
    $r->post('/users', ['Inventory\UserController', 'store']);
    $r->put('/users/{id}', ['Inventory\UserController', 'update']);
});

// Ecommerce Module (no auth for storefront)
$router->get('/api/storefront/products', ['Ecommerce\StorefrontController', 'products']);
$router->get('/api/storefront/product/{uuid}', ['Ecommerce\StorefrontController', 'product']);
$router->post('/api/storefront/order', ['Ecommerce\StorefrontController', 'placeOrder']);
$router->post('/api/storefront/register-customer', ['Ecommerce\StorefrontController', 'registerCustomer']);
$router->post('/api/storefront/send-order-email', ['Ecommerce\StorefrontController', 'sendOrderEmail']);

// Ecommerce (authenticated)
$router->group('/api', ['Auth\AuthMiddleware::authenticated'], function ($r) {
    $r->get('/web-orders', ['Ecommerce\WebOrderController', 'index']);
});

// ── Backward-compat API routes ──────────────────────────────
// These routes map old `api/*.php` patterns to new controllers
// so existing HTML/JS continues to work without changes.

// The router will try these after new-style routes, so only unmatched
// requests with .php extension will hit these fallbacks.
$router->get('/api/login.php', function () {
    require_once __DIR__ . '/../api/login.php';
    return true;
});
$router->post('/api/login.php', function () {
    require_once __DIR__ . '/../api/login.php';
    return true;
});

// Migration route (development only)
$router->get('/api/migrate', function () {
    _denyProductionUtility();
    require __DIR__ . '/../api/migrate.php';
    return true;
});
$router->post('/api/migrate', function () {
    _denyProductionUtility();
    require __DIR__ . '/../api/migrate.php';
    return true;
});

$router->get('/api/customers.php', function () {
    Auth\AuthMiddleware::authenticated();
    require __DIR__ . '/../api/customers.php';
    return true;
});
$router->get('/api/reports/sales-summary.php', function () {
    Auth\AuthMiddleware::authenticated();
    require __DIR__ . '/../api/reports/sales-summary.php';
    return true;
});
$router->get('/api/web_order.php', function () {
    Auth\AuthMiddleware::authenticated();
    require __DIR__ . '/../api/web_order.php';
    return true;
});
$router->post('/api/web_order.php', function () {
    require __DIR__ . '/../api/web_order.php';
    return true;
});
$router->get('/api/hero_slides.php', function () {
    require __DIR__ . '/../api/hero_slides.php';
    return true;
});
$router->post('/api/hero_slides.php', function () {
    Auth\AuthMiddleware::authenticated();
    require __DIR__ . '/../api/hero_slides.php';
    return true;
});
$router->delete('/api/hero_slides.php', function () {
    Auth\AuthMiddleware::authenticated();
    require __DIR__ . '/../api/hero_slides.php';
    return true;
});
$router->get('/api/product_images.php', function () {
    require __DIR__ . '/../api/product_images.php';
    return true;
});
$router->post('/api/product_images.php', function () {
    Auth\AuthMiddleware::authenticated();
    require __DIR__ . '/../api/product_images.php';
    return true;
});
$router->delete('/api/product_images.php', function () {
    Auth\AuthMiddleware::authenticated();
    require __DIR__ . '/../api/product_images.php';
    return true;
});

// Generic backward compat handler for /api/*.php routes
// This catches things like api/products.php, api/categories.php, etc.
// and forwards to appropriate route handler
$router->get('/api/{file}.php', function (string $file) {
    return _handleLegacyApi($file, 'GET');
});
$router->post('/api/{file}.php', function (string $file) {
    return _handleLegacyApi($file, 'POST');
});
$router->put('/api/{file}.php', function (string $file) {
    return _handleLegacyApi($file, 'PUT');
});
$router->delete('/api/{file}.php', function (string $file) {
    return _handleLegacyApi($file, 'DELETE');
});

// ── Upload (no auth - handled by session) ────────────────────
$router->post('/api/upload', ['Inventory\ProductController', 'uploadImage']);

// ── Resolve URI (subdirectory-aware) ─────────────────────────
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestUri = '/' . trim(str_replace('\\', '/', $requestUri), '/');

// Strip subdirectory base path by comparing DOCUMENT_ROOT to app root
$docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$appRoot = str_replace('\\', '/', dirname(__DIR__));
$basePath = str_replace($docRoot, '', $appRoot);
if ($basePath && $basePath !== '/') {
    $requestUri = '/' . ltrim(str_replace($basePath, '', $requestUri), '/');
}
if ($requestUri === '' || $requestUri === '/index.php') $requestUri = '/';

_sendCorsHeaders();

// Handle OPTIONS preflight
if ($method === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');
    http_response_code(204);
    exit;
}

// Try API route dispatch
$result = $router->dispatch($method, $requestUri);

if ($result !== null) {
    exit;
}



// ── Serve Views ─────────────────────────────────────────────
$uriPath = $requestUri;

$viewMap = [
    '/'                       => '/views/ecommerce/landing.php',
    '/login'                  => '/views/auth/login.php',
    '/login.php'              => '/views/auth/login.php',
    '/gateway'                => '/views/shared/gateway.html',
    '/gateway.html'           => '/views/shared/gateway.html',
    '/dashboard'              => '/views/inventory/dashboard.html',
    '/dashboard.html'         => '/views/inventory/dashboard.html',
    '/ecommerce-dashboard'    => '/views/ecommerce/dashboard.html',
    '/ecommerce_dashboard.html' => '/views/ecommerce/dashboard.html',
    '/products'               => '/views/inventory/products.html',
    '/products.html'          => '/views/inventory/products.html',
    '/categories'             => '/views/inventory/categories.html',
    '/categories.html'        => '/views/inventory/categories.html',
    '/customers'              => '/views/inventory/customers.html',
    '/customers.html'         => '/views/inventory/customers.html',
    '/inventory'              => '/views/inventory/inventory.html',
    '/inventory.html'         => '/views/inventory/inventory.html',
    '/sales'                  => '/views/inventory/sales.html',
    '/sales.html'             => '/views/inventory/sales.html',
    '/returns'                => '/views/inventory/returns.html',
    '/returns.html'           => '/views/inventory/returns.html',
    '/suppliers'              => '/views/inventory/suppliers.html',
    '/suppliers.html'         => '/views/inventory/suppliers.html',
    '/purchase-orders'        => '/views/inventory/purchase-orders.html',
    '/purchase-orders.html'   => '/views/inventory/purchase-orders.html',
    '/reports'                => '/views/inventory/reports.html',
    '/reports.html'           => '/views/inventory/reports.html',
    '/settings'               => '/views/shared/settings.html',
    '/settings.html'          => '/views/shared/settings.html',
    '/store'                  => '/views/ecommerce/landing.php',
    '/landing.php'            => '/views/ecommerce/landing.php',
    '/store/orders'           => '/views/ecommerce/orders.html',
];

if (isset($viewMap[$uriPath])) {
    $viewFile = __DIR__ . $viewMap[$uriPath];
    if (file_exists($viewFile)) {
        if (str_ends_with($viewFile, '.php')) {
            include $viewFile;
        } else {
            readfile($viewFile);
        }
        exit;
    }
}

// Product detail URL support: /store/product/{uuid} and /product/{uuid}
if (preg_match('#^/(?:store/)?product/([a-f0-9\-]+)$#i', $uriPath, $m)) {
    $viewFile = __DIR__ . '/views/ecommerce/landing.php';
    if (file_exists($viewFile)) {
        $_GET['product'] = $m[1];
        include $viewFile;
        exit;
    }
}

// ── Serve Static Assets ────────────────────────────────────
function _serveFile(string $path): void {
    $mimeMap = [
        'css'  => 'text/css', 'js' => 'application/javascript',
        'html' => 'text/html', 'png' => 'image/png',
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif', 'svg' => 'image/svg+xml',
        'woff' => 'font/woff', 'woff2' => 'font/woff2',
        'ico' => 'image/x-icon', 'pdf' => 'application/pdf',
    ];
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    header('Content-Type: ' . ($mimeMap[$ext] ?? 'text/html'));
    readfile($path);
    exit;
}

function _sendCorsHeaders(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowedOrigins = Core\Config::get('app.allowed_origins', []);
    if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    }
}

if (preg_match('#/(?:\.|storage/|cache/|logs/|database/|src/|app/|legacy/)#i', $uriPath) || preg_match('#\.(?:env|sql|sqlite3?|bak|backup|log|ini|conf|md|php)$#i', $uriPath)) {
    http_response_code(404);
    exit;
}

// Try public/ directory first, then project root for uploads/images
$assetPath = __DIR__ . $uriPath;
if (file_exists($assetPath) && is_file($assetPath)) {
    _serveFile($assetPath);
}
$assetPath = dirname(__DIR__) . $uriPath;
if (file_exists($assetPath) && is_file($assetPath)) {
    _serveFile($assetPath);
}

// ── 404 ─────────────────────────────────────────────────────
http_response_code(404);
header('Content-Type: application/json');
echo json_encode([
    'success' => false, 'message' => 'Route not found',
    'path' => $uriPath, 'timestamp' => date('c'),
]);

// ── Legacy API Handler ──────────────────────────────────────
function _denyProductionUtility(): void {
    if (Core\Config::get('app.env') === 'production') {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Route not found', 'timestamp' => date('c')]);
        exit;
    }
}

function _handleLegacyApi(string $file, string $method): ?bool {
    // Map old script names to controller actions
    $map = [
        'products'       => ['Inventory\ProductController', ['GET' => 'index', 'POST' => 'store', 'PUT' => 'update', 'DELETE' => 'destroy']],
        'categories'     => ['Inventory\CategoryController', ['GET' => 'index', 'POST' => 'store', 'PUT' => 'update', 'DELETE' => 'destroy']],
        'inventory'      => ['Inventory\InventoryController', ['GET' => 'index', 'PUT' => 'update']],
        'sales'          => ['Inventory\SaleController', ['GET' => 'index', 'POST' => 'store']],
        'suppliers'      => ['Inventory\SupplierController', ['GET' => 'index', 'POST' => 'store', 'PUT' => 'update', 'DELETE' => 'destroy']],
        'purchase-orders'=> ['Inventory\PurchaseOrderController', ['GET' => 'index', 'POST' => 'store']],
        'dashboard'      => ['Inventory\DashboardController', ['GET' => 'index']],
        'settings'       => ['Inventory\SettingsController', ['GET' => 'index', 'POST' => 'update']],
        'users'          => ['Inventory\UserController', ['GET' => 'index', 'POST' => 'store']],
        'web_order'      => ['Ecommerce\WebOrderController', ['POST' => 'store']],
        'upload'         => ['Inventory\ProductController', ['POST' => 'uploadImage']],
    ];

    if (!isset($map[$file])) return null;

    [$class, $actions] = $map[$file];
    $action = $actions[$method] ?? null;
    if (!$action) return null;

    $controller = new $class();

    // For update/delete actions, extract ID from query params
    if (in_array($action, ['update', 'destroy', 'show'])) {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $id = (int)($input['product_id'] ?? $input['id'] ?? 0);
        }
        if ($id > 0) {
            $controller->$action($id);
            return true;
        }
    }

    if ($action === 'uploadImage') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $controller->$action($id);
            return true;
        }
    }

    $controller->$action();
    return true;
}
