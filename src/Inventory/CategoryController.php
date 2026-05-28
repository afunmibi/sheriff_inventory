<?php
namespace Inventory;

use Core\Controller;

class CategoryController extends Controller {
    private CategoryModel $categoryModel;

    public function __construct() {
        parent::__construct();
        $this->categoryModel = new CategoryModel();
    }

    public function index(): void {
        $this->requireAuth();
        $categories = $this->categoryModel->getWithProductCount();
        $this->success('Categories retrieved', ['data' => $categories]);
    }

    public function store(): void {
        $this->requireAnyRole(['admin', 'manager']);
        $data = $this->getJsonInput();
        $errors = $this->validateRequired($data, ['name']);
        if (!empty($errors)) $this->validationError('Validation failed', $errors);

        $existing = $this->categoryModel->findByName($data['name']);
        if ($existing) $this->validationError('Category already exists', ['name' => 'This category name is already taken']);

        $id = $this->categoryModel->create($data);
        $this->created('Category created', ['category_id' => $id]);
    }

    public function update(int $id): void {
        $this->requireAnyRole(['admin', 'manager']);
        $data = $this->getJsonInput();
        $category = $this->categoryModel->findById($id);
        if (!$category) $this->notFound('Category not found');
        $this->categoryModel->update($id, $data);
        $this->success('Category updated');
    }

    public function destroy(int $id): void {
        $this->requireRole('admin');
        $category = $this->categoryModel->findById($id);
        if (!$category) $this->notFound('Category not found');
        $this->categoryModel->delete($id);
        $this->success('Category deleted');
    }
}
