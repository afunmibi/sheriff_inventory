<?php
/**
 * Audit Log Model
 */

require_once __DIR__ . '/BaseModel.php';

class AuditLog extends BaseModel {
    protected string $table = 'audit_logs';
    protected string $primaryKey = 'log_id';

    public function logAction(int $userId, string $action, string $entityType, ?int $entityId = null, ?array $oldValues = null, ?array $newValues = null): int {
        return DatabaseConnection::table($this->table)->insert([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function getAuditTrail(array $filters = [], int $page = 1, int $limit = 50): array {
        $sql = "SELECT al.*, u.name as user_name, u.email as user_email, u.role
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.user_id
                WHERE 1=1";
        
        $bindings = [];
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND al.user_id = ?";
            $bindings[] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $sql .= " AND al.action = ?";
            $bindings[] = $filters['action'];
        }
        
        if (!empty($filters['entity_type'])) {
            $sql .= " AND al.entity_type = ?";
            $bindings[] = $filters['entity_type'];
        }
        
        if (!empty($filters['entity_id'])) {
            $sql .= " AND al.entity_id = ?";
            $bindings[] = $filters['entity_id'];
        }
        
        if (!empty($filters['start_date'])) {
            $sql .= " AND al.created_at >= ?";
            $bindings[] = $filters['start_date'] . ' 00:00:00';
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND al.created_at <= ?";
            $bindings[] = $filters['end_date'] . ' 23:59:59';
        }
        
        $countSql = str_replace('SELECT al.*, u.name as user_name, u.email as user_email, u.role', 'SELECT COUNT(*) as total', $sql);
        
        $countResult = $this->executeRaw($countSql, $bindings);
        $total = (int)$countResult->fetch_assoc()['total'];
        
        $sql .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
        $bindings[] = $limit;
        $bindings[] = ($page - 1) * $limit;
        
        $result = $this->executeRaw($sql, $bindings);
        $data = $result->fetch_all(MYSQLI_ASSOC);
        
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'last_page' => ceil($total / $limit)
            ]
        ];
    }

    public function getUserActions(int $userId, int $limit = 20): array {
        return DatabaseConnection::table($this->table)
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    public function getEntityHistory(string $entityType, int $entityId): array {
        return DatabaseConnection::table($this->table)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('created_at', 'DESC')
            ->get();
    }
}
