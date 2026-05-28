<?php
namespace Inventory;

use Core\Model;
use Core\Database;
use Core\InsufficientStockException;

class InventoryModel extends Model {
    protected string $table = 'inventory';
    protected string $primaryKey = 'inventory_id';
    protected array $fillable = [
        'product_id', 'quantity_on_hand', 'quantity_reserved',
        'status', 'warehouse_location', 'last_restock_date',
    ];

    public function getAllWithProducts(): array {
        $sql = "SELECT i.*, p.product_name, p.sku, p.category, p.selling_price, p.cost_price, p.reorder_level
                FROM inventory i
                JOIN products p ON i.product_id = p.product_id
                WHERE p.is_active = 1
                ORDER BY p.product_name ASC";
        return $this->executeRaw($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getByProductId(int $productId): ?array {
        return Database::table($this->table)->where('product_id', $productId)->first();
    }

    public function reserveStock(int $productId, int $quantity): bool {
        $inventory = $this->getByProductId($productId);
        if (!$inventory) throw new InsufficientStockException('No inventory record', 0);
        if ($inventory['quantity_available'] < $quantity) {
            throw new InsufficientStockException('Insufficient stock', $inventory['quantity_available']);
        }
        Database::getConnection()->begin_transaction();
        try {
            Database::table($this->table)
                ->where('product_id', $productId)
                ->update([
                    'quantity_reserved' => $inventory['quantity_reserved'] + $quantity,
                ]);
            Database::getConnection()->commit();
            return true;
        } catch (\Exception $e) {
            Database::getConnection()->rollback();
            throw $e;
        }
    }

    public function releaseStock(int $productId, int $quantity): bool {
        $inventory = $this->getByProductId($productId);
        if (!$inventory) throw new \Exception('No inventory record');
        $newReserved = max(0, $inventory['quantity_reserved'] - $quantity);
        Database::table($this->table)
            ->where('product_id', $productId)
            ->update(['quantity_reserved' => $newReserved]);
        return true;
    }

    public function deductStock(int $productId, int $quantity): bool {
        $inventory = $this->getByProductId($productId);
        if (!$inventory) throw new InsufficientStockException('No inventory record', 0);
        $newOnHand = $inventory['quantity_on_hand'] - $quantity;
        $newReserved = max(0, $inventory['quantity_reserved'] - $quantity);
        if ($newOnHand < 0) throw new InsufficientStockException('Insufficient stock', $inventory['quantity_on_hand']);
        Database::table($this->table)
            ->where('product_id', $productId)
            ->update([
                'quantity_on_hand'  => $newOnHand,
                'quantity_reserved' => $newReserved,
            ]);
        return true;
    }

    public function adjustStock(int $productId, int $newQuantity, string $type = 'recount', ?string $reason = null, ?string $approvalStatus = null): bool {
        $inventory = $this->getByProductId($productId);
        if (!$inventory) throw new \Exception('No inventory record');
        $oldQuantity = $inventory['quantity_on_hand'];
        Database::getConnection()->begin_transaction();
        try {
            Database::table($this->table)
                ->where('product_id', $productId)
                ->update(['quantity_on_hand' => $newQuantity]);
            Database::table('stock_adjustments')->insert([
                'product_id'       => $productId,
                'type'             => $type,
                'quantity_before'  => $oldQuantity,
                'quantity_after'   => $newQuantity,
                'reason'           => $reason,
                'approval_status'  => $approvalStatus ?? 'approved',
                'adjusted_by'      => $_SESSION['user_id'] ?? null,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
            Database::getConnection()->commit();
            return true;
        } catch (\Exception $e) {
            Database::getConnection()->rollback();
            throw $e;
        }
    }

    public function getLowStock(): array {
        $sql = "SELECT i.*, p.product_name, p.sku, p.category, p.reorder_level
                FROM inventory i
                JOIN products p ON i.product_id = p.product_id
                WHERE p.is_active = 1
                AND (i.quantity_on_hand <= p.reorder_level OR i.status IN ('low_stock', 'out_of_stock'))
                ORDER BY i.quantity_on_hand ASC";
        return $this->executeRaw($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getStockMovement(int $productId = 0, int $limit = 50): array {
        $sql = "SELECT sa.*, p.product_name, p.sku, u.name AS adjusted_by_name
                FROM stock_adjustments sa
                JOIN products p ON sa.product_id = p.product_id
                LEFT JOIN users u ON sa.adjusted_by = u.user_id";
        if ($productId > 0) $sql .= " WHERE sa.product_id = ?";
        $sql .= " ORDER BY sa.created_at DESC LIMIT ?";
        $bindings = $productId > 0 ? [$productId, $limit] : [$limit];
        return $this->executeRaw($sql, $bindings)->fetch_all(MYSQLI_ASSOC);
    }
}
