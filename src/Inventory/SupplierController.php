<?php
namespace Inventory;

use Core\Controller;

class SupplierController extends Controller {
    private SupplierModel $supplierModel;

    public function __construct() {
        parent::__construct();
        $this->supplierModel = new SupplierModel();
    }

    public function index(): void {
        $this->requireAnyRole(['admin', 'manager']);
        $suppliers = $this->supplierModel->getWithProductCount();
        $this->success('Suppliers retrieved', ['data' => $suppliers]);
    }

    public function show(int $id): void {
        $this->requireAnyRole(['admin', 'manager']);
        $supplier = $this->supplierModel->getSupplierWithProducts($id);
        if (!$supplier) $this->notFound('Supplier not found');
        $this->success('Supplier retrieved', $supplier);
    }

    public function store(): void {
        $this->requireAnyRole(['admin', 'manager']);
        $data = $this->getJsonInput();
        $errors = $this->validateRequired($data, ['company_name']);
        if (!empty($errors)) $this->validationError('Validation failed', $errors);

        if (!isset($data['status'])) $data['status'] = 'active';
        $id = $this->supplierModel->create($data);
        $this->created('Supplier created', ['supplier_id' => $id]);
    }

    public function update(int $id): void {
        $this->requireAnyRole(['admin', 'manager']);
        $data = $this->getJsonInput();
        $supplier = $this->supplierModel->findById($id);
        if (!$supplier) $this->notFound('Supplier not found');
        $this->supplierModel->update($id, $data);
        $this->success('Supplier updated');
    }

    public function destroy(int $id): void {
        $this->requireRole('admin');
        $supplier = $this->supplierModel->findById($id);
        if (!$supplier) $this->notFound('Supplier not found');
        $this->supplierModel->update($id, ['status' => 'inactive']);
        $this->success('Supplier deleted');
    }

    public function search(): void {
        $this->requireAnyRole(['admin', 'manager']);
        $query = $this->getInput('q', '');
        $limit = $this->getInt('limit', 20);
        if (strlen($query) < 2) $this->error('Search query must be at least 2 characters');
        $suppliers = $this->supplierModel->search($query, $limit);
        $this->success('Search results', $suppliers);
    }

    public function performance(int $id): void {
        $this->requireAnyRole(['admin', 'manager']);
        $supplier = $this->supplierModel->findById($id);
        if (!$supplier) $this->notFound('Supplier not found');
        $performance = $this->supplierModel->getPerformance($id);
        $this->success('Supplier performance', $performance);
    }
}
