<?php
namespace Inventory;

use Core\Model;
use Core\Database;
use Core\Logger;

class PurchaseOrderModel extends Model {
    protected string $table = 'purchase_orders';
    protected string $primaryKey = 'po_id';
    protected array $fillable = [
        'po_number', 'supplier_id', 'status', 'total_amount',
        'notes', 'order_date', 'expected_date', 'received_date',
    ];

    public function generatePoNumber(): string {
        $prefix = 'PO-' . date('Ymd') . '-';
        $result = Database::table($this->table)
            ->select("MAX(CAST(SUBSTRING(po_number, LENGTH('$prefix') + 1) AS UNSIGNED)) as max_num")
            ->where('po_number', 'LIKE', "$prefix%")
            ->first();
        $nextNum = ($result['max_num'] ?? 0) + 1;
        return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    public function createWithItems(array $poData, array $items): int {
        Database::getConnection()->begin_transaction();
        try {
            if (empty($poData['po_number'])) $poData['po_number'] = $this->generatePoNumber();
            if (!isset($poData['status'])) $poData['status'] = 'draft';
            if (!isset($poData['order_date'])) $poData['order_date'] = date('Y-m-d');

            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += ($item['unit_cost'] ?? 0) * ($item['quantity_ordered'] ?? 0);
            }
            $poData['total_amount'] = $totalAmount;

            $poId = Database::table($this->table)->insert($poData);

            foreach ($items as $item) {
                Database::table('purchase_order_items')->insert([
                    'po_id'             => $poId,
                    'product_id'        => $item['product_id'],
                    'quantity_ordered'  => $item['quantity_ordered'],
                    'quantity_received' => 0,
                    'unit_cost'         => $item['unit_cost'],
                    'created_at'        => date('Y-m-d H:i:s'),
                ]);
            }

            Database::getConnection()->commit();
            Logger::info("PO created", ['po_id' => $poId, 'user_id' => $_SESSION['user_id'] ?? null]);
            return $poId;

        } catch (\Exception $e) {
            Database::getConnection()->rollback();
            throw $e;
        }
    }

    public function getWithItems(int $poId): ?array {
        $po = $this->findById($poId);
        if (!$po) return null;

        $sql = "SELECT poi.*, p.product_name, p.sku
                FROM purchase_order_items poi
                JOIN products p ON poi.product_id = p.product_id
                WHERE poi.po_id = ?";
        $po['items'] = $this->executeRaw($sql, [$poId])->fetch_all(MYSQLI_ASSOC);

        $supplier = Database::table('suppliers')->where('supplier_id', $po['supplier_id'])->first();
        $po['supplier'] = $supplier;

        return $po;
    }

    public function getAllWithDetails(): array {
        $sql = "SELECT po.*, s.company_name as supplier_name
                FROM purchase_orders po
                JOIN suppliers s ON po.supplier_id = s.supplier_id
                ORDER BY po.created_at DESC";
        return $this->executeRaw($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function updateStatus(int $poId, string $status): bool {
        return Database::table($this->table)
            ->where($this->primaryKey, $poId)
            ->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]) > 0;
    }

    public function receiveItems(int $poId, array $receivedItems): bool {
        Database::getConnection()->begin_transaction();
        try {
            $allReceived = true;
            foreach ($receivedItems as $item) {
                $itemId = $item['po_item_id'];
                $receivedQty = (int)$item['quantity_received'];

                $existing = Database::table('purchase_order_items')
                    ->where('po_item_id', $itemId)
                    ->first();

                if (!$existing) continue;

                $newReceived = $existing['quantity_received'] + $receivedQty;
                Database::table('purchase_order_items')
                    ->where('po_item_id', $itemId)
                    ->update(['quantity_received' => $newReceived]);

                if ($newReceived < $existing['quantity_ordered']) $allReceived = false;

                // Update inventory
                $invModel = new InventoryModel();
                $inv = $invModel->getByProductId($existing['product_id']);
                if ($inv) {
                    $newQty = $inv['quantity_on_hand'] + $receivedQty;
                    Database::table('inventory')
                        ->where('product_id', $existing['product_id'])
                        ->update(['quantity_on_hand' => $newQty, 'last_restock_date' => date('Y-m-d H:i:s')]);
                } else {
                    Database::table('inventory')->insert([
                        'product_id'       => $existing['product_id'],
                        'quantity_on_hand' => $receivedQty,
                        'status'           => $receivedQty > 0 ? 'in_stock' : 'out_of_stock',
                        'created_at'       => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            $newStatus = $allReceived ? 'received' : 'partially_received';
            $updateData = ['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')];
            if ($newStatus === 'received') $updateData['received_date'] = date('Y-m-d H:i:s');
            Database::table($this->table)->where($this->primaryKey, $poId)->update($updateData);

            Database::getConnection()->commit();
            Logger::info("PO items received", ['po_id' => $poId, 'user_id' => $_SESSION['user_id'] ?? null]);
            return true;

        } catch (\Exception $e) {
            Database::getConnection()->rollback();
            throw $e;
        }
    }
}
