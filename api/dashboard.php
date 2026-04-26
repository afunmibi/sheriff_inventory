<?php
/**
 * Dashboard API Handler
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../app/config/Config.php';
require_once __DIR__ . '/../app/config/DatabaseConnection.php';

Config::load();

try {
    $conn = DatabaseConnection::getConnection();
    
    // Total products
    $products = $conn->query("SELECT COUNT(*) as cnt FROM products WHERE is_active = 1");
    $totalProducts = $products->fetch_assoc()['cnt'] ?? 0;
    
    // Total inventory value
    $invValue = $conn->query("SELECT SUM(p.cost_price * COALESCE(i.quantity_on_hand, 0)) as val 
                              FROM products p LEFT JOIN inventory i ON p.product_id = i.product_id 
                              WHERE p.is_active = 1");
    $totalValue = $invValue->fetch_assoc()['val'] ?? 0;
    
    // Today's sales
    $today = date('Y-m-d');
    $sales = $conn->query("SELECT SUM(line_total) as revenue, COUNT(*) as count 
                          FROM sales_transactions 
                          WHERE sale_date = '$today' AND payment_status = 'completed'");
    $todaySales = $sales->fetch_assoc();
    
    // Low stock count
    $lowStock = $conn->query("SELECT COUNT(*) as cnt FROM inventory i 
                             JOIN products p ON i.product_id = p.product_id 
                             WHERE p.is_active = 1 AND COALESCE(i.quantity_on_hand, 0) <= p.reorder_level");
    $lowStockCount = $lowStock->fetch_assoc()['cnt'] ?? 0;
    
    // Pending POs
    $pos = $conn->query("SELECT COUNT(*) as cnt FROM purchase_orders 
                        WHERE status IN ('draft', 'submitted', 'approved')");
    $pendingPOs = $pos->fetch_assoc()['cnt'] ?? 0;
    
    // Recent sales
    $recent = $conn->query("SELECT s.*, p.product_name 
                          FROM sales_transactions s 
                          JOIN products p ON s.product_id = p.product_id 
                          ORDER BY s.transaction_id DESC LIMIT 5");
    $recentSales = [];
    while ($row = $recent->fetch_assoc()) {
        $recentSales[] = $row;
    }

    // Low stock items
    $lowStockItemsResult = $conn->query("SELECT 
                                            p.sku,
                                            p.product_name,
                                            p.reorder_level,
                                            COALESCE(i.quantity_on_hand, 0) AS current_stock
                                         FROM products p
                                         LEFT JOIN inventory i ON p.product_id = i.product_id
                                         WHERE p.is_active = 1
                                           AND COALESCE(i.quantity_on_hand, 0) <= p.reorder_level
                                         ORDER BY current_stock ASC, p.product_name ASC
                                         LIMIT 5");
    $lowStockItems = [];
    while ($row = $lowStockItemsResult->fetch_assoc()) {
        $lowStockItems[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_products' => (int)$totalProducts,
            'total_inventory_value' => (float)$totalValue,
            'today_sales' => (float)($todaySales['revenue'] ?? 0),
            'today_transactions' => (int)($todaySales['count'] ?? 0),
            'low_stock_count' => (int)$lowStockCount,
            'pending_pos' => (int)$pendingPOs,
            'recent_sales' => $recentSales,
            'low_stock_items' => $lowStockItems
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
