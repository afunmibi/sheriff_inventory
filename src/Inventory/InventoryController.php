<?php
namespace Inventory;

use Core\Controller;
use Core\Logger;

class InventoryController extends Controller {
    private InventoryModel $inventoryModel;

    public function __construct() {
        parent::__construct();
        $this->inventoryModel = new InventoryModel();
    }

    public function index(): void {
        $this->requireAuth();
        $items = $this->inventoryModel->getAllWithProducts();
        $this->success('Inventory retrieved', $items);
    }

    public function show(int $id): void {
        $this->requireAuth();
        $item = $this->inventoryModel->findById($id);
        if (!$item) $this->notFound('Inventory record not found');
        $this->success('Inventory record retrieved', $item);
    }

    public function update(int $id): void {
        $this->requireAnyRole(['admin', 'manager', 'warehouse_staff']);
        $data = $this->getJsonInput();
        $item = $this->inventoryModel->findById($id);
        if (!$item) $this->notFound('Inventory record not found');
        $this->inventoryModel->update($id, $data);
        Logger::info("Inventory updated", ['inventory_id' => $id, 'user_id' => $this->currentUser['user_id']]);
        $this->success('Inventory updated');
    }

    public function adjust(int $id): void {
        $this->requireAnyRole(['admin', 'manager', 'warehouse_staff']);
        $data = $this->getJsonInput();
        $item = $this->inventoryModel->findById($id);
        if (!$item) $this->notFound('Inventory record not found');

        $newQuantity = (int)($data['quantity'] ?? $data['quantity_on_hand'] ?? 0);
        $type = $data['type'] ?? 'recount';
        $reason = $data['reason'] ?? null;

        $this->inventoryModel->adjustStock($item['product_id'], $newQuantity, $type, $reason);
        Logger::info("Stock adjusted", ['product_id' => $item['product_id'], 'type' => $type, 'user_id' => $this->currentUser['user_id']]);
        $this->success('Stock adjusted');
    }

    public function lowStock(): void {
        $this->requireAuth();
        $items = $this->inventoryModel->getLowStock();
        $this->success('Low stock items', $items);
    }

    public function movement(): void {
        $this->requireAuth();
        $productId = $this->getInt('product_id', 0);
        $limit = $this->getInt('limit', 50);
        $movements = $this->inventoryModel->getStockMovement($productId, $limit);
        $this->success('Stock movements', $movements);
    }
}
