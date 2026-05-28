<?php
namespace Inventory;

use Core\Controller;

class ReturnController extends Controller {
    private SaleModel $saleModel;

    public function __construct() {
        parent::__construct();
        $this->saleModel = new SaleModel();
    }

    public function index(): void {
        $this->requireAnyRole(['admin', 'manager']);
        $sql = "SELECT sa.*, p.product_name, p.sku, u.name as adjusted_by_name
                FROM stock_adjustments sa
                JOIN products p ON sa.product_id = p.product_id
                LEFT JOIN users u ON sa.adjusted_by = u.user_id
                WHERE sa.type = 'return'
                ORDER BY sa.created_at DESC";
        $result = $this->saleModel->executeRaw($sql)->fetch_all(MYSQLI_ASSOC);
        $this->success('Returns retrieved', $result);
    }

    public function store(): void {
        $this->requireAnyRole(['admin', 'manager']);
        $data = $this->getJsonInput();
        $errors = $this->validateRequired($data, ['transaction_id', 'quantity']);
        if (!empty($errors)) $this->validationError('Validation failed', $errors);

        try {
            $this->saleModel->processReturn((int)$data['transaction_id'], (int)$data['quantity']);
            $this->success('Return processed');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
