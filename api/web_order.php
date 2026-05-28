<?php
/**
 * Web Order Handler
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../app/config/Config.php';
require_once __DIR__ . '/../app/config/DatabaseConnection.php';

Config::load();

$method = $_SERVER['REQUEST_METHOD'];

try {
    $conn = DatabaseConnection::getConnection();
    
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || empty($data['items'])) {
            echo json_encode(['success' => false, 'message' => 'No order data']);
            exit;
        }

        $invoice = 'WEB-' . date('YmdHis');
        $date = date('Y-m-d');
        $time = date('H:i:s');
        
        $conn->begin_transaction();

        foreach ($data['items'] as $item) {
            $stmt = $conn->prepare("INSERT INTO sales_transactions (
                invoice_number, sale_date, sale_time, customer_name, customer_phone, customer_address, customer_email,
                product_id, quantity_sold, unit_price, payment_method, payment_status, source
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $source = 'web';
            $qty = max(1, (int)($item['qty'] ?? $item['quantity_sold'] ?? 1));
            $pay_method = $data['payment_method'] ?? 'Pay on Delivery';
            $pay_status = 'pending';
            
            $stmt->bind_param('ssssssiddsss', 
                $invoice, $date, $time, $data['name'], $data['phone'], $data['address'], $data['email'] ?? '',
                $item['product_id'], $qty, $item['selling_price'], $pay_method, $pay_status, $source
            );
            $stmt->execute();

            // Deduct stock
            $pid = (int)$item['product_id'];
            $inv = $conn->query("SELECT quantity_on_hand, status FROM inventory WHERE product_id = $pid")->fetch_assoc();
            if ($inv) {
                $newQty = max(0, (int)$inv['quantity_on_hand'] - $qty);
                $reorder = $conn->query("SELECT reorder_level FROM products WHERE product_id = $pid")->fetch_assoc();
                $reorderLevel = (int)($reorder['reorder_level'] ?? 5);
                $status = $newQty <= 0 ? 'out_of_stock' : ($newQty <= $reorderLevel ? 'low_stock' : 'in_stock');
                $conn->query("UPDATE inventory SET quantity_on_hand = $newQty, status = '$status' WHERE product_id = $pid");
            }
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Order saved', 'invoice' => $invoice]);
        exit;
    }

    if ($method === 'GET') {
        $sql = "SELECT * FROM sales_transactions WHERE source = 'web' ORDER BY created_at DESC LIMIT 50";
        $result = $conn->query($sql);
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $orders]);
        exit;
    }

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
