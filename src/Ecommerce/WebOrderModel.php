<?php
namespace Ecommerce;

use Core\Model;
use Core\Database;

class WebOrderModel extends Model {
    protected string $table = 'sales_transactions';
    protected string $primaryKey = 'transaction_id';
    protected array $fillable = [
        'invoice_number', 'product_id', 'quantity_sold', 'unit_price',
        'total_amount', 'payment_method', 'payment_status',
        'customer_name', 'customer_phone', 'customer_email',
        'source', 'notes', 'sale_date',
    ];

    public function getWebOrders(int $page = 1, int $limit = 20): array {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT st.*, p.product_name, p.sku, p.image_url
                FROM sales_transactions st
                JOIN products p ON st.product_id = p.product_id
                WHERE st.source = 'web'
                ORDER BY st.sale_date DESC
                LIMIT ? OFFSET ?";
        $orders = $this->executeRaw($sql, [$limit, $offset])->fetch_all(MYSQLI_ASSOC);
        $total = Database::table($this->table)->where('source', 'web')->count();

        return [
            'data'       => $orders,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $limit,
                'total'        => $total,
                'last_page'    => ceil($total / $limit),
            ],
        ];
    }
}
