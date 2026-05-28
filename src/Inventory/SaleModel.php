<?php
namespace Inventory;

use Core\Model;
use Core\Database;

class SaleModel extends Model {
    protected string $table = 'sales_transactions';
    protected string $primaryKey = 'transaction_id';
    protected array $fillable = [
        'invoice_number', 'product_id', 'quantity_sold', 'unit_price',
        'total_amount', 'payment_method', 'payment_status', 'customer_name',
        'customer_phone', 'customer_email', 'sale_date', 'source', 'notes',
    ];

    public function generateInvoiceNumber(): string {
        $prefix = 'INV-' . date('Ymd') . '-';
        $result = Database::table($this->table)
            ->select("MAX(CAST(SUBSTRING(invoice_number, LENGTH('$prefix') + 1) AS UNSIGNED)) as max_num")
            ->where('invoice_number', 'LIKE', "$prefix%")
            ->first();
        $nextNum = ($result['max_num'] ?? 0) + 1;
        return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    public function recordSale(array $data): int {
        Database::getConnection()->begin_transaction();
        try {
            if (empty($data['invoice_number'])) {
                $data['invoice_number'] = $this->generateInvoiceNumber();
            }
            if (empty($data['sale_date'])) {
                $data['sale_date'] = date('Y-m-d H:i:s');
            }

            $id = Database::table($this->table)->insert($data);

            // Deduct stock
            $inventoryModel = new InventoryModel();
            $inventoryModel->deductStock($data['product_id'], $data['quantity_sold']);

            Database::getConnection()->commit();
            return $id;
        } catch (\Exception $e) {
            Database::getConnection()->rollback();
            throw $e;
        }
    }

    public function processReturn(int $transactionId, int $quantity): bool {
        $sale = $this->findById($transactionId);
        if (!$sale) throw new \Exception('Sale not found');
        if ($quantity > $sale['quantity_sold']) throw new \Exception('Return quantity exceeds sold quantity');

        Database::getConnection()->begin_transaction();
        try {
            Database::table('stock_adjustments')->insert([
                'product_id'      => $sale['product_id'],
                'type'            => 'return',
                'quantity_before' => 0,
                'quantity_after'  => $quantity,
                'reason'          => "Return from sale {$sale['invoice_number']}",
                'created_at'      => date('Y-m-d H:i:s'),
            ]);

            $inv = Database::table('inventory')->where('product_id', $sale['product_id'])->first();
            if ($inv) {
                Database::table('inventory')
                    ->where('product_id', $sale['product_id'])
                    ->update(['quantity_on_hand' => $inv['quantity_on_hand'] + $quantity]);
            }

            Database::getConnection()->commit();
            return true;
        } catch (\Exception $e) {
            Database::getConnection()->rollback();
            throw $e;
        }
    }

    public function getDailySales(string $date = ''): array {
        if (empty($date)) $date = date('Y-m-d');
        return Database::table($this->table)
            ->select('SUM(total_amount) as total, COUNT(*) as count, payment_method')
            ->whereRaw("DATE(sale_date) = ?", [$date])
            ->groupBy('payment_method')
            ->get();
    }

    public function getMonthlySales(int $year = 0, int $month = 0): array {
        if ($year === 0) $year = (int)date('Y');
        if ($month === 0) $month = (int)date('m');
        return Database::table($this->table)
            ->select('SUM(total_amount) as total, COUNT(*) as count')
            ->whereRaw("YEAR(sale_date) = ? AND MONTH(sale_date) = ?", [$year, $month])
            ->first();
    }

    public function getRecentSales(int $limit = 10): array {
        $sql = "SELECT st.*, p.product_name, p.sku
                FROM sales_transactions st
                JOIN products p ON st.product_id = p.product_id
                ORDER BY st.sale_date DESC LIMIT ?";
        return $this->executeRaw($sql, [$limit])->fetch_all(MYSQLI_ASSOC);
    }

    public function getTopProducts(int $limit = 10, string $startDate = '', string $endDate = ''): array {
        $sql = "SELECT p.product_id, p.product_name, p.sku,
                SUM(st.quantity_sold) as total_quantity,
                SUM(st.total_amount) as total_revenue
                FROM sales_transactions st
                JOIN products p ON st.product_id = p.product_id";
        $bindings = [];
        if ($startDate && $endDate) {
            $sql .= " WHERE st.sale_date >= ? AND st.sale_date <= ?";
            $bindings = [$startDate, $endDate];
        }
        $sql .= " GROUP BY p.product_id, p.product_name, p.sku
                  ORDER BY total_quantity DESC LIMIT ?";
        $bindings[] = $limit;
        return $this->executeRaw($sql, $bindings)->fetch_all(MYSQLI_ASSOC);
    }
}
