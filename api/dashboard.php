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
    
    // Auto-migration: Ensure payment_date column exists for cash-basis reporting
    $colsResult = $conn->query("SHOW COLUMNS FROM sales_transactions");
    $existingCols = [];
    if ($colsResult) {
        while($c = $colsResult->fetch_assoc()) { $existingCols[] = $c['Field']; }
        if (!in_array('payment_date', $existingCols)) {
            @$conn->query("ALTER TABLE sales_transactions ADD COLUMN payment_date DATETIME AFTER payment_status");
            @$conn->query("UPDATE sales_transactions SET payment_date = created_at WHERE payment_status = 'completed'");
        }
    }
    
    // Total products
    $products = $conn->query("SELECT COUNT(*) as cnt FROM products WHERE is_active = 1");
    $totalProducts = $products ? ($products->fetch_assoc()['cnt'] ?? 0) : 0;
    
    // Total inventory value
    $invValue = $conn->query("SELECT SUM(p.cost_price * COALESCE(i.quantity_on_hand, 0)) as val 
                              FROM products p LEFT JOIN inventory i ON p.product_id = i.product_id 
                              WHERE p.is_active = 1");
    $totalValue = $invValue ? (float)($invValue->fetch_assoc()['val'] ?? 0) : 0;
    
    // 1. Today's sales stats (Revenue and Transaction Count)
    $salesStatsQuery = $conn->query("SELECT SUM(line_total) as revenue, COUNT(DISTINCT invoice_number) as count 
                                     FROM sales_transactions 
                                     WHERE (DATE(payment_date) = CURDATE() OR (payment_date IS NULL AND sale_date = CURDATE())) 
                                     AND payment_status = 'completed'");
    $stats = $salesStatsQuery ? $salesStatsQuery->fetch_assoc() : null;
    $todayRevenue = (float)($stats['revenue'] ?? 0);
    $todayCount = (int)($stats['count'] ?? 0);
    
    // Low stock count
    $lowStock = $conn->query("SELECT COUNT(*) as cnt FROM inventory i 
                             JOIN products p ON i.product_id = p.product_id 
                             WHERE p.is_active = 1 AND COALESCE(i.quantity_on_hand, 0) <= p.reorder_level");
    $lowStockCount = $lowStock ? ($lowStock->fetch_assoc()['cnt'] ?? 0) : 0;
    
    // Pending POs
    $pos = $conn->query("SELECT COUNT(*) as cnt FROM purchase_orders 
                        WHERE status IN ('draft', 'submitted', 'approved', 'pending')");
    $pendingPOs = $pos ? ($pos->fetch_assoc()['cnt'] ?? 0) : 0;
    
    // Recent sales
    $recent = $conn->query("SELECT s.*, p.product_name 
                          FROM sales_transactions s 
                          JOIN products p ON s.product_id = p.product_id 
                          ORDER BY s.transaction_id DESC LIMIT 5");
    $recentSales = [];
    if ($recent) {
        while ($row = $recent->fetch_assoc()) {
            $recentSales[] = $row;
        }
    }

    // Category count
    $catQ = $conn->query("SELECT COUNT(*) as cnt FROM categories");
    $totalCategories = $catQ ? ($catQ->fetch_assoc()['cnt'] ?? 0) : 0;

    // Active suppliers count
    $suppliersQ = $conn->query("SELECT COUNT(*) as cnt FROM suppliers WHERE status = 'active'");
    $totalSuppliers = $suppliersQ ? ($suppliersQ->fetch_assoc()['cnt'] ?? 0) : 0;

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
    if ($lowStockItemsResult) {
        while ($row = $lowStockItemsResult->fetch_assoc()) {
            $lowStockItems[] = $row;
        }
    }

    // Payment Breakdown for today
    $breakdown = $conn->query("SELECT payment_method, SUM(line_total) as total 
                                FROM sales_transactions 
                                WHERE (DATE(payment_date) = CURDATE() OR (payment_date IS NULL AND sale_date = CURDATE())) 
                                AND payment_status = 'completed'
                                GROUP BY payment_method");
    $paymentBreakdown = [
        'cash' => 0,
        'opay' => 0,
        'access' => 0,
        'moniepoint' => 0
    ];
    
    if ($breakdown) {
        while ($row = $breakdown->fetch_assoc()) {
            $method = strtolower($row['payment_method']);
            // Map common method names to display keys
            if (str_contains($method, 'cash')) $paymentBreakdown['cash'] += (float)$row['total'];
            elseif (str_contains($method, 'opay')) $paymentBreakdown['opay'] += (float)$row['total'];
            elseif (str_contains($method, 'access')) $paymentBreakdown['access'] += (float)$row['total'];
            elseif (str_contains($method, 'monie') || str_contains($method, 'point')) $paymentBreakdown['moniepoint'] += (float)$row['total'];
            // Default mappings for the keys themselves
            elseif (isset($paymentBreakdown[$method])) $paymentBreakdown[$method] += (float)$row['total'];
            // Fallback for bank transfer / pos if they aren't one of the specific ones above
            elseif ($method === 'bank_transfer' || $method === 'pos') {
                $paymentBreakdown['cash'] += (float)$row['total'];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_products' => (int)$totalProducts,
            'total_inventory_value' => (float)$totalValue,
            'today_sales' => $todayRevenue,
            'today_transactions' => $todayCount,
            'low_stock_count' => (int)$lowStockCount,
            'pending_pos' => (int)$pendingPOs,
            'total_categories' => (int)$totalCategories,
            'total_suppliers' => (int)$totalSuppliers,
            'recent_sales' => $recentSales,
            'low_stock_items' => $lowStockItems,
            'payment_breakdown' => $paymentBreakdown
        ]
    ]);
    
} catch (Exception $e) {
    if (!headers_sent()) header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
