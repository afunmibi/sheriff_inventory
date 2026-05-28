<?php
namespace Ecommerce;

use Core\Config;
use Core\Controller;
use Core\Database;
use Helpers\Mailer;

class StorefrontController extends Controller {
    public function products(): void {
        $sql = "SELECT p.uuid, p.product_name, p.sku, p.selling_price, p.image_url,
                       COALESCE(i.quantity_available, 0) as available_stock,
                       COALESCE(i.status, 'out_of_stock') as stock_status
                FROM products p
                LEFT JOIN inventory i ON p.product_id = i.product_id
                WHERE p.is_active = 1 AND p.is_featured = 1
                ORDER BY p.product_name ASC";
        $products = Database::getConnection()->query($sql)->fetch_all(MYSQLI_ASSOC);
        $this->success('Storefront products', $products);
    }

    public function product(string $uuid): void {
        $sql = "SELECT p.*, COALESCE(i.quantity_available, 0) as available_stock,
                       COALESCE(i.status, 'out_of_stock') as stock_status
                FROM products p
                LEFT JOIN inventory i ON p.product_id = i.product_id
                WHERE p.uuid = ? AND p.is_active = 1";
        $stmt = Database::prepare($sql);
        $stmt->bind_param('s', $uuid);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$result) $this->notFound('Product not found');

        // Fetch gallery images
        $imgSql = "SELECT image_url, label, sort_order FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, image_id ASC";
        $imgStmt = Database::prepare($imgSql);
        $imgStmt->bind_param('i', $result['product_id']);
        $imgStmt->execute();
        $imgResult = $imgStmt->get_result();
        $gallery = [];
        while ($row = $imgResult->fetch_assoc()) {
            $gallery[] = $row;
        }
        $imgStmt->close();
        $result['gallery'] = $gallery;

        // Build price range display
        if (!empty($result['min_price']) && !empty($result['max_price'])) {
            $result['price_range'] = '₦' . number_format((float)$result['min_price']) . ' - ₦' . number_format((float)$result['max_price']);
        } elseif (!empty($result['selling_price'])) {
            $result['price_range'] = '₦' . number_format((float)$result['selling_price']);
        } else {
            $result['price_range'] = '₦' . number_format((float)$result['min_price'] ?: 0);
        }

        $this->success('Product details', $result);
    }

    public function placeOrder(): void {
        $data = $this->getJsonInput();
        $errors = $this->validateRequired($data, ['product_uuid', 'quantity', 'customer_name', 'customer_phone']);
        if (!empty($errors)) $this->validationError('Validation failed', $errors);

        $product = Database::table('products')
            ->where('uuid', $data['product_uuid'])
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
                'payment_method' => 'bank_transfer',
                'payment_status' => 'pending',
                'customer_name'  => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? '',
                'source'         => 'web',
                'notes'          => $data['notes'] ?? '',
                'sale_date'      => date('Y-m-d H:i:s'),
            ]);

            Database::getConnection()->commit();
            $this->success('Order placed', [
                'invoice_number' => $invoiceNumber,
                'total'          => $totalAmount,
                'product'        => $product['product_name'],
                'quantity'       => $quantity,
            ]);
        } catch (\Exception $e) {
            Database::getConnection()->rollback();
            $this->error('Failed to place order: ' . $e->getMessage());
        }
    }

    public function registerCustomer(): void
    {
        $data = $this->getJsonInput();
        $errors = $this->validateRequired($data, ['email', 'password', 'name']);
        if (!empty($errors)) $this->validationError('Validation failed', $errors);

        $email = trim($data['email']);
        $password = $data['password'];

        $existing = Database::table('store_customers')->where('email', $email)->first();
        if ($existing) $this->validationError('Email already registered', ['email' => 'This email is already registered']);

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        Database::table('store_customers')->insert([
            'email'         => $email,
            'password_hash' => $passwordHash,
            'name'          => trim($data['name']),
            'phone'         => $data['phone'] ?? '',
            'address'       => $data['address'] ?? '',
            'is_active'     => 1,
        ]);

        $this->created('Customer registered successfully', ['email' => $email]);
    }

    public function sendOrderEmail(): void
    {
        $data = $this->getJsonInput();
        $errors = $this->validateRequired($data, ['name', 'phone', 'items', 'total']);
        if (!empty($errors)) $this->validationError('Validation failed', $errors);

        $businessEmail = Config::get('mail.business_email', 'akintundesheriff09@gmail.com');
        $customerEmail = $data['customer_email'] ?? '';

        Mailer::sendOrderNotification($businessEmail, $customerEmail, $data);

        $this->success('Order email sent');
    }
}
