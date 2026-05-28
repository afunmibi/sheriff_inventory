<?php
/**
 * Reports Controller
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Inventory.php';

class ReportsController extends BaseController {
    private Sale $saleModel;
    private Product $productModel;
    private Inventory $inventoryModel;

    public function __construct() {
        parent::__construct();
        $this->saleModel = new Sale();
        $this->productModel = new Product();
        $this->inventoryModel = new Inventory();
    }

    public function index(): void {
        $this->requireAuth();
        $type = $this->getInput('type', 'sales');
        
        switch ($type) {
            case 'sales':
                $this->salesReport();
                break;
            case 'inventory':
                $this->inventoryReport();
                break;
            case 'financial':
                $this->financialReport();
                break;
            default:
                $this->salesReport();
        }
    }

    private function salesReport(): void {
        $startDate = $this->getInput('start_date', date('Y-m-01'));
        $endDate = $this->getInput('end_date', date('Y-m-t'));
        
        $daily = [];
        $current = strtotime($startDate);
        $end = strtotime($endDate);
        
        while ($current <= $end) {
            $date = date('Y-m-d', $current);
            $sales = $this->saleModel->getDailySalesSummary($date);
            $daily[] = [
                'date' => $date,
                'revenue' => $sales['total_revenue'] ?? 0,
                'transactions' => $sales['transaction_count'] ?? 0
            ];
            $current = strtotime('+1 day', $current);
        }
        
        $topProducts = $this->saleModel->getTopProducts(30, 10);
        
        $this->successResponse('Sales report', [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'daily' => $daily,
            'top_products' => $topProducts,
            'summary' => [
                'total_revenue' => array_sum(array_column($daily, 'revenue')),
                'transaction_count' => array_sum(array_column($daily, 'transactions'))
            ]
        ]);
    }

    private function inventoryReport(): void {
        $inventory = $this->inventoryModel->getAllInventory();
        $lowStock = $this->inventoryModel->getLowStockAlerts();
        
        $inStock = count(array_filter($inventory, fn($i) => ($i['status'] ?? '') === 'in_stock'));
        $lowStockCount = count(array_filter($inventory, fn($i) => ($i['status'] ?? '') === 'low_stock'));
        $outStock = count(array_filter($inventory, fn($i) => ($i['status'] ?? '') === 'out_of_stock'));
        
        $this->successResponse('Inventory report', [
            'total_products' => count($inventory),
            'in_stock' => $inStock,
            'low_stock' => $lowStockCount,
            'out_of_stock' => $outStock,
            'low_stock_items' => $lowStock,
            'total_value' => $this->productModel->getTotalInventoryValue()
        ]);
    }

    private function financialReport(): void {
        $startDate = $this->getInput('start_date', date('Y-m-01'));
        $endDate = $this->getInput('end_date', date('Y-m-t'));
        
        $sales = $this->saleModel->getSalesByDateRange($startDate, $endDate);
        
        $revenue = 0;
        $costOfGoods = 0;
        
        foreach ($sales as $sale) {
            $revenue += $sale['line_total'] ?? 0;
        }
        
        $grossProfit = $revenue - $costOfGoods;
        $margin = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;
        
        $this->successResponse('Financial report', [
            'revenue' => $revenue,
            'cost_of_goods' => $costOfGoods,
            'gross_profit' => $grossProfit,
            'gross_margin' => round($margin, 2)
        ]);
    }

    public function salesSummary(): void {
        $this->requireAuth();
        $date = $this->getInput('date', date('Y-m-d'));
        $summary = $this->saleModel->getDailySalesSummary($date);
        $this->successResponse('Daily summary', $summary);
    }

    public function export(): void {
        $this->requireAuth();
        $type = $this->getInput('type', 'csv');
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=report.csv');
        
        echo "Date,Revenue,Transactions\n";
        echo date('Y-m-d') . "," . "0,0\n";
    }
}
