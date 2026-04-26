<?php
/**
 * Sales Transaction Model
 */

require_once __DIR__ . '/BaseModel.php';

class Sale extends BaseModel {
    protected string $table = 'sales_transactions';
    protected string $primaryKey = 'transaction_id';
    protected array $fillable = ['invoice_number', 'sale_date', 'sale_time', 'customer_name', 'customer_phone', 'customer_email', 'customer_address', 'product_id', 'quantity_sold', 'unit_price', 'payment_method', 'payment_status', 'notes', 'cashier_id'];
    protected array $casts = ['quantity_sold' => 'int', 'unit_price' => 'float', 'line_total' => 'float'];

    public function getAllSales(array $filters = [], int $page = 1, int $limit = 20): array {
        $options = ['order_by' => [['sale_date', 'DESC'], ['transaction_id', 'DESC']]];
        
        $conditions = [];
        
        if (!empty($filters['payment_status'])) {
            $conditions[] = ['payment_status', '=', $filters['payment_status']];
        }
        
        if (!empty($filters['payment_method'])) {
            $conditions[] = ['payment_method', '=', $filters['payment_method']];
        }
        
        if (!empty($filters['cashier_id'])) {
            $conditions[] = ['cashier_id', '=', $filters['cashier_id']];
        }
        
        if (!empty($conditions)) {
            $options['where'] = $conditions;
        }
        
        return $this->paginate($page, $limit, $options);
    }

    public function getSaleById(int $saleId): ?array {
        $sql = "SELECT s.*, p.product_name, p.sku, p.category, u.name as cashier_name
                FROM sales_transactions s
                JOIN products p ON s.product_id = p.product_id
                LEFT JOIN users u ON s.cashier_id = u.user_id
                WHERE s.transaction_id = ?";
        
        $result = $this->executeRaw($sql, [$saleId]);
        return $result->fetch_assoc() ?: null;
    }

