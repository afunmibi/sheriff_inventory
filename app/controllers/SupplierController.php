<?php
/**
 * Supplier Controller
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Supplier.php';

class SupplierController extends BaseController {
    private Supplier $model;

    public function __construct() {
        parent::__construct();
        $this->model = new Supplier();
    }

    public function index(): void {
        $this->requireAuth();
        $page = $this->getInt('page', 1);
        $limit = $this->getInt('limit', 20);
        $filters = [];
        
        $result = $this->model->getAllSuppliers($filters, $page, $limit);
        $this->successResponse('Suppliers retrieved', $result);
    }

    public function show(int $id): void {
        $this->requireAuth();
        $supplier = $this->model->findById($id);
        
        if (!$supplier) {
            $this->notFoundResponse('Supplier not found');
        }
        
        $supplier['products'] = $this->model->getSupplierProducts($id);
        $supplier['performance'] = $this->model->getSupplierPerformance($id);
        
        $this->successResponse('Supplier retrieved', $supplier);
    }

    public function store(): void {
        $this->requireAnyRole(['admin', 'manager']);
        $data = $this->getJsonInput();
        
        $errors = $this->validateRequired($data, ['company_name']);
        if (!empty($errors)) {
            $this->validationError('Validation failed', $errors);
        }
        
        if (isset($data['account_number']) && !empty($data['account_number'])) {
            if (!$this->model->validateNUBAN($data['account_number'])) {
                $this->validationError('Invalid NUBAN', ['account_number' => 'Must be 10-11 digits']);
            }
        }
        
        $id = $this->model->create($data);
        $this->createdResponse('Supplier created', ['supplier_id' => $id]);
    }

    public function update(int $id): void {
        $this->requireAnyRole(['admin', 'manager']);
        $data = $this->getJsonInput();
        
        if (!$this->model->findById($id)) {
            $this->notFoundResponse('Supplier not found');
        }
        
        if (isset($data['account_number']) && !empty($data['account_number'])) {
            if (!$this->model->validateNUBAN($data['account_number'])) {
                $this->validationError('Invalid NUBAN', ['account_number' => 'Must be 10-11 digits']);
            }
        }
        
        $this->model->update($id, $data);
        $this->successResponse('Supplier updated');
    }

    public function destroy(int $id): void {
        $this->requireRole('admin');
        
        if (!$this->model->findById($id)) {
            $this->notFoundResponse('Supplier not found');
        }
        
        $this->model->update($id, ['status' => 'inactive']);
        $this->successResponse('Supplier deleted');
    }

    public function search(): void {
        $this->requireAuth();
        $query = $this->getInput('q', '');
        
        $results = $this->model->searchSuppliers($query);
        $this->successResponse('Search results', $results);
    }

    public function performance(int $id): void {
        $this->requireAuth();
        $metrics = $this->model->getSupplierPerformance($id);
        $this->successResponse('Performance metrics', $metrics);
    }
}