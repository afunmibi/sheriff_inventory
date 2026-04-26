<?php
/**
 * Inventory Model
 */

require_once __DIR__ . '/BaseModel.php';

class Inventory extends BaseModel {
    protected string $table = 'inventory';
    protected string $primaryKey = 'inventory_id';
    protected array $fillable = ['product_id', 'quantity_on_hand', 'quantity_reserved', 'last_restock_date', 'warehouse_location', 'batch_number', 'serial_numbers', 'status'];

    public function getStockLevel(int $productId): ?array {
        return DatabaseConnection::table($this->table)
            ->where('product_id', $productId)
            ->first();
    }

    public function getAllInventory(array $filters = []): array {
        $sql = "SELECT p.*, 
                COALESCE(i.quantity_on_hand, 0) as quantity_on_hand,
                COALESCE(i.quantity_reserved, 0) as quantity_reserved,
                COALESCE(i.quantity_available, 0) as quantity_available,
                COALESCE(i.status, 'out_of_stock') as status,
                i.warehouse_location,
                i.last_restock_date,
                i.batch_number
                FROM products p
                LEFT JOIN inventory i ON p.product_id = i.product_id
                WHERE p.is_active = 1";
        
        $bindings = [];
        
        if (!empty($filters['category'])) {
            $sql .= " AND p.category = ?";
            $bindings[] = $filters['category'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND COALESCE(i.status, 'out_of_stock') = ?";
            $bindings[] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (p.product_name LIKE ? OR p.sku LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $bindings[] = $searchTerm;
            $bindings[] = $searchTerm;
        }
        
        $sql .= " ORDER BY p.product_name ASC";
        
        $result = $this->executeRaw($sql, $bindings);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function updateStock(int $productId, int $quantity): int {
        $conn = DatabaseConnection::getConnection();
        
        $conn->begin_transaction();
        
        try {
            $current = $this->getStockLevel($productId);
            
            if (!$current) {
                $sql = "INSERT INTO inventory (product_id, quantity_on_hand, last_restock_date, status) 
                        VALUES (?, ?, NOW(), 'in_stock')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ii', $productId, $quantity);
                $stmt->execute();
                $stmt->close();
            } else {
                $sql = "UPDATE inventory SET quantity_on_hand = ?, last_restock_date = NOW() WHERE product_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ii', $quantity, $productId);
                $stmt->execute();
                $stmt->close();
            }
            
            $this->updateStatus($productId);
            
            $conn->commit();
            return $quantity;
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function reserveStock(int $productId, int $quantity): bool {
        $conn = DatabaseConnection::getConnection();
        
        $conn->begin_transaction();
        
        try {
            $current = $this->getStockLevel($productId);
            
            if (!$current || $current['quantity_available'] < $quantity) {
                throw new InsufficientStockException("Insufficient stock available", (int)($current['quantity_available'] ?? 0));
            }
            
            $sql = "UPDATE inventory SET quantity_reserved = quantity_reserved + ? WHERE product_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $quantity, $productId);
            $stmt->execute();
            $stmt->close();
            
            $this->updateStatus($productId);
            
            $conn->commit();
            return true;
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function releaseReserve(int $productId, int $quantity): int {
        $sql = "UPDATE inventory 
                SET quantity_reserved = GREATEST(0, quantity_reserved - ?),
                    quantity_on_hand = quantity_on_hand + ?
                WHERE product_id = ?";
        
        $stmt = $this->executeRaw($sql, [$quantity, $quantity, $productId]);
        
        return DatabaseConnection::getConnection()->affected_rows;
    }

    public function deductStock(int $productId, int $quantity): bool {
        $conn = DatabaseConnection::getConnection();
        
        $conn->begin_transaction();
        
        try {
            $current = $this->getStockLevel($productId);
            
            if (!$current || $current['quantity_available'] < $quantity) {
                throw new InsufficientStockException("Insufficient stock available", (int)($current['quantity_available'] ?? 0));
            }
            
            $sql = "UPDATE inventory 
                    SET quantity_on_hand = quantity_on_hand - ? 
                    WHERE product_id = ? AND quantity_on_hand >= ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iii', $quantity, $productId, $quantity);
            $stmt->execute();
            
            if ($stmt->affected_rows === 0) {
                throw new InsufficientStockException("Insufficient stock available");
            }
            
            $stmt->close();
            
            $this->updateStatus($productId);
            
            $conn->commit();
            return true;
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function adjustStock(int $productId, int $quantity, string $type, string $reason, int $adjustedBy): int {
        $conn = DatabaseConnection::getConnection();
        
        $conn->begin_transaction();
        
        try {
            $current = $this->getStockLevel($productId);
            $quantityBefore = $current ? (int)$current['quantity_on_hand'] : 0;
            $quantityAfter = $quantityBefore + $quantity;
            
            if ($quantityAfter < 0) {
                throw new ValidationException("Adjustment would result in negative stock");
            }
            
            DatabaseConnection::table('stock_adjustments')->insert([
                'product_id' => $productId,
                'adjustment_type' => $type,
                'quantity_adjusted' => $quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reason' => $reason,
                'adjusted_by' => $adjustedBy,
                'adjustment_date' => date('Y-m-d'),
                'approval_status' => 'approved',
                'approved_by' => $adjustedBy,
                'approved_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $sql = "UPDATE inventory SET quantity_on_hand = ? WHERE product_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $quantityAfter, $productId);
            $stmt->execute();
            $stmt->close();
            
            $this->updateStatus($productId);
            
            $conn->commit();
            return $quantityAfter;
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function getLowStockAlerts(): array {
        $sql = "SELECT p.product_id, p.sku, p.product_name, p.category, p.reorder_level,
                COALESCE(i.quantity_on_hand, 0) as current_stock,
                (p.reorder_level - COALESCE(i.quantity_on_hand, 0)) as deficit
                FROM products p
                LEFT JOIN inventory i ON p.product_id = i.product_id
                WHERE p.is_active = 1 
                AND COALESCE(i.quantity_on_hand, 0) <= p.reorder_level
                ORDER BY deficit DESC";
        
        $result = $this->executeRaw($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getStockMovement(int $days = 30): array {
        $sql = "SELECT 
                DATE(sa.adjustment_date) as date,
                SUM(CASE WHEN sa.adjustment_type IN ('recount', 'bonus', 'return') THEN sa.quantity_adjusted ELSE 0 END) as stock_in,
                SUM(CASE WHEN sa.adjustment_type IN ('damage', 'loss') THEN ABS(sa.quantity_adjusted) ELSE 0 END) as stock_out,
                COUNT(*) as adjustments
                FROM stock_adjustments sa
                WHERE sa.adjustment_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                AND sa.approval_status = 'approved'
                GROUP BY DATE(sa.adjustment_date)
                ORDER BY date ASC";
        
        $result = $this->executeRaw($sql, [$days]);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    private function updateStatus(int $productId): void {
        $sql = "UPDATE inventory i
                JOIN products p ON i.product_id = p.product_id
                SET i.status = CASE
                    WHEN i.quantity_on_hand <= 0 THEN 'out_of_stock'
                    WHEN i.quantity_on_hand <= p.reorder_level THEN 'low_stock'
                    ELSE 'in_stock'
                END
                WHERE i.product_id = ?";
        
        $stmt = DatabaseConnection::getConnection()->prepare($sql);
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $stmt->close();
    }
}
