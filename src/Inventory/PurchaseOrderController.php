<?php
namespace Inventory;

use Core\Controller;

class PurchaseOrderController extends Controller {
    private PurchaseOrderModel $poModel;

    public function __construct() {
        parent::__construct();
        $this->poModel = new PurchaseOrderModel();
    }

    public function index(): void {
        $this->requireAnyRole(['admin', 'manager']);
        $pos = $this->poModel->getAllWithDetails();
        $this->success('Purchase orders retrieved', ['data' => $pos]);
    }

    public function show(int $id): void {
        $this->requireAnyRole(['admin', 'manager']);
        $po = $this->poModel->getWithItems($id);
        if (!$po) $this->notFound('Purchase order not found');
        $this->success('Purchase order retrieved', $po);
    }

    public function store(): void {
        $this->requireAnyRole(['admin', 'manager']);
        $data = $this->getJsonInput();
        $errors = $this->validateRequired($data, ['supplier_id', 'items']);
        if (!empty($errors)) $this->validationError('Validation failed', $errors);

        if (!is_array($data['items']) || empty($data['items'])) {
            $this->validationError('At least one item is required');
        }

        try {
            $poId = $this->poModel->createWithItems($data, $data['items']);
            $this->created('Purchase order created', ['po_id' => $poId]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function update(int $id): void {
        $this->requireAnyRole(['admin', 'manager']);
        $data = $this->getJsonInput();
        $po = $this->poModel->findById($id);
        if (!$po) $this->notFound('Purchase order not found');
        $this->poModel->update($id, $data);
        $this->success('Purchase order updated');
    }

    public function submit(int $id): void {
        $this->requireAnyRole(['admin', 'manager']);
        $po = $this->poModel->findById($id);
        if (!$po) $this->notFound('Purchase order not found');
        $this->poModel->updateStatus($id, 'submitted');
        $this->success('Purchase order submitted');
    }

    public function approve(int $id): void {
        $this->requireRole('admin');
        $po = $this->poModel->findById($id);
        if (!$po) $this->notFound('Purchase order not found');
        $this->poModel->updateStatus($id, 'approved');
        $this->success('Purchase order approved');
    }

    public function receive(int $id): void {
        $this->requireAnyRole(['admin', 'manager']);
        $po = $this->poModel->findById($id);
        if (!$po) $this->notFound('Purchase order not found');

        $data = $this->getJsonInput();
        if (!isset($data['items']) || !is_array($data['items'])) {
            $this->validationError('Items array is required');
        }

        try {
            $this->poModel->receiveItems($id, $data['items']);
            $this->success('Items received');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function pending(): void {
        $this->requireAnyRole(['admin', 'manager']);
        $sql = "SELECT po.*, s.company_name as supplier_name
                FROM purchase_orders po
                JOIN suppliers s ON po.supplier_id = s.supplier_id
                WHERE po.status NOT IN ('received', 'cancelled')
                ORDER BY po.created_at DESC";
        $result = $this->poModel->executeRaw($sql)->fetch_all(MYSQLI_ASSOC);
        $this->success('Pending purchase orders', $result);
    }
}
