<?php
namespace Inventory;

use Core\Model;
use Core\Database;

class CategoryModel extends Model {
    protected string $table = 'categories';
    protected string $primaryKey = 'category_id';
    protected array $fillable = ['name', 'description'];

    public function getWithProductCount(): array {
        $sql = "SELECT c.*, COUNT(p.product_id) as product_count
                FROM categories c
                LEFT JOIN products p ON LOWER(p.category) = LOWER(c.name)
                GROUP BY c.category_id, c.name, c.description
                ORDER BY c.name ASC";
        return $this->executeRaw($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function findByName(string $name): ?array {
        return Database::table($this->table)
            ->whereRaw("LOWER(name) = LOWER(?)", [$name])
            ->first();
    }
}
