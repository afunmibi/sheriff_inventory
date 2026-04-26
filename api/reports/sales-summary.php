<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../app/config/Config.php';
require_once __DIR__ . '/../../app/config/DatabaseConnection.php';

Config::load();

function fetchSalesSummary(mysqli $conn, string $startDate, string $endDate): array
{
    $stmt = $conn->prepare("SELECT
                                COALESCE(SUM(line_total), 0) AS total_revenue,
                                COUNT(*) AS transaction_count,
                                COALESCE(AVG(line_total), 0) AS average_sale,
                                COALESCE(SUM(quantity_sold), 0) AS total_items_sold
                            FROM sales_transactions
                            WHERE payment_status = 'completed'
                              AND sale_date BETWEEN ? AND ?");
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();

    return [
        'total_revenue' => (float)($row['total_revenue'] ?? 0),
        'transaction_count' => (int)($row['transaction_count'] ?? 0),
        'average_sale' => (float)($row['average_sale'] ?? 0),
        'total_items_sold' => (int)($row['total_items_sold'] ?? 0)
    ];
}

try {
    $conn = DatabaseConnection::getConnection();

    $today = date('Y-m-d');
    $weekStart = date('Y-m-d', strtotime('-6 days'));
    $monthStart = date('Y-m-01');
    $lastMonthStart = date('Y-m-01', strtotime('first day of last month'));
    $lastMonthEnd = date('Y-m-t', strtotime('last day of last month'));

    $daily = fetchSalesSummary($conn, $today, $today);
    $weekly = fetchSalesSummary($conn, $weekStart, $today);
    $monthly = fetchSalesSummary($conn, $monthStart, $today);
    $lastMonth = fetchSalesSummary($conn, $lastMonthStart, $lastMonthEnd);

    $trendStmt = $conn->prepare("SELECT
                                    sale_date,
                                    COALESCE(SUM(line_total), 0) AS revenue,
                                    COUNT(*) AS transaction_count,
                                    COALESCE(SUM(quantity_sold), 0) AS total_items_sold
                                 FROM sales_transactions
                                 WHERE payment_status = 'completed'
                                   AND sale_date BETWEEN ? AND ?
                                 GROUP BY sale_date
                                 ORDER BY sale_date ASC");
    $trendStart = date('Y-m-d', strtotime('-13 days'));
    $trendStmt->bind_param('ss', $trendStart, $today);
    $trendStmt->execute();
    $trendResult = $trendStmt->get_result();
    $trendRows = [];
    while ($row = $trendResult->fetch_assoc()) {
        $trendRows[$row['sale_date']] = [
            'date' => $row['sale_date'],
            'revenue' => (float)($row['revenue'] ?? 0),
            'transaction_count' => (int)($row['transaction_count'] ?? 0),
            'total_items_sold' => (int)($row['total_items_sold'] ?? 0)
        ];
    }
    $trendStmt->close();

    $trend = [];
    $cursor = strtotime($trendStart);
    $endCursor = strtotime($today);
    while ($cursor <= $endCursor) {
        $date = date('Y-m-d', $cursor);
        $trend[] = $trendRows[$date] ?? [
            'date' => $date,
            'revenue' => 0,
            'transaction_count' => 0,
            'total_items_sold' => 0
        ];
        $cursor = strtotime('+1 day', $cursor);
    }

    $paymentStmt = $conn->prepare("SELECT
                                      payment_method,
                                      COALESCE(SUM(line_total), 0) AS amount,
                                      COUNT(*) AS transaction_count
                                   FROM sales_transactions
                                   WHERE payment_status = 'completed'
                                     AND sale_date BETWEEN ? AND ?
                                   GROUP BY payment_method
                                   ORDER BY amount DESC");
    $paymentStmt->bind_param('ss', $monthStart, $today);
    $paymentStmt->execute();
    $paymentResult = $paymentStmt->get_result();
    $paymentBreakdown = [];
    while ($row = $paymentResult->fetch_assoc()) {
        $paymentBreakdown[] = [
            'payment_method' => $row['payment_method'],
            'amount' => (float)($row['amount'] ?? 0),
            'transaction_count' => (int)($row['transaction_count'] ?? 0)
        ];
    }
    $paymentStmt->close();

    $topProductStmt = $conn->prepare("SELECT
                                         p.product_name,
                                         p.sku,
                                         COALESCE(SUM(s.quantity_sold), 0) AS total_quantity,
                                         COALESCE(SUM(s.line_total), 0) AS total_revenue
                                      FROM sales_transactions s
                                      JOIN products p ON s.product_id = p.product_id
                                      WHERE s.payment_status = 'completed'
                                        AND s.sale_date BETWEEN ? AND ?
                                      GROUP BY p.product_id, p.product_name, p.sku
                                      ORDER BY total_revenue DESC
                                      LIMIT 5");
    $topProductStmt->bind_param('ss', $monthStart, $today);
    $topProductStmt->execute();
    $topProductResult = $topProductStmt->get_result();
    $topProducts = [];
    while ($row = $topProductResult->fetch_assoc()) {
        $topProducts[] = [
            'product_name' => $row['product_name'],
            'sku' => $row['sku'],
            'total_quantity' => (int)($row['total_quantity'] ?? 0),
            'total_revenue' => (float)($row['total_revenue'] ?? 0)
        ];
    }
    $topProductStmt->close();
    
    // Fetch all individual sales transactions for the current month
    $detailsStmt = $conn->prepare("SELECT 
                                     s.sale_date, 
                                     p.product_name, 
                                     s.quantity_sold, 
                                     s.unit_price, 
                                     s.line_total, 
                                     s.payment_method, 
                                     s.customer_name,
                                     s.invoice_number
                                   FROM sales_transactions s
                                   JOIN products p ON s.product_id = p.product_id
                                   WHERE s.payment_status = 'completed'
                                     AND s.sale_date BETWEEN ? AND ?
                                   ORDER BY s.sale_date DESC, s.transaction_id DESC");
    $detailsStmt->bind_param('ss', $monthStart, $today);
    $detailsStmt->execute();
    $detailsResult = $detailsStmt->get_result();
    $salesDetails = [];
    while ($row = $detailsResult->fetch_assoc()) {
        $salesDetails[] = $row;
    }
    $detailsStmt->close();

    $monthlyDifference = $monthly['total_revenue'] - $lastMonth['total_revenue'];
    $monthlyChangePercent = $lastMonth['total_revenue'] > 0
        ? round(($monthlyDifference / $lastMonth['total_revenue']) * 100, 2)
        : null;

    echo json_encode([
        'success' => true,
        'data' => [
            'generated_at' => date('c'),
            'periods' => [
                'daily' => $daily,
                'weekly' => $weekly,
                'monthly' => $monthly,
                'last_month' => $lastMonth
            ],
            'comparison' => [
                'monthly_difference' => round($monthlyDifference, 2),
                'monthly_change_percent' => $monthlyChangePercent
            ],
            'trend' => $trend,
            'payment_breakdown' => $paymentBreakdown,
            'top_products' => $topProducts,
            'sales_details' => $salesDetails
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
