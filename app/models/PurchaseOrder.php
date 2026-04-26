<?php
/**
 * Purchase Order Model
 */

require_once __DIR__ . '/BaseModel.php';

class PurchaseOrder extends BaseModel {
    protected string $table = 'purchase_orders';
    protected string $primaryKey = 'po_id';
    protected array $fillable = ['po_number', 'supplier_id', 'po_date', 'expected_delivery_date', 'status', 'total_amount', 'payment_status', 'notes', 'created_by', 'approved_by'];

    public function getAllPOs(array $filters = [], int $page = 1, int $limit = 20): array {
        $options = ['order_by' => [['po_date', 'DESC']]];
        
        $conditions = [];
        
        if (!empty($filters['status'])) {
            $conditions[] = ['status', '=', $filters['status']];
        }
        
        if (!empty($filters['supplier_id'])) {
            $conditions[] = ['supplier_id', '=', $filters['supplier_id']];
        }
        
        if (!empty($conditions)) {
            $options['where'] = $conditions;
        }
        
        return $this->paginate($page, $limit, $options);
    }

    public function getPOById(int $poId): ?array {
        $sql = "SELECT po.*, s.company_name, s.contact_person, s.phone as supplier_phone, s.email as supplier_email
                FROM purchase_orders po
                JOIN suppliers s ON po.supplier_id = s.supplier_id
                WHERE po.po_id = ?";
        
        $result = $this->executeRaw($sql, [$poId]);
        return $result->fetch_assoc() ?: null;
    }

    public function getPOItems(int $poId): array {
        return DatabaseConnection::table('purchase_order_items')
            ->where('po_id', $poId)
            ->orderBy('po_item_id', 'ASC')
            ->get();
    }

    public function createPO(array $data, array $items): int {
        $conn = DatabaseConnection::getConnection();
        
        $conn->begin_transaction();
        
        try {
            $poNumber = $this->generatePONumber();
            
            $poId = DatabaseConnection::table($this->table)->insert([
                'po_number' => $poNumber,
                'supplier_id' => $data['supplier_id'],
                'po_date' => $data['po_date'] ?? date('Y-m-d'),
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'status' => 'draft',
                'total_amount' => 0,
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $totalAmount = 0;
            
            foreach ($items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_cost'];
                $totalAmount += $lineTotal;
                
                DatabaseConnection::table('purchase_order_items')->insert([
                    'po_id' => $poId,
                    'product_id' => $item['product_id'],
                    'quantity_ordered' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            DatabaseConnection::table($this->table)
                ->where('po_id', $poId)
                ->update(['total_amount' => $totalAmount]);
            
            $conn->commit();
            
            return $poId;
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function updatePO(int $poId, array $data): int {
        $current = $this->findById($poId);
        
        if (!$current) {
            throw new NotFoundException("Purchase order not found");
        }
        
        if ($current['status'] !== 'draft') {
            throw new ConflictException("Only draft purchase orders can be updated");
        }
        
        if (isset($data['items'])) {
            $items = $data['items'];
            unset($data['items']);
            
            $totalAmount = 0;
            
            DatabaseConnection::table('purchase_order_items')
                ->where('po_id', $poId)
                ->delete();
            
            foreach ($items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_cost'];
                $totalAmount += $lineTotal;
                
                DatabaseConnection::table('purchase_order_items')->insert([
                    'po_id' => $poId,
                    'product_id' => $item['product_id'],
                    'quantity_ordered' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            $data['total_amount'] = $totalAmount;
        }
        
        return $this->update($poId, $data);
    }

    public function submitPO(int $poId, int $userId): int {
        return DatabaseConnection::table($this->table)
            ->where('po_id', $poId)
            ->where('status', 'draft')
            ->update(['status' => 'submitted']);
    }

    public function approvePO(int $poId, int $userId): int {
        return DatabaseConnection::table($this->table)
            ->where('po_id', $poId)
            ->where('status', 'submitted')
            ->update([
                'status' => 'approved',
                'approved_by' => $userId
            ]);
    }

    public function receivePO(int $poId, array $receivedItems): bool {
        $conn = DatabaseConnection::getConnection();
        
        $conn->begin_transaction();
        
        try {
            $po = $this->getPOById($poId);
            
            if (!in_array($po['status'], ['approved', 'partially_received'])) {
                throw new ConflictException("PO must be approved before receiving");
            }
            
            $totalReceivedAmount = 0;
            
            foreach ($receivedItems as $item) {
                $poItemId = $item['po_item_id'];
                $quantityReceived = $item['quantity_received'];
                
                $currentItem = DatabaseConnection::table('purchase_order_items')
                    ->where('po_item_id', $poItemId)
                    ->first();
                
                $newReceivedQty = $currentItem['quantity_received'] + $quantityReceived;
                $itemStatus = $newReceivedQty >= $currentItem['quantity_ordered'] ? 'received' : 'pending';
                
                DatabaseConnection::table('purchase_order_items')
                    ->where('po_item_id', $poItemId)
                    ->update([
                        'quantity_received' => $newReceivedQty,
                        'status' => $itemStatus
                    ]);
                
                $totalReceivedAmount += $quantityReceived * $currentItem['unit_cost'];
                
                $inventory = new Inventory();
                $inventory->updateStock($currentItem['product_id'], $quantityReceived);
            }
            
            $allReceived = $this->checkAllItemsReceived($poId);
            
            $newStatus = $allReceived ? 'received' : 'partially_received';
            $actualDelivery = $allReceived ? date('Y-m-d') : null;
            
            DatabaseConnection::table($this->table)
                ->where('po_id', $poId)
                ->update([
                    'status' => $newStatus,
                    'actual_delivery_date' => $actualDelivery
                ]);
            
            $conn->commit();
            return true;
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function cancelPO(int $poId): int {
        $po = $this->findById($poId);
        
        if (!$po) {
            throw new NotFoundException("Purchase order not found");
        }
        
        if (in_array($po['status'], ['received', 'partially_received'])) {
            throw new ConflictException("Cannot cancel a received or partially received PO");
        }
        
        return DatabaseConnection::table($this->table)
            ->where('po_id', $poId)
            ->update(['status' => 'cancelled']);
    }

    public function getPendingPOs(): array {
        return DatabaseConnection::table($this->table)
            ->whereIn('status', ['draft', 'submitted', 'approved', 'partially_received'])
            ->orderBy('expected_delivery_date', 'ASC')
            ->get();
    }

    private function generatePONumber(): string {
        $year = date('Y');
        
        $result = DatabaseConnection::table($this->table)
            ->select("MAX(CAST(SUBSTRING(po_number, 9) AS UNSIGNED)) as max_num")
            ->where('po_number', "LIKE", "PO-$year-%")
            ->first();
        
        $nextNum = ($result['max_num'] ?? 0) + 1;
        
        return sprintf("PO-%s-%04d", $year, $nextNum);
    }

    private function checkAllItemsReceived(int $poId): bool {
        $items = $this->getPOItems($poId);
        
        foreach ($items as $item) {
            if ($item['quantity_received'] < $item['quantity_ordered']) {
                return false;
            }
        }
        
        return true;
    }
}
