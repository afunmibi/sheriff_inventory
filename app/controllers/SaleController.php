<?php
/**
 * Sale Controller
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../models/Inventory.php';

class SaleController extends BaseController {
    private Sale $saleModel;

    public function __construct() {
        parent::__construct();
        $this->saleModel = new Sale();
    }

    public function index(): void {
        $this->requireAuth();
        
        $page = $this->getInt('page', 1);
        $limit = $this->getInt('limit', 20);
        $paymentStatus = $this->getInput('payment_status');
        $paymentMethod = $this->getInput('payment_method');
        
        $filters = [];
        if ($paymentStatus) $filters['payment_status'] = $paymentStatus;
        if ($paymentMethod) $filters['payment_method'] = $paymentMethod;
        
        $result = $this->saleModel->getAllSales($filters, $page, $limit);
        
        $this->successResponse('Sales retrieved', $result);
    }

    public function show(int $id): void {
        $this->requireAuth();
        
        $sale = $this->saleModel->getSaleById($id);
        
        if (!$sale) {
            $this->notFoundResponse('Sale not found');
        }
        
        $this->successResponse('Sale retrieved', $sale);
    }

    public function store(): void {
        $this->requireAnyRole(['admin', 'manager', 'cashier']);
        
        $data = $this->getJsonInput();
        
        $errors = $this->validateRequired($data, ['product_id', 'quantity_sold', 'unit_price', 'payment_method']);
        
        if (!empty($errors)) {
            $this->validationError('Validation failed', $errors);
        }
        
        $user = $this->requireAuth();
        $data['cashier_id'] = $user['user_id'];
        $data['payment_status'] = 'completed';
        
        $saleId = $this->saleModel->recordSale($data);
        
        $this->createdResponse('Sale recorded successfully', ['sale_id' => $saleId]);
    }

    public function daily(): void {
        $this->requireAuth();
        
        $date = $this->getInput('date', date('Y-m-d'));
        $summary = $this->saleModel->getDailySalesSummary($date);
        
        $this->successResponse('Daily summary', $summary);
    }

    public function monthly(): void {
        $this->requireAuth();
        
        $year = $this->getInt('year', (int)date('Y'));
        $month = $this->getInt('month', (int)date('m'));
        
        $summary = $this->saleModel->getMonthlySalesSummary($year, $month);
        
        $this->successResponse('Monthly summary', $summary);
    }

    public function recent(): void {
        $this->requireAuth();
        
        $limit = $this->getInt('limit', 10);
        $sales = $this->saleModel->getRecentSales($limit);
        
        $this->successResponse('Recent sales', $sales);
    }

    public function topProducts(): void {
        $this->requireAuth();
        
        $days = $this->getInt('days', 30);
        $limit = $this->getInt('limit', 5);
        
        $products = $this->saleModel->getTopProducts($days, $limit);
        
        $this->successResponse('Top products', $products);
    }
}