<?php
namespace Inventory;

use Core\Model;
use Core\Database;

class ProductModel extends Model {
    protected string $table = 'products';
    protected string $primaryKey = 'product_id';
    protected array $fillable = [
        'uuid', 'sku', 'product_name', 'category', 'subcategory',
        'description', 'specifications', 'cost_price', 'selling_price',
        'reorder_level', 'unit_of_measurement', 'image_url', 'is_featured', 'is_active',
    ];
    protected array $casts = [
        'specifications' => 'json',
        'cost_price' => 'float',
        'selling_price' => 'float',
        'markup_percentage' => 'float',
        'reorder_level' => 'int',
        'is_active' => 'bool',
    ];

    public function getAllProducts(array $filters = [], int $page = 1, int $limit = 20): array {
        $options = ['order_by' => [['product_name', 'ASC']]];
        $conditions = [];

        if (isset($filters['category'])) $conditions[] = ['category', '=', $filters['category']];
        if (isset($filters['is_active'])) $conditions[] = ['is_active', '=', $filters['is_active']];
        if (isset($filters['search'])) $conditions[] = ['product_name', 'LIKE', '%' . $filters['search'] . '%'];

        if (!empty($conditions)) $options['where'] = $conditions;
        return $this->paginate($page, $limit, $options);
    }

    public function getProductBySku(string $sku): ?array {
        return Database::table($this->table)->where('sku', $sku)->first();
    }

    public function getProductByUuid(string $uuid): ?array {
        return Database::table($this->table)->where('uuid', $uuid)->first();
    }

    public function getActiveProducts(): array {
        return Database::table($this->table)
            ->where('is_active', 1)
            ->orderBy('product_name', 'ASC')
            ->get();
    }

    public function getProductsByCategory(string $category): array {
        return Database::table($this->table)
            ->where('category', $category)
            ->where('is_active', 1)
            ->orderBy('product_name', 'ASC')
            ->get();
    }

    public function searchProducts(string $query, int $limit = 20): array {
        return Database::table($this->table)
            ->whereRaw("(product_name LIKE ? OR sku LIKE ?)", ["%$query%", "%$query%"])
            ->where('is_active', 1)
            ->limit($limit)
            ->orderBy('product_name', 'ASC')
            ->get();
    }

    public function getProductsWithStock(): array {
        $sql = "SELECT p.*,
                COALESCE(i.quantity_on_hand, 0) AS stock_quantity,
                COALESCE(i.quantity_available, 0) AS available_quantity,
                COALESCE(i.status, 'out_of_stock') AS stock_status
                FROM products p
                LEFT JOIN inventory i ON p.product_id = i.product_id
                WHERE p.is_active = 1 AND p.is_featured = 1
                ORDER BY p.created_at DESC";
        return $this->executeRaw($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getProductWithStock(int $productId): ?array {
        $sql = "SELECT p.*,
                COALESCE(i.quantity_on_hand, 0) AS stock_quantity,
                COALESCE(i.quantity_available, 0) AS available_quantity,
                COALESCE(i.quantity_reserved, 0) AS reserved_quantity,
                COALESCE(i.status, 'out_of_stock') AS stock_status,
                i.warehouse_location, i.last_restock_date
                FROM products p
                LEFT JOIN inventory i ON p.product_id = i.product_id
                WHERE p.product_id = ? AND p.is_active = 1";
        $result = $this->executeRaw($sql, [$productId]);
        return $result->fetch_assoc() ?: null;
    }

    public function calculateMarkup(float $costPrice, float $sellingPrice): float {
        if ($costPrice <= 0) return 0;
        return round((($sellingPrice - $costPrice) / $costPrice) * 100, 2);
    }

    public function calculateSellingPrice(float $costPrice, float $markupPercentage): float {
        return round($costPrice * (1 + $markupPercentage / 100), 2);
    }

    public function getSuppliers(int $productId): array {
        $sql = "SELECT s.*, ps.supplier_cost, ps.supplier_sku, ps.is_primary
                FROM product_suppliers ps
                JOIN suppliers s ON ps.supplier_id = s.supplier_id
                WHERE ps.product_id = ?
                ORDER BY ps.is_primary DESC, s.company_name ASC";
        $result = $this->executeRaw($sql, [$productId]);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function addSupplier(int $productId, int $supplierId, ?float $cost = null, ?string $supplierSku = null, bool $isPrimary = false): int {
        return Database::table('product_suppliers')->insert([
            'product_id'    => $productId,
            'supplier_id'   => $supplierId,
            'supplier_cost' => $cost,
            'supplier_sku'  => $supplierSku,
            'is_primary'    => $isPrimary ? 1 : 0,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    public function removeSupplier(int $productId, int $supplierId): int {
        return Database::table('product_suppliers')
            ->where('product_id', $productId)
            ->where('supplier_id', $supplierId)
            ->delete();
    }

    public function generateSku(string $category): string {
        $prefix = strtoupper(substr($category, 0, 3));
        $year = date('Y');
        $result = Database::table($this->table)
            ->select("MAX(CAST(SUBSTRING(sku, 5) AS UNSIGNED)) as max_num")
            ->where('sku', 'LIKE', "$prefix-$year-%")
            ->first();
        $nextNum = ($result['max_num'] ?? 0) + 1;
        return sprintf("%s-%s-%04d", $prefix, $year, $nextNum);
    }

    public function getTotalProductCount(): int {
        return Database::table($this->table)->where('is_active', 1)->count();
    }

    public function getTotalInventoryValue(): float {
        $sql = "SELECT SUM(p.cost_price * COALESCE(i.quantity_on_hand, 0)) as total_value
                FROM products p
                LEFT JOIN inventory i ON p.product_id = i.product_id
                WHERE p.is_active = 1";
        $result = $this->executeRaw($sql);
        return (float)($result->fetch_assoc()['total_value'] ?? 0);
    }
}
