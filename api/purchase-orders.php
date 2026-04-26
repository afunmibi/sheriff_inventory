<?php
/**
 * Purchase Orders API Handler
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
        $id = (int)($_GET['id'] ?? 0);

        if ($id > 0) {
            // Get single PO with items
            $poResult = $conn->query("SELECT po.*, s.company_name
                                      FROM purchase_orders po
                                      LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
                                      WHERE po.po_id = $id");
            $po = $poResult->fetch_assoc();

            if (!$po) {
                echo json_encode(['success' => false, 'message' => 'PO not found']);
                exit;
            }

            $itemsResult = $conn->query("SELECT poi.*, p.product_name, p.sku
                                         FROM purchase_order_items poi
                                         JOIN products p ON poi.product_id = p.product_id
                                         WHERE poi.po_id = $id");
            $items = [];
            while ($row = $itemsResult->fetch_assoc()) { $items[] = $row; }
            $po['items'] = $items;

            echo json_encode(['success' => true, 'data' => $po]);
            exit;
        }

        $result = $conn->query("SELECT po.*, s.company_name
                                FROM purchase_orders po
                                LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
                                ORDER BY po.po_date DESC, po.po_id DESC");
        $purchaseOrders = [];

        while ($row = $result->fetch_assoc()) {
            $purchaseOrders[] = $row;
        }

        echo json_encode(['success' => true, 'data' => ['data' => $purchaseOrders]]);
        exit;
    }

    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];

        $supplierId = (int)($data['supplier_id'] ?? 0);
        $expectedDeliveryDate = $data['expected_delivery_date'] ?? null;
        $notes = trim((string)($data['notes'] ?? ''));
        $items = $data['items'] ?? [];

        if ($supplierId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Supplier is required']);
            exit;
        }

        if (!is_array($items) || count($items) === 0) {
            echo json_encode(['success' => false, 'message' => 'At least one item is required']);
            exit;
        }

        $year = date('Y');
        $numberResult = $conn->query("SELECT MAX(CAST(SUBSTRING_INDEX(po_number, '-', -1) AS UNSIGNED)) as max_num
                                      FROM purchase_orders
                                      WHERE po_number LIKE 'PO-$year-%'");
        $numberRow = $numberResult->fetch_assoc();
        $nextNum = (int)($numberRow['max_num'] ?? 0) + 1;
        $poNumber = sprintf("PO-%s-%04d", $year, $nextNum);

        $conn->begin_transaction();

        try {
            $insertPo = $conn->prepare("INSERT INTO purchase_orders
                                        (po_number, supplier_id, po_date, expected_delivery_date, total_amount, status, notes)
                                        VALUES (?, ?, CURDATE(), ?, 0, 'pending', ?)");
            $insertPo->bind_param('siss', $poNumber, $supplierId, $expectedDeliveryDate, $notes);
            $insertPo->execute();
            $poId = (int)$conn->insert_id;
            $insertPo->close();

            $totalAmount = 0.0;
            $validItems = 0;

            $insertItem = $conn->prepare("INSERT INTO purchase_order_items
                                          (po_id, product_id, quantity_ordered, quantity_received, unit_cost, status)
                                          VALUES (?, ?, ?, 0, ?, 'pending')");

            foreach ($items as $item) {
                $productId = (int)($item['product_id'] ?? 0);
                $quantity = (int)($item['quantity'] ?? 0);
                $unitCost = (float)($item['unit_cost'] ?? 0);

                if ($productId <= 0 || $quantity <= 0) continue;

                $insertItem->bind_param('iiid', $poId, $productId, $quantity, $unitCost);
                $insertItem->execute();

                $totalAmount += $quantity * $unitCost;
                $validItems++;
            }

            $insertItem->close();

            if ($validItems === 0) throw new Exception('Please add at least one valid item');

            $updatePo = $conn->prepare("UPDATE purchase_orders SET total_amount = ? WHERE po_id = ?");
            $updatePo->bind_param('di', $totalAmount, $poId);
            $updatePo->execute();
            $updatePo->close();

            $conn->commit();

            echo json_encode(['success' => true, 'message' => 'Purchase order created', 'data' => ['po_id' => $poId, 'po_number' => $poNumber]]);
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    if ($method === 'PUT') {
        $id = (int)($_GET['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = $data['action'] ?? '';

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'PO ID required']);
            exit;
        }

        if ($action === 'receive') {
            $conn->begin_transaction();
            try {
                // Get PO items
                $itemsResult = $conn->query("SELECT product_id, quantity_ordered FROM purchase_order_items WHERE po_id = $id");
                
                while ($item = $itemsResult->fetch_assoc()) {
                    $pid = (int)$item['product_id'];
                    $qty = (int)$item['quantity_ordered'];

                    // Update Inventory
                    $conn->query("INSERT INTO inventory (product_id, quantity_on_hand, status, last_restock_date)
                                  VALUES ($pid, $qty, 'in_stock', CURDATE())
                                  ON DUPLICATE KEY UPDATE 
                                  quantity_on_hand = quantity_on_hand + $qty,
                                  status = 'in_stock',
                                  last_restock_date = CURDATE()");
                }

                // Update PO status
                $stmt = $conn->prepare("UPDATE purchase_orders SET status = 'received' WHERE po_id = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Inventory updated and PO marked as received']);
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
        }
    }

    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'PO ID required']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM purchase_orders WHERE po_id = ? AND status = 'pending'");
        $stmt->bind_param('i', $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Purchase order cancelled']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Cannot cancel PO (it may have been received already)']);
        }
        $stmt->close();
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
