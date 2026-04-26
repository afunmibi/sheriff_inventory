<?php
/**
 * Products API Handler
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../app/config/Config.php';
require_once __DIR__ . '/../app/config/DatabaseConnection.php';

Config::load();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $conn = DatabaseConnection::getConnection();

    if ($method === 'GET') {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(200, max(1, (int)($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;
        $search = trim((string)($_GET['search'] ?? ''));
        $category = trim((string)($_GET['category'] ?? ''));
        $stockStatus = trim((string)($_GET['stock_status'] ?? ''));

        $where = ["p.is_active = 1"];
        $types = '';
        $params = [];

        if ($category !== '') {
            $where[] = "p.category = ?";
            $types .= 's';
            $params[] = $category;
        }

        if ($stockStatus !== '') {
            $where[] = "COALESCE(i.status, 'out_of_stock') = ?";
            $types .= 's';
            $params[] = $stockStatus;
        }

        if ($search !== '') {
            $where[] = "(p.product_name LIKE ? OR p.sku LIKE ? OR COALESCE(p.description, '') LIKE ?)";
            $like = '%' . $search . '%';
            $types .= 'sss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT p.*,
                       COALESCE(i.quantity_on_hand, 0) AS quantity_on_hand,
                       COALESCE(i.quantity_available, 0) AS quantity_available,
                       COALESCE(i.status, 'out_of_stock') AS status
                FROM products p
                LEFT JOIN inventory i ON p.product_id = i.product_id
                WHERE $whereSql
                ORDER BY p.product_name ASC
                LIMIT ? OFFSET ?";

        $stmt = $conn->prepare($sql);
        $queryTypes = $types . 'ii';
        $queryParams = array_merge($params, [$limit, $offset]);
        $stmt->bind_param($queryTypes, ...$queryParams);
        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        $stmt->close();

        $countSql = "SELECT COUNT(*) AS total
                     FROM products p
                     LEFT JOIN inventory i ON p.product_id = i.product_id
                     WHERE $whereSql";
        $countStmt = $conn->prepare($countSql);

        if ($types !== '') {
            $countStmt->bind_param($types, ...$params);
        }

        $countStmt->execute();
        $countResult = $countStmt->get_result()->fetch_assoc();
        $countStmt->close();

        $total = (int)($countResult['total'] ?? 0);

        echo json_encode([
            'success' => true,
            'data' => [
                'data' => $products,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => max(1, (int)ceil($total / $limit))
                ]
            ]
        ]);
        exit;
    }

    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];

        $productName = trim((string)($data['product_name'] ?? ''));
        $category = trim((string)($data['category'] ?? ''));
        $sellingPrice = (float)($data['selling_price'] ?? 0);
        $costPrice = (float)($data['cost_price'] ?? 0);
        $sku = trim((string)($data['sku'] ?? ''));
        $subcategory = trim((string)($data['subcategory'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));
        $reorderLevel = max(0, (int)($data['reorder_level'] ?? 10));
        $unit = trim((string)($data['unit_of_measurement'] ?? 'pieces'));

        if ($productName === '' || $category === '') {
            echo json_encode(['success' => false, 'message' => 'Product name and category are required']);
            exit;
        }

        if ($sku === '') {
            $prefix = strtoupper(substr($category, 0, 3));
            $year = date('Y');
            $numberResult = $conn->query("SELECT MAX(CAST(SUBSTRING_INDEX(sku, '-', -1) AS UNSIGNED)) AS max_num
                                          FROM products
                                          WHERE sku LIKE '{$prefix}-{$year}-%'");
            $numberRow = $numberResult->fetch_assoc();
            $nextNum = (int)($numberRow['max_num'] ?? 0) + 1;
            $sku = sprintf("%s-%s-%04d", $prefix, $year, $nextNum);
        }

        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );

        $stmt = $conn->prepare("INSERT INTO products
                                (uuid, sku, product_name, category, subcategory, description, selling_price, cost_price, reorder_level, unit_of_measurement, is_active)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param(
            'ssssssddis',
            $uuid,
            $sku,
            $productName,
            $category,
            $subcategory,
            $description,
            $sellingPrice,
            $costPrice,
            $reorderLevel,
            $unit
        );
        $stmt->execute();
        $productId = (int)$conn->insert_id;
        $stmt->close();

        // Initialize inventory record for the new product
        $qty = (int)($data['quantity_on_hand'] ?? 0);
        $status = ($qty <= 0) ? 'out_of_stock' : (($qty <= $reorderLevel) ? 'low_stock' : 'in_stock');
        
        $invStmt = $conn->prepare("INSERT INTO inventory (product_id, quantity_on_hand, status) VALUES (?, ?, ?)");
        $invStmt->bind_param('iis', $productId, $qty, $status);
        $invStmt->execute();
        $invStmt->close();

        echo json_encode(['success' => true, 'message' => 'Product created', 'data' => ['product_id' => $productId]]);
        exit;
    }

    if ($method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $productId = (int)($_GET['id'] ?? $data['product_id'] ?? $data['id'] ?? 0);

        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Product ID is required']);
            exit;
        }

        $productName = trim((string)($data['product_name'] ?? ''));
        $category = trim((string)($data['category'] ?? ''));
        $sellingPrice = (float)($data['selling_price'] ?? 0);
        $costPrice = (float)($data['cost_price'] ?? 0);
        $sku = trim((string)($data['sku'] ?? ''));
        $subcategory = trim((string)($data['subcategory'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));
        $reorderLevel = max(0, (int)($data['reorder_level'] ?? 10));
        $unit = trim((string)($data['unit_of_measurement'] ?? 'pieces'));

        if ($productName === '' || $category === '') {
            echo json_encode(['success' => false, 'message' => 'Product name and category are required']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE products
                                SET sku = ?,
                                    product_name = ?,
                                    category = ?,
                                    subcategory = ?,
                                    description = ?,
                                    selling_price = ?,
                                    cost_price = ?,
                                    reorder_level = ?,
                                    unit_of_measurement = ?
                                WHERE product_id = ?");
        $stmt->bind_param(
            'sssssddisi',
            $sku,
            $productName,
            $category,
            $subcategory,
            $description,
            $sellingPrice,
            $costPrice,
            $reorderLevel,
            $unit,
            $productId
        );
        $stmt->execute();
        $stmt->close();

        // Update inventory record
        if (isset($data['quantity_on_hand'])) {
            $qty = (int)$data['quantity_on_hand'];
            $status = ($qty <= 0) ? 'out_of_stock' : (($qty <= $reorderLevel) ? 'low_stock' : 'in_stock');
            
            $invStmt = $conn->prepare("INSERT INTO inventory (product_id, quantity_on_hand, status) 
                                     VALUES (?, ?, ?) 
                                     ON DUPLICATE KEY UPDATE quantity_on_hand = ?, status = ?");
            $invStmt->bind_param('iisis', $productId, $qty, $status, $qty, $status);
            $invStmt->execute();
            $invStmt->close();
        }

        echo json_encode(['success' => true, 'message' => 'Product updated']);
        exit;
    }

    if ($method === 'DELETE') {
        $productId = (int)($_GET['id'] ?? 0);

        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Product ID is required']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE products SET is_active = 0 WHERE product_id = ?");
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true, 'message' => 'Product deleted']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