    public function recordSale(array $data): int {
        $conn = DatabaseConnection::getConnection();
        
        $conn->begin_transaction();
        
        try {
            $invoiceNumber = $this->generateInvoiceNumber();
            
            $saleId = DatabaseConnection::table($this->table)->insert([
                'invoice_number' => $invoiceNumber,
                'sale_date' => $data['sale_date'] ?? date('Y-m-d'),
                'sale_time' => $data['sale_time'] ?? date('H:i:s'),
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'customer_address' => $data['customer_address'] ?? null,
                'product_id' => $data['product_id'],
                'quantity_sold' => $data['quantity_sold'],
                'unit_price' => $data['unit_price'],
                'payment_method' => $data['payment_method'],
                'payment_status' => $data['payment_status'] ?? 'pending',
                'notes' => $data['notes'] ?? null,
                'cashier_id' => $data['cashier_id'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            if (in_array($data['payment_status'] ?? 'pending', ['completed'])) {
                $inventory = new Inventory();
                $inventory->deductStock($data['product_id'], $data['quantity_sold']);
            }
            
            $conn->commit();
            
            return $saleId;
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function processPayment(int $saleId, string $paymentMethod): bool {
        $conn = DatabaseConnection::getConnection();
        
        $conn->begin_transaction();
        
        try {
            DatabaseConnection::table($this->table)
                ->where('transaction_id', $saleId)
                ->update([
                    'payment_method' => $paymentMethod,
                    'payment_status' => 'completed'
                ]);
            
            $sale = $this->getSaleById($saleId);
            
            $inventory = new Inventory();
            $inventory->deductStock($sale['product_id'], $sale['quantity_sold']);
            
            $conn->commit();
            
            return true;
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function processReturn(int $saleId, int $quantity, string $reason, int $userId): bool {
        $conn = DatabaseConnection::getConnection();
        
        $conn->begin_transaction();
        
        try {
            $sale = $this->getSaleById($saleId);
            
            if (!$sale) {
                throw new NotFoundException("Sale not found");
            }
            
            if ($quantity > $sale['quantity_sold']) {
                throw new ValidationException("Return quantity cannot exceed sold quantity");
            }
            
            $returnQuantity = min($quantity, $sale['quantity_sold']);
            
            DatabaseConnection::table($this->table)
                ->where('transaction_id', $saleId)
                ->update(['payment_status' => 'refunded']);
            
            $inventory = new Inventory();
            $inventory->updateStock($sale['product_id'], $returnQuantity);
            
            DatabaseConnection::table('stock_adjustments')->insert([
                'product_id' => $sale['product_id'],
                'adjustment_type' => 'return',
                'quantity_adjusted' => $returnQuantity,
                'quantity_before' => $sale['quantity_sold'] - $returnQuantity,
                'quantity_after' => $sale['quantity_sold'],
                'reason' => "Return for invoice {$sale['invoice_number']}: $reason",
                'adjusted_by' => $userId,
                'adjustment_date' => date('Y-m-d'),
                'approval_status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $conn->commit();
            
            return true;
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function getSalesByDateRange(string $startDate, string $endDate): array {
        $sql = "SELECT s.*, p.product_name, p.sku, u.name as cashier_name
                FROM sales_transactions s
                JOIN products p ON s.product_id = p.product_id
                LEFT JOIN users u ON s.cashier_id = u.user_id
                WHERE s.sale_date BETWEEN ? AND ?
                ORDER BY s.sale_date DESC, s.sale_time DESC";
        
        $result = $this->executeRaw($sql, [$startDate, $endDate]);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getDailySalesSummary(string $date = null): array {
        $date = $date ?? date('Y-m-d');
        
        $sql = "SELECT 
                COUNT(*) as transaction_count,
                SUM(line_total) as total_revenue,
                AVG(line_total) as average_sale,
                SUM(quantity_sold) as total_items_sold,
                SUM(CASE WHEN payment_method = 'cash' THEN line_total ELSE 0 END) as cash_sales,
                SUM(CASE WHEN payment_method = 'bank_transfer' THEN line_total ELSE 0 END) as bank_transfer_sales,
                SUM(CASE WHEN payment_method = 'paystack' THEN line_total ELSE 0 END) as paystack_sales,
                SUM(CASE WHEN payment_method = 'pos' THEN line_total ELSE 0 END) as pos_sales
                FROM sales_transactions
                WHERE sale_date = ? AND payment_status = 'completed'";
        
        $result = $this->executeRaw($sql, [$date]);
        return $result->fetch_assoc();
    }

    public function getMonthlySalesSummary(int $year, int $month): array {
        $startDate = sprintf("%04d-%02d-01", $year, $month);
        $endDate = date("Y-m-t", strtotime($startDate));
        
        $sql = "SELECT 
                COUNT(*) as transaction_count,
                SUM(line_total) as total_revenue,
                AVG(line_total) as average_sale,
                SUM(quantity_sold) as total_items_sold
                FROM sales_transactions
                WHERE sale_date BETWEEN ? AND ? AND payment_status = 'completed'";
        
        $result = $this->executeRaw($sql, [$startDate, $endDate]);
        return $result->fetch_assoc();
    }

    public function getSalesByProduct(int $productId, string $startDate = null, string $endDate = null): array {
        $sql = "SELECT s.*, p.product_name, u.name as cashier_name
                FROM sales_transactions s
                JOIN products p ON s.product_id = p.product_id
                LEFT JOIN users u ON s.cashier_id = u.user_id
                WHERE s.product_id = ?";
        
        $bindings = [$productId];
        
        if ($startDate && $endDate) {
            $sql .= " AND s.sale_date BETWEEN ? AND ?";
            $bindings[] = $startDate;
            $bindings[] = $endDate;
        }
        
        $sql .= " ORDER BY s.sale_date DESC, s.sale_time DESC";
        
        $result = $this->executeRaw($sql, $bindings);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getSalesByCashier(int $cashierId, string $startDate = null, string $endDate = null): array {
        $sql = "SELECT s.*, p.product_name
                FROM sales_transactions s
                JOIN products p ON s.product_id = p.product_id
                WHERE s.cashier_id = ?";
        
        $bindings = [$cashierId];
        
        if ($startDate && $endDate) {
            $sql .= " AND s.sale_date BETWEEN ? AND ?";
            $bindings[] = $startDate;
            $bindings[] = $endDate;
        }
        
        $sql .= " ORDER BY s.sale_date DESC, s.sale_time DESC";
        
        $result = $this->executeRaw($sql, $bindings);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getRecentSales(int $limit = 10): array {
        $sql = "SELECT s.*, p.product_name, p.sku, u.name as cashier_name
                FROM sales_transactions s
                JOIN products p ON s.product_id = p.product_id
                LEFT JOIN users u ON s.cashier_id = u.user_id
                ORDER BY s.sale_date DESC, s.transaction_id DESC
                LIMIT ?";
        
        $result = $this->executeRaw($sql, [$limit]);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getTopProducts(int $days = 30, int $limit = 5): array {
        $sql = "SELECT p.product_id, p.product_name, p.sku, p.category,
                SUM(s.quantity_sold) as total_quantity,
                SUM(s.line_total) as total_revenue,
                COUNT(*) as transaction_count
                FROM sales_transactions s
                JOIN products p ON s.product_id = p.product_id
                WHERE s.sale_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                AND s.payment_status = 'completed'
                GROUP BY p.product_id
                ORDER BY total_revenue DESC
                LIMIT ?";
        
        $result = $this->executeRaw($sql, [$days, $limit]);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    private function generateInvoiceNumber(): string {
        $year = date('Y');
        
        $result = DatabaseConnection::table($this->table)
            ->select("MAX(CAST(SUBSTRING(invoice_number, 9) AS UNSIGNED)) as max_num")
            ->where('invoice_number', "LIKE", "INV-$year-%")
            ->first();
        
        $nextNum = ($result['max_num'] ?? 0) + 1;
        
        return sprintf("INV-%s-%04d", $year, $nextNum);
    }
}
