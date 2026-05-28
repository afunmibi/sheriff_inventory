<?php
/**
 * Dashboard Controller
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Inventory.php';
require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../models/PurchaseOrder.php';

class DashboardController extends BaseController {
    private Product $productModel;
    private Inventory $inventoryModel;
    private Sale $saleModel;
    private PurchaseOrder $poModel;

    public function __construct() {
        parent::__construct();
        $this->productModel = new Product();
        $this->inventoryModel = new Inventory();
        $this->saleModel = new Sale();
        $this->poModel = new PurchaseOrder();
    }

    public function index(): void {
        $this->requireAuth();
        
        $stats = $this->getQuickStats();
        
        $this->successResponse('Dashboard data', $stats);
    }

    public function metrics(): void {
        $this->requireAuth();
        
        $metrics = $this->getDetailedMetrics();
        
        $this->successResponse('Dashboard metrics', $metrics);
    }

    private function getQuickStats(): array {
        $today = date('Y-m-d');
        
        $totalProducts = $this->productModel->getTotalProductCount();
        $totalValue = $this->productModel->getTotalInventoryValue();
        
        $lowStock = $this->inventoryModel->getLowStockAlerts();
        $lowStockCount = count($lowStock);
        
        $todaySales = $this->saleModel->getDailySalesSummary($today);
        
        $pendingPOs = $this->poModel->getPendingPOs();
        $pendingPOCount = count($pendingPOs);
        
        return [
            'total_products' => $totalProducts,
            'total_inventory_value' => $totalValue,
            'low_stock_count' => $lowStockCount,
            'today_sales' => $todaySales['total_revenue'] ?? 0,
            'today_transactions' => $todaySales['transaction_count'] ?? 0,
            'pending_pos' => $pendingPOCount,
            'date' => $today
        ];
    }

    private function getDetailedMetrics(): array {
        $today = date('Y-m-d');
        $weekAgo = date('Y-m-d', strtotime('-7 days'));
        $monthAgo = date('Y-m-d', strtotime('-30 days'));
        
        $lowStock = $this->inventoryModel->getLowStockAlerts();
        
        $todaySales = $this->saleModel->getDailySalesSummary($today);
        
        $recentSales = $this->saleModel->getRecentSales(10);
        
        $topProducts = $this->saleModel->getTopProducts(30, 5);
        
        $pendingPOs = $this->poModel->getPendingPOs();
        
        return [
            'quick_stats' => $this->getQuickStats(),
            'low_stock_items' => $lowStock,
            'today_summary' => $todaySales,
            'recent_sales' => $recentSales,
            'top_products' => $topProducts,
            'pending_pos' => $pendingPOs
        ];
    }
}
