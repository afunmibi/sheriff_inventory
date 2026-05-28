<?php
namespace Ecommerce;

use Core\Controller;
use Core\Database;

class WebOrderController extends Controller {
    public function index(): void {
        $this->requireAuth();
        $sql = "SELECT st.*, p.product_name, p.sku
                FROM sales_transactions st
                JOIN products p ON st.product_id = p.product_id
                WHERE st.source = 'web'
                ORDER BY st.sale_date DESC";
        $orders = Database::getConnection()->query($sql)->fetch_all(MYSQLI_ASSOC);
        $this->success('Web orders', ['data' => $orders]);
    }

    public function store(): void {
        // Public endpoint - no auth required
        $data = $this->getJsonInput();
        $errors = $this->validateRequired($data, ['product_id', 'quantity', 'customer_name', 'customer_phone']);
        if (!empty($errors)) $this->validationError('Validation failed', $errors);

        $product = Database::table('products')
            ->where('product_id', (int)$data['product_id'])
            ->where('is_active', 1)
            ->first();

        if (!$product) $this->notFound('Product not found');

        $quantity = (int)$data['quantity'];
        $totalAmount = $quantity * (float)$product['selling_price'];
        $invoiceNumber = 'WEB-' . date('Ymd') . '-' . time();

        Database::getConnection()->begin_transaction();
        try {
            Database::table('sales_transactions')->insert([
                'invoice_number' => $invoiceNumber,
                'product_id'     => $product['product_id'],
                'quantity_sold'  => $quantity,
                'unit_price'     => $product['selling_price'],
                'total_amount'   => $totalAmount,
                'payment_method' => $data['payment_method'] ?? 'bank_transfer',
                'payment_status' => 'pending',
                'customer_name'  => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? '',
                'source'         => 'web',
                'notes'          => $data['notes'] ?? '',
                'sale_date'      => date('Y-m-d H:i:s'),
            ]);

            Database::getConnection()->commit();
            $this->created('Order placed', [
                'invoice_number' => $invoiceNumber,
                'total'          => $totalAmount,
            ]);
        } catch (\Exception $e) {
            Database::getConnection()->rollback();
            $this->error('Failed to place order');
        }
    }
}
