<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../app/config/Config.php';
require_once __DIR__ . '/../app/config/DatabaseConnection.php';

Config::load();

try {
    $conn = DatabaseConnection::getConnection();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $phone = $_GET['phone'] ?? '';
        $search = $_GET['search'] ?? '';

        // Get unique customers with stats
        $where = "WHERE s.customer_name != '' AND s.customer_name IS NOT NULL";
        $params = [];
        $types = '';

        if ($phone) {
            $where .= " AND s.customer_phone = ?";
            $params[] = $phone;
            $types .= 's';
        }
        if ($search) {
            $where .= " AND (s.customer_name LIKE ? OR s.customer_phone LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ss';
        }

        // First get unique customers
        $sql = "SELECT 
                    s.customer_name,
                    s.customer_phone,
                    COUNT(DISTINCT s.invoice_number) AS total_orders,
                    SUM(s.quantity_sold * s.unit_price) AS total_spent,
                    MAX(s.sale_date) AS last_order_date,
                    MIN(s.sale_date) AS first_order_date
                FROM sales_transactions s
                $where
                GROUP BY s.customer_name, s.customer_phone
                ORDER BY last_order_date DESC
                LIMIT 200";

        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $customers = [];
        while ($row = $result->fetch_assoc()) {
            $customers[] = $row;
        }

        // If a specific customer is requested, get their orders
        $orders = [];
        if ($phone) {
            $oSql = "SELECT s.*, p.product_name, p.sku
                     FROM sales_transactions s
                     JOIN products p ON s.product_id = p.product_id
                     WHERE s.customer_phone = ?
                     ORDER BY s.sale_date DESC, s.sale_time DESC
                     LIMIT 100";
            $oStmt = $conn->prepare($oSql);
            $oStmt->bind_param('s', $phone);
            $oStmt->execute();
            $oResult = $oStmt->get_result();
            while ($row = $oResult->fetch_assoc()) {
                $orders[] = $row;
            }
        }

        echo json_encode([
            'success' => true,
            'data' => $customers,
            'orders' => $orders
        ]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
