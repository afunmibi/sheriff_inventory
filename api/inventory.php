<?php
/**
 * Inventory API Handler
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
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

    if (in_array($method, ['POST', 'PUT'], true)) {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];

        $productId = (int)($_GET['id'] ?? $input['product_id'] ?? 0);
        
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Valid product ID is required']);
            exit;
        }

        // Get product details for status auto-calculation
        $productStmt = $conn->prepare("SELECT reorder_level FROM products WHERE product_id = ?");
        $productStmt->bind_param('i', $productId);
        $productStmt->execute();
        $productRow = $productStmt->get_result()->fetch_assoc();
        $productStmt->close();

        if (!$productRow) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }

        $reorderLevel = (int)($productRow['reorder_level'] ?? 10);
        
        // Data to update
        $qtyOnHand = isset($input['quantity_on_hand']) ? (int)$input['quantity_on_hand'] : (isset($input['quantity']) ? (int)$input['quantity'] : 0);
        $qtyReserved = isset($input['quantity_reserved']) ? (int)$input['quantity_reserved'] : 0;
        $location = trim((string)($input['warehouse_location'] ?? 'Main Store'));
        $status = trim((string)($input['status'] ?? ''));

        if (empty($status)) {
            if ($qtyOnHand <= 0) $status = 'out_of_stock';
            else if ($qtyOnHand <= $reorderLevel) $status = 'low_stock';
            else $status = 'in_stock';
        }

        // Use REPLACE INTO or INSERT ... ON DUPLICATE KEY to ensure record exists
        $stmt = $conn->prepare(
            "INSERT INTO inventory (product_id, quantity_on_hand, quantity_reserved, warehouse_location, status, last_restock_date)
             VALUES (?, ?, ?, ?, ?, CURDATE())
             ON DUPLICATE KEY UPDATE
                quantity_on_hand = VALUES(quantity_on_hand),
                quantity_reserved = VALUES(quantity_reserved),
                warehouse_location = VALUES(warehouse_location),
                status = VALUES(status),
                last_restock_date = CURDATE()"
        );
        
        if (!$stmt) {
             throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param('iiiss', $productId, $qtyOnHand, $qtyReserved, $location, $status);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'Inventory updated successfully',
            'data' => [
                'product_id' => $productId,
                'quantity_on_hand' => $qtyOnHand,
                'status' => $status
            ]
        ]);
        exit;
    }

    // GET Request - List Inventory
    $sql = "SELECT p.product_id,
                   p.sku,
                   p.product_name,
                   p.category,
                   p.selling_price,
                   p.reorder_level,
                   COALESCE(i.quantity_on_hand, 0) as quantity_on_hand,
                   COALESCE(i.quantity_reserved, 0) as quantity_reserved,
                   COALESCE(i.quantity_available, 0) as quantity_available,
                   COALESCE(i.status, 'out_of_stock') as status,
                   COALESCE(i.warehouse_location, 'Main Store') as warehouse_location,
                   i.last_restock_date
            FROM products p
            LEFT JOIN inventory i ON p.product_id = i.product_id
            WHERE p.is_active = 1
            ORDER BY p.product_name ASC";

    $result = $conn->query($sql);
    $inventory = [];

    while ($row = $result->fetch_assoc()) {
        $inventory[] = $row;
    }

    $inStock = 0; $lowStock = 0; $outOfStock = 0;
    foreach($inventory as $item) {
        if ($item['status'] === 'in_stock') $inStock++;
        else if ($item['status'] === 'low_stock') $lowStock++;
        else $outOfStock++;
    }

    echo json_encode([
        'success' => true,
        'data' => $inventory,
        'stats' => [
            'in_stock' => $inStock,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
