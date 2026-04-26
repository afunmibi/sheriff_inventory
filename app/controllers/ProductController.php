<?php
/**
 * Product Controller
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../helpers/helpers.php';

class ProductController extends BaseController {
    private Product $productModel;

    public function __construct() {
        parent::__construct();
        $this->productModel = new Product();
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
        
        $this->successResponse('Products retrieved successfully', $result);
    }

    public function show(int $id): void {
        $this->requireAuth();
        
        $product = $this->productModel->getProductWithStock($id);
        
        if (!$product) {
            $this->notFoundResponse('Product not found');
        }
        
        $product['suppliers'] = $this->productModel->getSuppliers($id);
        
        $this->successResponse('Product retrieved successfully', $product);
    }

    public function store(): void {
        $this->requireAnyRole(['admin', 'manager']);
        
        $data = $this->getJsonInput();
        
        $errors = $this->validateRequired($data, ['product_name', 'category', 'selling_price']);
        
        if (!empty($errors)) {
            $this->validationError('Validation failed', $errors);
        }
        
        if (isset($data['sku'])) {
            $existing = $this->productModel->getProductBySku($data['sku']);
            if ($existing) {
                $this->validationError('SKU already exists', ['sku' => 'This SKU is already in use']);
            }
        } else {
            $data['sku'] = $this->productModel->generateSku($data['category']);
        }
        
        if (!isset($data['uuid'])) {
            $data['uuid'] = generateUuid();
        }
        
        if (!isset($data['cost_price'])) {
            $data['cost_price'] = 0;
        }
        
        if (!isset($data['reorder_level'])) {
            $data['reorder_level'] = 10;
        }
        
        $id = $this->productModel->create($data);
        
        $this->createdResponse('Product created successfully', ['product_id' => $id]);
    }

    public function update(int $id): void {
        $this->requireAnyRole(['admin', 'manager']);
        
        $data = $this->getJsonInput();
        
        $product = $this->productModel->findById($id);
        
        if (!$product) {
            $this->notFoundResponse('Product not found');
        }
        
        if (isset($data['sku']) && $data['sku'] !== $product['sku']) {
            $existing = $this->productModel->getProductBySku($data['sku']);
            if ($existing) {
                $this->validationError('SKU already exists', ['sku' => 'This SKU is already in use']);
            }
        }
        
        $this->productModel->update($id, $data);
        
        $this->successResponse('Product updated successfully');
    }

    public function destroy(int $id): void {
        $this->requireRole('admin');
        
        $product = $this->productModel->findById($id);
        
        if (!$product) {
            $this->notFoundResponse('Product not found');
        }
        
        $this->productModel->update($id, ['is_active' => 0]);
        
        $this->successResponse('Product deleted successfully');
    }

    public function search(): void {
        $this->requireAuth();
        
        $query = $this->getInput('q', '');
        $limit = $this->getInt('limit', 20);
        
        if (strlen($query) < 2) {
            $this->errorResponse('Search query must be at least 2 characters');
        }
        
        $products = $this->productModel->searchProducts($query, $limit);
        
        $this->successResponse('Search results retrieved', $products);
    }

    public function byCategory(string $category): void {
        $this->requireAuth();
        
        $products = $this->productModel->getProductsByCategory($category);
        
        $this->successResponse('Products retrieved successfully', $products);
    }

    public function active(): void {
        $this->requireAuth();
        
        $products = $this->productModel->getActiveProducts();
        
        $this->successResponse('Products retrieved successfully', $products);
    }

    public function uploadImage(int $id): void {
        $this->requireAnyRole(['admin', 'manager']);
        
        $product = $this->productModel->findById($id);
        
        if (!$product) {
            $this->notFoundResponse('Product not found');
        }
        
        if (!isset($_FILES['image'])) {
            $this->errorResponse('No image file provided');
        }
        
        $file = $_FILES['image'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errorResponse('File upload error: ' . $file['error']);
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            $this->errorResponse('Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed');
        }
        
        $maxSize = 10 * 1024 * 1024;
        
        if ($file['size'] > $maxSize) {
            $this->errorResponse('File size must be less than 10MB');
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $id . '_' . time() . '.' . $extension;
        
        $uploadDir = dirname(__DIR__, 2) . '/uploads/products/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filepath = $uploadDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            $this->errorResponse('Failed to upload image');
        }
        
        $imageUrl = '/uploads/products/' . $filename;
        
        $this->productModel->update($id, ['image_url' => $imageUrl]);
        
        $this->successResponse('Image uploaded successfully', ['image_url' => $imageUrl]);
    }

    public function stats(): void {
        $this->requireAuth();
        
        $totalProducts = $this->productModel->getTotalProductCount();
        $totalValue = $this->productModel->getTotalInventoryValue();
        
        $categories = $this->productModel->getCategories();
        $categoryCounts = [];
        
        foreach ($categories as $cat) {
            $categoryCounts[$cat] = count($this->productModel->getProductsByCategory($cat));
        }
        
        $this->successResponse('Product statistics retrieved', [
            'total_products' => $totalProducts,
            'total_inventory_value' => $totalValue,
            'categories' => $categoryCounts
        ]);
    }
}
