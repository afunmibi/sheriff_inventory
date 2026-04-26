<?php
/**
 * Sales API Handler
 */
ob_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../app/config/Config.php';
require_once __DIR__ . '/../app/config/DatabaseConnection.php';

Config::load();

try {
    $conn = DatabaseConnection::getConnection();
    
    // Auto-migration: Ensure new columns exist safely
    $colsResult = $conn->query("SHOW COLUMNS FROM sales_transactions");
    $existingCols = [];
    if ($colsResult) {
        while($c = $colsResult->fetch_assoc()) { $existingCols[] = $c['Field']; }
        
        if (!in_array('customer_phone', $existingCols)) { @$conn->query("ALTER TABLE sales_transactions ADD COLUMN customer_phone VARCHAR(20) AFTER customer_name"); }
        if (!in_array('serial_number', $existingCols)) { @$conn->query("ALTER TABLE sales_transactions ADD COLUMN serial_number VARCHAR(100) AFTER customer_phone"); }
        if (!in_array('vin', $existingCols)) { @$conn->query("ALTER TABLE sales_transactions ADD COLUMN vin VARCHAR(100) AFTER serial_number"); }
    }

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(200, max(1, (int)($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $sql = "SELECT s.*, p.product_name, p.sku, u.name AS cashier_name
                FROM sales_transactions s
                JOIN products p ON s.product_id = p.product_id
                LEFT JOIN users u ON s.cashier_id = u.user_id
                ORDER BY s.sale_date DESC, s.transaction_id DESC
                LIMIT ? OFFSET ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $sales = [];
        while ($row = $result->fetch_assoc()) {
            $sales[] = $row;
        }
        $stmt->close();

        $count = $conn->query("SELECT COUNT(*) AS total FROM sales_transactions");
        $total = (int)($count->fetch_assoc()['total'] ?? 0);

        if (ob_get_length()) ob_clean();
        echo json_encode([
            'success' => true,
            'data' => [
                'data' => $sales,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total
                ]
            ]
        ]);
        exit;
    }

    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];

        $productId = (int)($data['product_id'] ?? 0);
        $quantitySold = max(1, (int)($data['quantity_sold'] ?? 1));
        $unitPrice = (float)($data['unit_price'] ?? 0);
        $customerName = trim((string)($data['customer_name'] ?? ''));
        $notes = trim((string)($data['notes'] ?? ''));
        $paymentMethod = trim((string)($data['payment_method'] ?? 'cash'));

        $paymentMap = [
            'cash' => 'cash',
            'transfer' => 'bank_transfer',
            'bank_transfer' => 'bank_transfer',
            'card' => 'pos',
            'pos' => 'pos',
            'paystack' => 'paystack'
        ];
        $paymentMethod = $paymentMap[$paymentMethod] ?? 'cash';

        $customerPhone = trim((string)($data['customer_phone'] ?? ''));
        $serialNumber = trim((string)($data['serial_number'] ?? ''));
        $vin = trim((string)($data['vin'] ?? ''));

        if ($productId <= 0 || $unitPrice <= 0) {
            echo json_encode(['success' => false, 'message' => 'Product and valid unit price are required']);
            exit;
        }

        $conn->begin_transaction();

        try {
            $year = date('Y');
            $numberResult = $conn->query("SELECT MAX(CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED)) AS max_num
                                          FROM sales_transactions
                                          WHERE invoice_number LIKE 'INV-$year-%'");
            $numberRow = $numberResult->fetch_assoc();
            $nextNum = (int)($numberRow['max_num'] ?? 0) + 1;
            $invoiceNumber = sprintf("INV-%s-%04d", $year, $nextNum);
            
            $inventoryStmt = $conn->prepare("SELECT quantity_on_hand FROM inventory WHERE product_id = ? FOR UPDATE");
            $inventoryStmt->bind_param('i', $productId);
            $inventoryStmt->execute();
            $inventoryRow = $inventoryStmt->get_result()->fetch_assoc();
            $inventoryStmt->close();

            $currentStock = (int)($inventoryRow['quantity_on_hand'] ?? 0);
            if ($currentStock < $quantitySold) {
                throw new Exception('Insufficient stock available for this sale');
            }

            $insertSale = $conn->prepare("INSERT INTO sales_transactions
                                          (invoice_number, sale_date, sale_time, product_id, quantity_sold, unit_price, payment_method, payment_status, customer_name, customer_phone, serial_number, vin, notes)
                                          VALUES (?, CURDATE(), CURTIME(), ?, ?, ?, ?, 'completed', ?, ?, ?, ?, ?)");
            $insertSale->bind_param(
                'siidssssss',
                $invoiceNumber,
                $productId,
                $quantitySold,
                $unitPrice,
                $paymentMethod,
                $customerName,
                $customerPhone,
                $serialNumber,
                $vin,
                $notes
            );
            $insertSale->execute();
            $saleId = (int)$conn->insert_id;
            $insertSale->close();

            $newQuantity = $currentStock - $quantitySold;

            // Get reorder level for status calculation
            $reorderStmt = $conn->prepare("SELECT reorder_level FROM products WHERE product_id = ?");
            $reorderStmt->bind_param('i', $productId);
            $reorderStmt->execute();
            $reorderRow = $reorderStmt->get_result()->fetch_assoc();
            $reorderStmt->close();

            $reorderLevel = (int)($reorderRow['reorder_level'] ?? 10);
            $status = $newQuantity <= 0 ? 'out_of_stock' : ($newQuantity <= $reorderLevel ? 'low_stock' : 'in_stock');

            $updateInventory = $conn->prepare("UPDATE inventory
                                               SET quantity_on_hand = ?, 
                                                   status = ?, 
                                                   last_restock_date = CURDATE()
                                               WHERE product_id = ?");
            $updateInventory->bind_param('isi', $newQuantity, $status, $productId);
            $updateInventory->execute();
            $updateInventory->close();

            $conn->commit();

            if (ob_get_length()) ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Sale recorded',
                'data' => [
                    'sale_id' => $saleId,
                    'invoice_number' => $invoiceNumber,
                    'line_total' => round($quantitySold * $unitPrice, 2)
                ]
            ]);
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
