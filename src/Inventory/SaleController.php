<?php
namespace Inventory;

use Core\Controller;
use Core\Database;
use Core\Logger;

class SaleController extends Controller {
    private SaleModel $saleModel;

    public function __construct() {
        parent::__construct();
        $this->saleModel = new SaleModel();
    }

    public function index(): void {
        $this->requireAnyRole(['admin', 'manager', 'cashier']);
        $page = $this->getInt('page', 1);
        $limit = $this->getInt('limit', 20);
        $result = $this->saleModel->paginate($page, $limit);
        $this->success('Sales retrieved', $result);
    }

    public function show(int $id): void {
        $this->requireAnyRole(['admin', 'manager', 'cashier']);
        $sale = $this->saleModel->findById($id);
        if (!$sale) $this->notFound('Sale not found');

        $sql = "SELECT p.product_name, p.sku FROM products p WHERE p.product_id = ?";
        $productResult = Database::table('products')->where('product_id', $sale['product_id'])->first();
        $sale['product'] = $productResult;

        $this->success('Sale retrieved', $sale);
    }

    public function store(): void {
        $this->requireAnyRole(['admin', 'manager', 'cashier']);
        $data = $this->getJsonInput();

        $errors = $this->validateRequired($data, ['product_id', 'quantity_sold', 'unit_price']);
        if (!empty($errors)) $this->validationError('Validation failed', $errors);

        $data['total_amount'] = $data['quantity_sold'] * $data['unit_price'];
        $data['source'] = $data['source'] ?? 'pos';

        try {
            $id = $this->saleModel->recordSale($data);
            Logger::info("Sale recorded", ['sale_id' => $id, 'user_id' => $this->currentUser['user_id']]);
            $this->created('Sale recorded', ['transaction_id' => $id]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function daily(): void {
        $this->requireAuth();
        $date = $this->getInput('date', date('Y-m-d'));
        $summary = $this->saleModel->getDailySales($date);
        $this->success('Daily sales summary', $summary);
    }

    public function monthly(): void {
        $this->requireAuth();
        $year = $this->getInt('year', (int)date('Y'));
        $month = $this->getInt('month', (int)date('m'));
        $summary = $this->saleModel->getMonthlySales($year, $month);
        $this->success('Monthly sales summary', $summary);
    }

    public function recent(): void {
        $this->requireAuth();
        $limit = $this->getInt('limit', 10);
        $sales = $this->saleModel->getRecentSales($limit);
        $this->success('Recent sales', $sales);
    }

    public function topProducts(): void {
        $this->requireAuth();
        $limit = $this->getInt('limit', 10);
        $startDate = $this->getInput('start_date', '');
        $endDate = $this->getInput('end_date', '');
        $products = $this->saleModel->getTopProducts($limit, $startDate, $endDate);
        $this->success('Top products', $products);
    }
}
