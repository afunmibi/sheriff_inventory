<?php
/**
 * User Model
 */

require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel {
    protected string $table = 'users';
    protected string $primaryKey = 'user_id';
    protected array $fillable = ['name', 'email', 'phone', 'password_hash', 'role', 'permissions', 'is_active', 'department'];
    protected array $hidden = ['password_hash'];
    protected array $casts = ['permissions' => 'json', 'is_active' => 'bool'];

    public const ROLES = ['admin', 'manager', 'cashier', 'warehouse_staff'];

    public function getAllUsers(array $filters = [], int $page = 1, int $limit = 20): array {
        $options = ['order_by' => [['name', 'ASC']]];
        
        $conditions = [];
        
        if (isset($filters['role'])) {
            $conditions[] = ['role', '=', $filters['role']];
        }
        
        if (isset($filters['is_active'])) {
            $conditions[] = ['is_active', '=', $filters['is_active']];
        }
        
        if (!empty($conditions)) {
            $options['where'] = $conditions;
        }
        
        return $this->paginate($page, $limit, $options);
    }

    public function getUserByEmail(string $email): ?array {
        return DatabaseConnection::table($this->table)
            ->where('email', $email)
            ->first();
    }

    public function authenticate(string $email, string $password): ?array {
        $user = $this->getUserByEmail($email);
        
        if (!$user) {
            return null;
        }
        
        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }
        
        if (!$user['is_active']) {
            return null;
        }
        
        $this->updateLastLogin($user['user_id']);
        
        return $this->hideAttributes($user);
    }

    public function updateLastLogin(int $userId): void {
        DatabaseConnection::table($this->table)
            ->where('user_id', $userId)
            ->update(['last_login' => date('Y-m-d H:i:s')]);
    }

    public function changePassword(int $userId, string $newPassword): bool {
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        
        $affected = DatabaseConnection::table($this->table)
            ->where('user_id', $userId)
            ->update(['password_hash' => $passwordHash]);
        
        return $affected > 0;
    }

    public function validatePassword(string $password): array {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        if (!preg_match('/[!@#$%^&*]/', $password)) {
            $errors[] = "Password must contain at least one special character (!@#$%^&*)";
        }
        
        return $errors;
    }

    public function hasPermission(int $userId, string $permission): bool {
        $user = $this->findById($userId);
        
        if (!$user) {
            return false;
        }
        
        if ($user['role'] === 'admin') {
            return true;
        }
        
        $permissions = json_decode($user['permissions'] ?? '[]', true);
        
        return in_array($permission, $permissions, true);
    }

    public function hasRole(int $userId, string $role): bool {
        $user = $this->findById($userId);
        return $user && $user['role'] === $role;
    }

    public function hasAnyRole(int $userId, array $roles): bool {
        $user = $this->findById($userId);
        return $user && in_array($user['role'], $roles, true);
    }

    public static function getPermissionsByRole(string $role): array {
        $permissions = [
            'admin' => ['*'],
            'manager' => ['products.view', 'products.create', 'products.edit', 'inventory.view', 'inventory.adjust', 'suppliers.view', 'suppliers.create', 'suppliers.edit', 'po.view', 'po.create', 'po.approve', 'po.receive', 'sales.view', 'sales.create', 'sales.return', 'reports.view', 'users.view', 'users.edit'],
            'cashier' => ['products.view', 'inventory.view', 'sales.view', 'sales.create'],
            'warehouse_staff' => ['products.view', 'inventory.view', 'inventory.adjust']
        ];
        
        return $permissions[$role] ?? [];
    }

    public function getActiveUsers(): array {
        return DatabaseConnection::table($this->table)
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get();
    }

    public function createUser(array $data): int {
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
            unset($data['password']);
        }
        
        if (!isset($data['permissions']) && isset($data['role'])) {
            $data['permissions'] = json_encode(self::getPermissionsByRole($data['role']));
        }
        
        return $this->create($data);
    }

    public function updateUser(int $id, array $data): int {
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
            unset($data['password']);
        }
        
        return $this->update($id, $data);
    }
}
