<?php
namespace Inventory;

use Core\Model;
use Core\Database;

class SupplierModel extends Model {
    protected string $table = 'suppliers';
    protected string $primaryKey = 'supplier_id';
    protected array $fillable = [
        'company_name', 'contact_name', 'email', 'phone',
        'address', 'city', 'state', 'account_number', 'bank_name',
        'is_preferred', 'status', 'notes',
    ];

    public function getWithProductCount(): array {
        $sql = "SELECT s.*, COUNT(ps.product_id) as product_count
                FROM suppliers s
                LEFT JOIN product_suppliers ps ON s.supplier_id = ps.supplier_id
                GROUP BY s.supplier_id
                ORDER BY s.company_name ASC";
        return $this->executeRaw($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getSupplierWithProducts(int $supplierId): ?array {
        $supplier = $this->findById($supplierId);
        if (!$supplier) return null;

        $sql = "SELECT p.*, ps.supplier_cost, ps.supplier_sku, ps.is_primary
                FROM product_suppliers ps
                JOIN products p ON ps.product_id = p.product_id
                WHERE ps.supplier_id = ?";
        $supplier['products'] = $this->executeRaw($sql, [$supplierId])->fetch_all(MYSQLI_ASSOC);
        return $supplier;
    }

    public function search(string $query, int $limit = 20): array {
        return Database::table($this->table)
            ->whereRaw("(company_name LIKE ? OR contact_name LIKE ? OR email LIKE ?)", ["%$query%", "%$query%", "%$query%"])
            ->where('status', 'active')
            ->limit($limit)
            ->orderBy('company_name', 'ASC')
            ->get();
    }

    public function getPerformance(int $supplierId): array {
        $sql = "SELECT
                    COUNT(DISTINCT po.po_id) as total_pos,
                    COUNT(DISTINCT CASE WHEN po.status = 'received' THEN po.po_id END) as completed_pos,
                    AVG(
                        DATEDIFF(COALESCE(po.received_date, po.updated_at), po.created_at)
                    ) as avg_lead_time_days
                FROM purchase_orders po
                WHERE po.supplier_id = ?";
        $result = $this->executeRaw($sql, [$supplierId])->fetch_assoc();

        $sql2 = "SELECT SUM(poi.quantity_ordered) as total_ordered,
                        SUM(poi.quantity_received) as total_received
                 FROM purchase_order_items poi
                 JOIN purchase_orders po ON poi.po_id = po.po_id
                 WHERE po.supplier_id = ?";
        $result2 = $this->executeRaw($sql2, [$supplierId])->fetch_assoc();

        return array_merge($result ?: [], $result2 ?: []);
    }
}
