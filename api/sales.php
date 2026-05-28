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
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // Auto-migration: only run on GET to avoid DDL locks during writes
    if ($method === 'GET') {
        $colsResult = $conn->query("SHOW COLUMNS FROM sales_transactions");
        $existingCols = [];
        if ($colsResult) {
            while($c = $colsResult->fetch_assoc()) { $existingCols[] = $c['Field']; }
            if (!in_array('customer_phone', $existingCols)) { @$conn->query("ALTER TABLE sales_transactions ADD COLUMN customer_phone VARCHAR(20) AFTER customer_name"); }
            if (!in_array('serial_number', $existingCols)) { @$conn->query("ALTER TABLE sales_transactions ADD COLUMN serial_number VARCHAR(100) AFTER customer_phone"); }
            if (!in_array('vin', $existingCols)) { @$conn->query("ALTER TABLE sales_transactions ADD COLUMN vin VARCHAR(100) AFTER serial_number"); }
        }
        $indexResult = $conn->query("SHOW INDEX FROM sales_transactions WHERE Column_name = 'invoice_number' AND Non_unique = 0");
        if ($indexResult && $indexResult->num_rows > 0) {
            $row = $indexResult->fetch_assoc();
            $keyName = $row['Key_name'];
            @$conn->query("ALTER TABLE sales_transactions DROP INDEX `$keyName` ");
            @$conn->query("ALTER TABLE sales_transactions ADD INDEX idx_invoice_number (invoice_number)");
        }
        
        $invoice = $_GET['invoice'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(500, max(1, (int)($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        if ($invoice) {
            $sql = "SELECT s.*, p.product_name, p.sku, u.name AS cashier_name
                    FROM sales_transactions s
                    JOIN products p ON s.product_id = p.product_id
                    LEFT JOIN users u ON s.cashier_id = u.user_id
                    WHERE s.invoice_number = ?
                    ORDER BY s.transaction_id ASC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $invoice);
        } else {
            $sql = "SELECT s.*, p.product_name, p.sku, u.name AS cashier_name
                    FROM sales_transactions s
                    JOIN products p ON s.product_id = p.product_id
                    LEFT JOIN users u ON s.cashier_id = u.user_id
                    ORDER BY s.sale_date DESC, s.transaction_id DESC
                    LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $limit, $offset);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $sales = [];
        while ($row = $result->fetch_assoc()) {
            $sales[] = $row;
        }
        $stmt->close();

        $countSql = $invoice ? "SELECT COUNT(*) AS total FROM sales_transactions WHERE invoice_number = ?" : "SELECT COUNT(*) AS total FROM sales_transactions";
        $countStmt = $conn->prepare($countSql);
        if ($invoice) $countStmt->bind_param('s', $invoice);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $total = (int)($countResult->fetch_assoc()['total'] ?? 0);
        $countStmt->close();

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

        // Check if we are receiving an array of items or a single item
        $items = [];
        if (isset($data['items']) && is_array($data['items'])) {
            $items = $data['items'];
        } else {
            // Convert single item to array format for unified processing
            $items[] = [
                'product_id' => $data['product_id'] ?? 0,
                'quantity_sold' => $data['quantity_sold'] ?? 1,
                'unit_price' => $data['unit_price'] ?? 0,
                'serial_number' => $data['serial_number'] ?? '',
                'vin' => $data['vin'] ?? ''
            ];
        }

        $customerName = trim((string)($data['customer_name'] ?? ''));
        $notes = trim((string)($data['notes'] ?? ''));
        $paymentMethod = trim((string)($data['payment_method'] ?? 'cash'));
        $paymentStatus = trim((string)($data['payment_status'] ?? 'completed'));
        $customerPhone = trim((string)($data['customer_phone'] ?? ''));

        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'No items provided']);
            exit;
        }

        $conn = DatabaseConnection::getConnection();
        $conn->begin_transaction();

        try {
            // Generate ONE invoice number for all items
            $year = date('Y');
            $numberResult = $conn->query("SELECT MAX(CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED)) AS max_num
                                          FROM sales_transactions
                                          WHERE invoice_number LIKE 'INV-$year-%'");
            $numberRow = $numberResult->fetch_assoc();
            $nextNum = (int)($numberRow['max_num'] ?? 0) + 1;
            $invoiceNumber = sprintf("INV-%s-%04d", $year, $nextNum);
            
            $totalAmount = 0;
            $saleIds = [];

            foreach ($items as $item) {
                $productId = (int)($item['product_id'] ?? 0);
                $quantitySold = max(1, (int)($item['quantity_sold'] ?? 1));
                $unitPrice = (float)($item['unit_price'] ?? 0);
                $sn = trim((string)($item['serial_number'] ?? ''));
                $v = trim((string)($item['vin'] ?? ''));

                if ($productId <= 0) continue;

                // Check stock
                $inventoryStmt = $conn->prepare("SELECT quantity_on_hand FROM inventory WHERE product_id = ? FOR UPDATE");
                $inventoryStmt->bind_param('i', $productId);
                $inventoryStmt->execute();
                $inventoryRow = $inventoryStmt->get_result()->fetch_assoc();
                $inventoryStmt->close();

                $currentStock = (int)($inventoryRow['quantity_on_hand'] ?? 0);
                if ($paymentStatus !== 'returned' && $currentStock < $quantitySold) {
                    throw new Exception("Insufficient stock for product ID $productId");
                }

                $paymentDate = ($paymentStatus === 'completed') ? date('Y-m-d H:i:s') : (($paymentStatus === 'returned') ? date('Y-m-d H:i:s') : null);
                
                $insertSale = $conn->prepare("INSERT INTO sales_transactions
                                              (invoice_number, sale_date, sale_time, product_id, quantity_sold, unit_price, payment_method, payment_status, payment_date, customer_name, customer_phone, serial_number, vin, notes)
                                              VALUES (?, CURDATE(), CURTIME(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insertSale->bind_param(
                    'siidssssssss',
                    $invoiceNumber,
                    $productId,
                    $quantitySold,
                    $unitPrice,
                    $paymentMethod,
                    $paymentStatus,
                    $paymentDate,
                    $customerName,
                    $customerPhone,
                    $sn,
                    $v,
                    $notes
                );
                $insertSale->execute();
                $saleIds[] = (int)$conn->insert_id;
                $insertSale->close();

                if ($paymentStatus !== 'returned') {
                    $newQuantity = $currentStock - $quantitySold;

                    // Get reorder level
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
                }
                
                $totalAmount += ($quantitySold * $unitPrice);
            }

            $conn->commit();

            if (ob_get_length()) ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Sale recorded',
                'data' => [
                    'invoice_number' => $invoiceNumber,
                    'total_amount' => round($totalAmount, 2),
                    'sale_ids' => $saleIds
                ]
            ]);
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    if ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        $transactionId = (int)($_GET['id'] ?? $input['transaction_id'] ?? 0);
        $status = $input['status'] ?? '';

        if ($transactionId <= 0 || empty($status)) {
            echo json_encode(['success' => false, 'message' => 'Transaction ID and Status required']);
            exit;
        }

        if ($status === 'returned') {
            $conn = DatabaseConnection::getConnection();
            $conn->begin_transaction();
            try {
                // Get transaction details
                $saleStmt = $conn->prepare("SELECT product_id, quantity_sold, payment_status FROM sales_transactions WHERE transaction_id = ? FOR UPDATE");
                $saleStmt->bind_param('i', $transactionId);
                $saleStmt->execute();
                $sale = $saleStmt->get_result()->fetch_assoc();
                $saleStmt->close();

                if (!$sale) {
                    throw new Exception('Transaction not found');
                }

                if ($sale['payment_status'] === 'returned') {
                    throw new Exception('Sale already marked as returned');
                }

                $productId = $sale['product_id'];
                $qty = $sale['quantity_sold'];

                // Update sale status
                $updateSale = $conn->prepare("UPDATE sales_transactions SET payment_status = 'returned' WHERE transaction_id = ?");
                $updateSale->bind_param('i', $transactionId);
                $updateSale->execute();
                $updateSale->close();

                // Increment inventory
                $getInv = $conn->prepare("SELECT quantity_on_hand FROM inventory WHERE product_id = ? FOR UPDATE");
                $getInv->bind_param('i', $productId);
                $getInv->execute();
                $inv = $getInv->get_result()->fetch_assoc();
                $getInv->close();

                $newQty = ($inv['quantity_on_hand'] ?? 0) + $qty;

                // Update inventory status
                $reorderStmt = $conn->prepare("SELECT reorder_level FROM products WHERE product_id = ?");
                $reorderStmt->bind_param('i', $productId);
                $reorderStmt->execute();
                $reorder = $reorderStmt->get_result()->fetch_assoc();
                $reorderStmt->close();

                $reorderLevel = (int)($reorder['reorder_level'] ?? 10);
                $invStatus = $newQty <= 0 ? 'out_of_stock' : ($newQty <= $reorderLevel ? 'low_stock' : 'in_stock');

                $updateInv = $conn->prepare("UPDATE inventory SET quantity_on_hand = ?, status = ? WHERE product_id = ?");
                $updateInv->bind_param('isi', $newQty, $invStatus, $productId);
                $updateInv->execute();
                $updateInv->close();

                // Add stock adjustment log
                $adjStmt = $conn->prepare("INSERT INTO stock_adjustments (product_id, adjustment_type, quantity_adjusted, quantity_before, quantity_after, reason, adjustment_date, approval_status) VALUES (?, 'return', ?, ?, ?, ?, CURDATE(), 'approved')");
                $reason = "Sale returned (Invoice reference: $transactionId)";
                $qty_before = $inv['quantity_on_hand'] ?? 0;
                $adjStmt->bind_param('iiiis', $productId, $qty, $qty_before, $newQty, $reason);
                $adjStmt->execute();
                $adjStmt->close();

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Sale marked as returned and inventory updated']);
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            exit;
        }

        // Use NOW() for payment_date if status is being set to completed
        $stmt = $conn->prepare("UPDATE sales_transactions SET payment_status = ?, payment_date = CASE WHEN ? = 'completed' THEN NOW() ELSE payment_date END WHERE transaction_id = ?");
        $stmt->bind_param('ssi', $status, $status, $transactionId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            throw new Exception($stmt->error);
        }
        $stmt->close();
        exit;
    }
    if ($method === 'DELETE') {
        $transactionId = (int)($_GET['id'] ?? 0);
        if ($transactionId <= 0) {
            $input = json_decode(file_get_contents('php://input'), true);
            $transactionId = (int)($input['transaction_id'] ?? 0);
        }
        
        if ($transactionId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
            exit;
        }

        $conn = DatabaseConnection::getConnection();
        $conn->begin_transaction();
        try {
            // Get transaction details first
            $saleStmt = $conn->prepare("SELECT product_id, quantity_sold, payment_status, invoice_number FROM sales_transactions WHERE transaction_id = ? FOR UPDATE");
            $saleStmt->bind_param('i', $transactionId);
            $saleStmt->execute();
            $sale = $saleStmt->get_result()->fetch_assoc();
            $saleStmt->close();

            if (!$sale) {
                throw new Exception('Transaction not found');
            }

            $productId = $sale['product_id'];
            $qty = $sale['quantity_sold'];
            $invNum = $sale['invoice_number'];

            // 1. Return stock to inventory (if it wasn't already marked as returned)
            if ($sale['payment_status'] !== 'returned') {
                $getInv = $conn->prepare("SELECT quantity_on_hand FROM inventory WHERE product_id = ? FOR UPDATE");
                $getInv->bind_param('i', $productId);
                $getInv->execute();
                $inv = $getInv->get_result()->fetch_assoc();
                $getInv->close();

                $currentQty = (int)($inv['quantity_on_hand'] ?? 0);
                $newQty = $currentQty + $qty;

                // Get reorder level
                $reorderStmt = $conn->prepare("SELECT reorder_level FROM products WHERE product_id = ?");
                $reorderStmt->bind_param('i', $productId);
                $reorderStmt->execute();
                $reorderRow = $reorderStmt->get_result()->fetch_assoc();
                $reorderStmt->close();
                $reorderLevel = (int)($reorderRow['reorder_level'] ?? 10);
                $invStatus = $newQty <= 0 ? 'out_of_stock' : ($newQty <= $reorderLevel ? 'low_stock' : 'in_stock');

                $updateInv = $conn->prepare("UPDATE inventory SET quantity_on_hand = ?, status = ? WHERE product_id = ?");
                $updateInv->bind_param('isi', $newQty, $invStatus, $productId);
                $updateInv->execute();
                $updateInv->close();

                // Log adjustment
                $adjStmt = $conn->prepare("INSERT INTO stock_adjustments (product_id, adjustment_type, quantity_adjusted, quantity_before, quantity_after, reason, adjustment_date, approval_status) VALUES (?, 'return', ?, ?, ?, ?, CURDATE(), 'approved')");
                $reason = "Sale Voided/Deleted ($invNum)";
                $adjStmt->bind_param('iiiis', $productId, $qty, $currentQty, $newQty, $reason);
                $adjStmt->execute();
                $adjStmt->close();
            }

            // 2. Instead of a hard delete, mark as 'returned' to preserve the record for the Returns Log
            $updateStmt = $conn->prepare("UPDATE sales_transactions 
                                         SET payment_status = 'returned', 
                                             notes = CONCAT(COALESCE(notes, ''), ' [VOIDED/RETURNED]') 
                                         WHERE transaction_id = ?");
            $updateStmt->bind_param('i', $transactionId);
            $updateStmt->execute();
            $updateStmt->close();

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Transaction marked as returned and items added back to stock']);
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        exit;
    }

    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
