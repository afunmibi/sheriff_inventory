<?php
namespace Inventory;

use Core\Controller;
use Core\Database;

class DashboardController extends Controller {
    public function index(): void {
        $this->requireAuth();

        $totalProducts = Database::table('products')->where('is_active', 1)->count();

        $lowStockCount = Database::table('inventory i')
            ->join('products p', 'i.product_id = p.product_id')
            ->whereRaw('i.quantity_on_hand <= p.reorder_level')
            ->count();

        $todaySales = Database::table('sales_transactions')
            ->select('COALESCE(SUM(total_amount), 0) as total, COUNT(*) as count')
            ->whereRaw("DATE(sale_date) = CURDATE()")
            ->first();

        $pendingPOs = Database::table('purchase_orders')
            ->whereRaw("status NOT IN ('received', 'cancelled')")
            ->count();

        $totalSuppliers = Database::table('suppliers')->where('status', 'active')->count();

        $recentSales = (new SaleModel())->getRecentSales(5);

        $lowStockItems = (new InventoryModel())->getLowStock();

        $this->success('Dashboard metrics', [
            'stats' => [
                'total_products'  => (int)$totalProducts,
                'low_stock_count' => (int)$lowStockCount,
                'today_sales'     => (float)($todaySales['total'] ?? 0),
                'today_transactions' => (int)($todaySales['count'] ?? 0),
                'pending_pos'     => (int)$pendingPOs,
                'total_suppliers' => (int)$totalSuppliers,
            ],
            'recent_sales'  => $recentSales,
            'low_stock'     => $lowStockItems,
        ]);
    }

    public function metrics(): void {
        $this->index();
    }
}
