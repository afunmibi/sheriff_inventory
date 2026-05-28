<?php
/**
 * Purchase Order Controller
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/PurchaseOrder.php';

class PurchaseOrderController extends BaseController {
    private PurchaseOrder $model;

    public function __construct() {
        parent::__construct();
        $this->model = new PurchaseOrder();
    }

    public function index(): void {
        $this->requireAuth();
        $page = $this->getInt('page', 1);
        $limit = $this->getInt('limit', 20);
        $filters = [];
        
        if ($this->getInput('status')) {
            $filters['status'] = $this->getInput('status');
        }
        
        $result = $this->model->getAllPOs($filters, $page, $limit);
        $this->successResponse('Purchase orders retrieved', $result);
    }

    public function show(int $id): void {
        $this->requireAuth();
        $po = $this->model->getPOById($id);
        
        if (!$po) {
            $this->notFoundResponse('Purchase order not found');
        }
        
        $po['items'] = $this->model->getPOItems($id);
        $this->successResponse('PO retrieved', $po);
    }

    public function store(): void {
        $this->requireAnyRole(['admin', 'manager']);
        $data = $this->getJsonInput();
        
        $errors = $this->validateRequired($data, ['supplier_id', 'items']);
        if (!empty($errors)) {
            $this->validationError('Validation failed', $errors);
        }
        
        $id = $this->model->createPO($data, $data['items']);
        $this->createdResponse('PO created', ['po_id' => $id]);
    }

    public function update(int $id): void {
        $this->requireAnyRole(['admin', 'manager']);
        
        $this->model->updatePO($id, $this->getJsonInput());
        $this->successResponse('PO updated');
    }

    public function submit(int $id): void {
        $user = $this->requireAnyRole(['admin', 'manager']);
        $this->model->submitPO($id, $user['user_id']);
        $this->successResponse('PO submitted');
    }

    public function approve(int $id): void {
        $user = $this->requireAnyRole(['admin', 'manager']);
        $this->model->approvePO($id, $user['user_id']);
        $this->successResponse('PO approved');
    }

    public function receive(int $id): void {
        $this->requireAnyRole(['admin', 'manager', 'warehouse_staff']);
        $items = $this->getJsonInput();
        
        $this->model->receivePO($id, $items);
        $this->successResponse('PO received');
    }

    public function pending(): void {
        $this->requireAuth();
        $pos = $this->model->getPendingPOs();
        $this->successResponse('Pending POs', $pos);
    }
}
