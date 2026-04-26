<?php
/**
 * Inventory Controller
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Inventory.php';
require_once __DIR__ . '/../models/Product.php';

class InventoryController extends BaseController {
    private Inventory $inventoryModel;

    public function __construct() {
        parent::__construct();
        $this->inventoryModel = new Inventory();
    }

    public function index(): void {
        $this->requireAuth();
        
        $category = $this->getInput('category');
        $status = $this->getInput('status');
        $search = $this->getInput('search');
        
        $filters = [];
        if ($category) $filters['category'] = $category;
        if ($status) $filters['status'] = $status;
        if ($search) $filters['search'] = $search;
        
        $inventory = $this->inventoryModel->getAllInventory($filters);
        
        $this->successResponse('Inventory retrieved', $inventory);
    }

    public function show(int $productId): void {
        $this->requireAuth();
        
        $stock = $this->inventoryModel->getStockLevel($productId);
        
        if (!$stock) {
            $this->notFoundResponse('Stock not found');
        }
        
        $this->successResponse('Stock retrieved', $stock);
    }

    public function update(int $productId): void {
        $this->requireAnyRole(['admin', 'manager', 'warehouse_staff']);
        
        $data = $this->getJsonInput();
        
        $errors = $this->validateRequired($data, ['quantity']);
        
        if (!empty($errors)) {
            $this->validationError('Validation failed', $errors);
        }
        
        $quantity = (int)$data['quantity'];
        
        if ($quantity < 0) {
            $this->validationError('Invalid quantity', ['quantity' => 'Quantity must be positive']);
        }
        
        $this->inventoryModel->updateStock($productId, $quantity);
        
        $this->successResponse('Stock updated successfully');
    }

    public function adjust(int $productId): void {
        $this->requireAnyRole(['admin', 'manager', 'warehouse_staff']);
        
        $data = $this->getJsonInput();
        
        $errors = $this->validateRequired($data, ['quantity', 'type']);
        
        if (!empty($errors)) {
            $this->validationError('Validation failed', $errors);
        }
        
        $validTypes = ['recount', 'damage', 'loss', 'return', 'stock_take', 'bonus'];
        
        if (!in_array($data['type'], $validTypes)) {
            $this->validationError('Invalid adjustment type', ['type' => 'Invalid type']);
        }
        
        $user = $this->requireAuth();
        $userId = $user['user_id'];
        
        $newQuantity = $this->inventoryModel->adjustStock(
            $productId,
            (int)$data['quantity'],
            $data['type'],
            $data['reason'] ?? 'Adjustment',
            $userId
        );
        
        $this->successResponse('Stock adjusted', ['new_quantity' => $newQuantity]);
    }

    public function lowStock(): void {
        $this->requireAuth();
        
        $alerts = $this->inventoryModel->getLowStockAlerts();
        
        $this->successResponse('Low stock alerts', $alerts);
    }

    public function movement(): void {
        $this->requireAuth();
        
        $days = $this->getInt('days', 30);
        $movement = $this->inventoryModel->getStockMovement($days);
        
        $this->successResponse('Stock movement', $movement);
    }
}