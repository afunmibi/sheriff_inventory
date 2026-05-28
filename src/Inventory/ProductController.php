<?php
namespace Inventory;

use Core\Controller;

class ProductController extends Controller {
    private ProductModel $productModel;

    public function __construct() {
        parent::__construct();
        $this->productModel = new ProductModel();
    }

    public function index(): void {
        $this->requireAuth();
        $page = $this->getInt('page', 1);
        $limit = $this->getInt('limit', 20);
        $category = $this->getInput('category');
        $search = $this->getInput('search');

        $filters = [];
        if ($category) $filters['category'] = $category;
        if ($search) $filters['search'] = $search;

        $result = $this->productModel->getAllProducts($filters, $page, $limit);
        $this->success('Products retrieved', $result);
    }

    public function show(int $id): void {
        $this->requireAuth();
        $product = $this->productModel->getProductWithStock($id);
        if (!$product) $this->notFound('Product not found');
        $product['suppliers'] = $this->productModel->getSuppliers($id);
        $this->success('Product retrieved', $product);
    }

    public function store(): void {
        $this->requireAnyRole(['admin', 'manager']);
        $data = $this->getJsonInput();
        $errors = $this->validateRequired($data, ['product_name', 'category', 'selling_price']);
        if (!empty($errors)) $this->validationError('Validation failed', $errors);

        if (isset($data['sku'])) {
            $existing = $this->productModel->getProductBySku($data['sku']);
            if ($existing) $this->validationError('SKU already exists', ['sku' => 'This SKU is already in use']);
        } else {
            $data['sku'] = $this->productModel->generateSku($data['category']);
        }

        if (!isset($data['uuid'])) $data['uuid'] = \generateUuid();
        if (!isset($data['cost_price'])) $data['cost_price'] = 0;
        if (!isset($data['reorder_level'])) $data['reorder_level'] = 10;

        $id = $this->productModel->create($data);
        $this->created('Product created', ['product_id' => $id]);
    }

    public function update(int $id): void {
        $this->requireAnyRole(['admin', 'manager']);
        $data = $this->getJsonInput();
        $product = $this->productModel->findById($id);
        if (!$product) $this->notFound('Product not found');

        if (isset($data['sku']) && $data['sku'] !== $product['sku']) {
            $existing = $this->productModel->getProductBySku($data['sku']);
            if ($existing) $this->validationError('SKU already exists', ['sku' => 'This SKU is already in use']);
        }

        $this->productModel->update($id, $data);
        $this->success('Product updated');
    }

    public function destroy(int $id): void {
        $this->requireRole('admin');
        $product = $this->productModel->findById($id);
        if (!$product) $this->notFound('Product not found');
        $this->productModel->update($id, ['is_active' => 0]);
        $this->success('Product deleted');
    }

    public function search(): void {
        $this->requireAuth();
        $query = $this->getInput('q', '');
        $limit = $this->getInt('limit', 20);
        if (strlen($query) < 2) $this->error('Search query must be at least 2 characters');
        $products = $this->productModel->searchProducts($query, $limit);
        $this->success('Search results', $products);
    }

    public function byCategory(string $category): void {
        $this->requireAuth();
        $products = $this->productModel->getProductsByCategory($category);
        $this->success('Products retrieved', $products);
    }

    public function active(): void {
        $this->requireAuth();
        $products = $this->productModel->getActiveProducts();
        $this->success('Products retrieved', $products);
    }

    public function stats(): void {
        $this->requireAuth();
        $totalProducts = $this->productModel->getTotalProductCount();
        $totalValue = $this->productModel->getTotalInventoryValue();
        $this->success('Product stats', [
            'total_products'       => $totalProducts,
            'total_inventory_value' => $totalValue,
        ]);
    }

    public function uploadImage(): void {
        $this->requireAnyRole(['admin', 'manager']);
        $id = (int)($this->getInput('id') ?: $_GET['id'] ?? 0);

        if ($id) {
            $product = $this->productModel->findById($id);
            if (!$product) $this->notFound('Product not found');
        }

        if (!isset($_FILES['image'])) $this->error('No image file provided');
        $file = $_FILES['image'];
        if ($file['error'] !== UPLOAD_ERR_OK) $this->error('File upload error');

        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset($allowedTypes[$mimeType])) $this->error('Invalid file type');

        $maxSize = \Core\Config::get('upload.max_size', 10 * 1024 * 1024);
        if ($file['size'] > $maxSize) $this->error('File size must be less than ' . round($maxSize / 1024 / 1024) . 'MB');

        $filename = ($id ?: time()) . '_' . bin2hex(random_bytes(8)) . '.' . $allowedTypes[$mimeType];
        $uploadDir = \Core\Config::get('upload.path', dirname(__DIR__, 2) . '/public/uploads/') . 'products/';

        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) $this->error('Failed to upload image');

        $imageUrl = '/uploads/products/' . $filename;
        if ($id) $this->productModel->update($id, ['image_url' => $imageUrl]);

        $this->success('Image uploaded', ['url' => $imageUrl]);
    }
}
