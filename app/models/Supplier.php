<?php
/**
 * Supplier Model
 */

require_once __DIR__ . '/BaseModel.php';

class Supplier extends BaseModel {
    protected string $table = 'suppliers';
    protected string $primaryKey = 'supplier_id';
    protected array $fillable = ['company_name', 'contact_person', 'email', 'phone', 'address', 'city', 'state', 'payment_terms', 'account_name', 'account_number', 'bank_name', 'lead_time_days', 'is_preferred', 'status', 'notes'];

    public function getAllSuppliers(array $filters = [], int $page = 1, int $limit = 20): array {
        $options = ['order_by' => [['company_name', 'ASC']]];
        
        $conditions = [];
        
        if (isset($filters['status'])) {
            $conditions[] = ['status', '=', $filters['status']];
        }
        
        if (isset($filters['is_preferred'])) {
            $conditions[] = ['is_preferred', '=', $filters['is_preferred']];
        }
        
        if (!empty($conditions)) {
            $options['where'] = $conditions;
        }
        
        return $this->paginate($page, $limit, $options);
    }

    public function getActiveSuppliers(): array {
        return DatabaseConnection::table($this->table)
            ->where('status', 'active')
            ->orderBy('company_name', 'ASC')
            ->get();
    }

    public function getPreferredSuppliers(): array {
        return DatabaseConnection::table($this->table)
            ->where('status', 'active')
            ->where('is_preferred', 1)
            ->orderBy('company_name', 'ASC')
            ->get();
    }

    public function searchSuppliers(string $query): array {
        return DatabaseConnection::table($this->table)
            ->whereRaw("(company_name LIKE ? OR contact_person LIKE ? OR phone LIKE ?)", ["%$query%", "%$query%", "%$query%"])
            ->where('status', 'active')
            ->orderBy('company_name', 'ASC')
            ->limit(20)
            ->get();
    }

    public function validateNUBAN(string $accountNumber): bool {
        return preg_match('/^\d{10,11}$/', $accountNumber);
    }

    public function formatAccountNumber(string $accountNumber): string {
        return preg_replace('/\D/', '', $accountNumber);
    }

    public function getSupplierPerformance(int $supplierId): array {
        $sql = "SELECT 
                COUNT(po.po_id) as total_orders,
                SUM(CASE WHEN po.status = 'received' THEN po.total_amount ELSE 0 END) as total_spent,
                SUM(CASE WHEN po.status = 'received' AND po.actual_delivery_date <= po.expected_delivery_date THEN 1 ELSE 0 END) as on_time_deliveries,
                AVG(DATEDIFF(po.actual_delivery_date, po.po_date)) as avg_lead_time,
                COUNT(CASE WHEN po.status = 'cancelled' THEN 1 END) as cancelled_orders
                FROM purchase_orders po
                WHERE po.supplier_id = ?";
        
        $result = $this->executeRaw($sql, [$supplierId]);
        $stats = $result->fetch_assoc();
        
        $totalOrders = (int)$stats['total_orders'];
        $onTimeDeliveries = (int)$stats['on_time_deliveries'];
        
        return [
            'total_orders' => $totalOrders,
            'total_spent' => (float)($stats['total_spent'] ?? 0),
            'on_time_delivery_rate' => $totalOrders > 0 ? round(($onTimeDeliveries / $totalOrders) * 100, 2) : 0,
            'avg_lead_time' => round((float)($stats['avg_lead_time'] ?? 0), 1),
            'cancelled_orders' => (int)$stats['cancelled_orders']
        ];
    }

    public function getSupplierProducts(int $supplierId): array {
        $sql = "SELECT p.*, ps.supplier_cost, ps.supplier_sku, ps.is_primary
                FROM product_suppliers ps
                JOIN products p ON ps.product_id = p.product_id
                WHERE ps.supplier_id = ? AND p.is_active = 1
                ORDER BY ps.is_primary DESC, p.product_name ASC";
        
        $result = $this->executeRaw($sql, [$supplierId]);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getSupplierPOs(int $supplierId, int $limit = 10): array {
        return DatabaseConnection::table('purchase_orders')
            ->where('supplier_id', $supplierId)
            ->orderBy('po_date', 'DESC')
            ->limit($limit)
            ->get();
    }
}
