<?php
namespace Inventory;

use Core\Controller;
use Core\Database;

class ReportsController extends Controller {
    public function index(): void {
        $this->requireAnyRole(['admin', 'manager']);
        $type = $this->getInput('type', 'sales');
        $startDate = $this->getInput('start_date', date('Y-m-01'));
        $endDate = $this->getInput('end_date', date('Y-m-t'));

        $data = match ($type) {
            'inventory' => $this->inventoryReport(),
            'financial' => $this->financialReport($startDate, $endDate),
            default     => $this->salesReport($startDate, $endDate),
        };

        $this->success('Report generated', $data);
    }

    private function salesReport(string $startDate, string $endDate): array {
        $sql = "SELECT DATE(sale_date) as date, SUM(total_amount) as total, COUNT(*) as count
                FROM sales_transactions
                WHERE sale_date >= ? AND sale_date <= ?
                GROUP BY DATE(sale_date)
                ORDER BY date DESC";
        $daily = (new SaleModel())->executeRaw($sql, [$startDate, $endDate])->fetch_all(MYSQLI_ASSOC);

        $summary = Database::table('sales_transactions')
            ->select('COALESCE(SUM(total_amount), 0) as grand_total, COUNT(*) as total_transactions')
            ->whereRaw("sale_date >= ? AND sale_date <= ?", [$startDate, $endDate])
            ->first();

        $topProducts = (new SaleModel())->getTopProducts(10, $startDate, $endDate);

        return [
            'daily'      => $daily,
            'summary'    => $summary,
            'top_products' => $topProducts,
        ];
    }

    private function inventoryReport(): array {
        $sql = "SELECT p.category, COUNT(*) as count,
                SUM(i.quantity_on_hand) as total_stock,
                SUM(p.cost_price * i.quantity_on_hand) as total_value
                FROM products p
                LEFT JOIN inventory i ON p.product_id = i.product_id
                WHERE p.is_active = 1
                GROUP BY p.category";
        $byCategory = Database::getConnection()->query($sql)->fetch_all(MYSQLI_ASSOC);

        $lowStock = (new InventoryModel())->getLowStock();

        $totalValue = Database::table('products p')
            ->leftJoin('inventory i', 'p.product_id = i.product_id')
            ->select('SUM(p.cost_price * COALESCE(i.quantity_on_hand, 0)) as total_value')
            ->where('p.is_active', 1)
            ->first();

        return [
            'by_category' => $byCategory,
            'low_stock'   => $lowStock,
            'total_value' => (float)($totalValue['total_value'] ?? 0),
        ];
    }

    private function financialReport(string $startDate, string $endDate): array {
        $revenue = Database::table('sales_transactions')
            ->select('COALESCE(SUM(total_amount), 0) as total_revenue, COUNT(*) as transactions')
            ->whereRaw("sale_date >= ? AND sale_date <= ?", [$startDate, $endDate])
            ->first();

        $cost = Database::table('products p')
            ->join('sales_transactions st', 'p.product_id = st.product_id')
            ->select('COALESCE(SUM(p.cost_price * st.quantity_sold), 0) as total_cost')
            ->whereRaw("st.sale_date >= ? AND st.sale_date <= ?", [$startDate, $endDate])
            ->first();

        $revenueTotal = (float)($revenue['total_revenue'] ?? 0);
        $costTotal = (float)($cost['total_cost'] ?? 0);

        return [
            'total_revenue' => $revenueTotal,
            'total_cost'    => $costTotal,
            'gross_profit'  => $revenueTotal - $costTotal,
            'margin'        => $revenueTotal > 0 ? round(($revenueTotal - $costTotal) / $revenueTotal * 100, 2) : 0,
            'transactions'  => (int)($revenue['transactions'] ?? 0),
        ];
    }

    public function salesSummary(): void {
        $startDate = $this->getInput('start_date', date('Y-m-01'));
        $endDate = $this->getInput('end_date', date('Y-m-t'));
        $this->success('Sales summary', $this->salesReport($startDate, $endDate));
    }

    public function export(): void {
        $this->requireAnyRole(['admin', 'manager']);
        $type = $this->getInput('type', 'sales');
        $startDate = $this->getInput('start_date', date('Y-m-01'));
        $endDate = $this->getInput('end_date', date('Y-m-t'));

        if ($type === 'sales') {
            $sql = "SELECT st.invoice_number, p.product_name, st.quantity_sold, st.unit_price,
                           st.total_amount, st.payment_method, st.sale_date, st.customer_name
                    FROM sales_transactions st
                    JOIN products p ON st.product_id = p.product_id
                    WHERE st.sale_date >= ? AND st.sale_date <= ?
                    ORDER BY st.sale_date DESC";
            $rows = (new SaleModel())->executeRaw($sql, [$startDate, $endDate])->fetch_all(MYSQLI_ASSOC);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="sales_export_' . date('Ymd') . '.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Invoice', 'Product', 'Qty', 'Unit Price', 'Total', 'Payment', 'Date', 'Customer']);
            foreach ($rows as $row) fputcsv($output, $row);
            fclose($output);
            exit;
        }

        $this->error('Export type not supported');
    }
}
