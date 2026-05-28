<?php
namespace Core;

abstract class Model {
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];
    protected bool $softDelete = false;
    protected string $deletedAtColumn = 'deleted_at';

    public function __construct() {
        if (empty($this->table)) {
            $this->table = $this->getTableName();
        }
    }

    protected function getTableName(): string {
        $className = (new \ReflectionClass($this))->getShortName();
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $className)) . 's';
    }

    public function getAll(array $columns = ['*'], array $options = []): array {
        $select = implode(', ', $columns);
        $query = Database::table($this->table)->select($select);

        if (isset($options['where'])) {
            foreach ($options['where'] as $condition) {
                call_user_func_array([$query, 'where'], $condition);
            }
        }
        if (isset($options['order_by'])) {
            foreach ($options['order_by'] as $order) {
                $query->orderBy($order[0], $order[1] ?? 'ASC');
            }
        }
        if (isset($options['limit'])) {
            $query->limit($options['limit'], $options['offset'] ?? null);
        }
        if ($this->softDelete) {
            $query->whereNull($this->deletedAtColumn);
        }

        return $query->get();
    }

    public function findById(int $id): ?array {
        return Database::table($this->table)->where($this->primaryKey, $id)->first();
    }

    public function findWhere(array $conditions): ?array {
        $query = Database::table($this->table);
        foreach ($conditions as $column => $value) { $query->where($column, $value); }
        return $query->first();
    }

    public function findIn(string $column, array $values): array {
        if (empty($values)) return [];
        return Database::table($this->table)->whereIn($column, $values)->get();
    }

    public function create(array $data): int {
        $filteredData = $this->filterFillable($data);
        if (!isset($filteredData['created_at'])) $filteredData['created_at'] = date('Y-m-d H:i:s');
        if (!isset($filteredData['updated_at'])) $filteredData['updated_at'] = date('Y-m-d H:i:s');

        $id = Database::table($this->table)->insert($filteredData);
        $this->logAudit('CREATE', $id, null, $filteredData);
        return $id;
    }

    public function update(int $id, array $data): int {
        $oldData = $this->findById($id);
        if (!$oldData) throw new NotFoundException("Record not found");

        $filteredData = $this->filterFillable($data);
        $filteredData['updated_at'] = date('Y-m-d H:i:s');

        $affected = Database::table($this->table)->where($this->primaryKey, $id)->update($filteredData);
        $this->logAudit('UPDATE', $id, $oldData, $filteredData);
        return $affected;
    }

    public function delete(int $id): int {
        if ($this->softDelete) {
            return Database::table($this->table)->where($this->primaryKey, $id)->update([$this->deletedAtColumn => date('Y-m-d H:i:s')]);
        }
        $oldData = $this->findById($id);
        $affected = Database::table($this->table)->where($this->primaryKey, $id)->delete();
        $this->logAudit('DELETE', $id, $oldData, null);
        return $affected;
    }

    public function count(array $conditions = []): int {
        $query = Database::table($this->table);
        foreach ($conditions as $column => $value) { $query->where($column, $value); }
        if ($this->softDelete) $query->whereNull($this->deletedAtColumn);
        return $query->count();
    }

    public function paginate(int $page = 1, int $perPage = 20, array $options = []): array {
        $offset = ($page - 1) * $perPage;
        $options['limit'] = $perPage;
        $options['offset'] = $offset;
        if (!isset($options['order_by'])) $options['order_by'] = [[$this->primaryKey, 'DESC']];

        $total = $this->count($options['where'] ?? []);
        $data = $this->getAll(['*'], $options);

        return [
            'data'       => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => ceil($total / $perPage),
            ],
        ];
    }

    protected function filterFillable(array $data): array {
        if (empty($this->fillable)) return $data;
        return array_intersect_key($data, array_flip($this->fillable));
    }

    protected function castAttribute(string $key, mixed $value): mixed {
        if (!isset($this->casts[$key])) return $value;
        return match ($this->casts[$key]) {
            'int', 'integer' => (int)$value,
            'float'          => (float)$value,
            'bool', 'boolean' => (bool)$value,
            'array'          => json_decode($value, true) ?? [],
            'json'           => json_decode($value, true) ?? $value,
            'date'           => $value ? date('Y-m-d', strtotime($value)) : null,
            'datetime'       => $value ? date('Y-m-d H:i:s', strtotime($value)) : null,
            default          => $value,
        };
    }

    protected function toCastedArray(array $data): array {
        $result = [];
        foreach ($data as $key => $value) { $result[$key] = $this->castAttribute($key, $value); }
        return $result;
    }

    protected function hideAttributes(array $data): array {
        if (empty($this->hidden)) return $data;
        return array_diff_key($data, array_flip($this->hidden));
    }

    protected function logAudit(string $action, int $entityId, ?array $oldData, ?array $newData): void {
        try {
            Database::table('audit_logs')->insert([
                'user_id'    => $_SESSION['user_id'] ?? null,
                'action'     => $action,
                'entity_type' => $this->table,
                'entity_id'  => $entityId,
                'old_values' => $oldData ? json_encode($oldData) : null,
                'new_values' => $newData ? json_encode($newData) : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            Logger::error("Failed to log audit: " . $e->getMessage());
        }
    }

    protected function executeRaw(string $sql, array $bindings = []): \mysqli_result|bool {
        $conn = Database::getConnection();
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new DatabaseException("Prepare failed: " . $conn->error);
        if (!empty($bindings)) {
            $stmt->bind_param(str_repeat('s', count($bindings)), ...$bindings);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }

    public static function __callStatic(string $method, array $args) {
        $instance = new static();
        if (str_starts_with($method, 'findBy')) {
            $column = strtolower(substr($method, 6));
            return Database::table($instance->table)->where($column, $args[0])->first();
        }
        if (str_starts_with($method, 'getBy')) {
            $column = strtolower(substr($method, 5));
            return Database::table($instance->table)->where($column, $args[0])->get();
        }
        throw new \Exception("Method $method does not exist");
    }
}
