<?php
namespace Core;

class QueryBuilder {
    private string $table;
    private string $sql = '';
    private array $bindings = [];
    private string $select = '*';
    private array $where = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private string $join = '';
    private string $groupBy = '';
    private string $having = '';

    public function __construct(string $table) { $this->table = $table; }

    public function select(string $columns): self { $this->select = $columns; return $this; }

    public function where(string $column, $operator, $value = null): self {
        if ($value === null) { $value = $operator; $operator = '='; }
        $this->where[] = "$column $operator ?";
        $this->bindings[] = $value;
        return $this;
    }

    public function orWhere(string $column, $operator, $value = null): self {
        if ($value === null) { $value = $operator; $operator = '='; }
        $this->where[] = "OR $column $operator ?";
        $this->bindings[] = $value;
        return $this;
    }

    public function whereRaw(string $condition, array $bindings = []): self {
        $this->where[] = $condition;
        $this->bindings = array_merge($this->bindings, $bindings);
        return $this;
    }

    public function whereIn(string $column, array $values): self {
        if (empty($values)) return $this;
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->where[] = "$column IN ($placeholders)";
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function whereNull(string $column): self { $this->where[] = "$column IS NULL"; return $this; }
    public function whereNotNull(string $column): self { $this->where[] = "$column IS NOT NULL"; return $this; }

    public function orderBy(string $column, string $direction = 'ASC'): self {
        $this->orderBy[] = "$column " . strtoupper($direction);
        return $this;
    }

    public function limit(int $limit, ?int $offset = null): self { $this->limit = $limit; $this->offset = $offset; return $this; }
    public function offset(int $offset): self { $this->offset = $offset; return $this; }

    public function join(string $table, string $on, string $type = 'INNER'): self {
        $this->join .= " $type JOIN $table ON $on";
        return $this;
    }

    public function leftJoin(string $table, string $on): self { return $this->join($table, $on, 'LEFT'); }
    public function groupBy(string $column): self { $this->groupBy = "GROUP BY $column"; return $this; }
    public function having(string $condition): self { $this->having = "HAVING $condition"; return $this; }

    public function get(): array {
        $this->buildSelect();
        $conn = Database::getConnection();
        $stmt = $conn->prepare($this->sql);
        if (!$stmt) throw new \Exception("Prepare failed: " . $conn->error);
        if (!empty($this->bindings)) {
            $stmt->bind_param(str_repeat('s', count($this->bindings)), ...$this->bindings);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) { $rows[] = $row; }
        $stmt->close();
        return $rows;
    }

    public function first(): ?array {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    public function count(): int {
        $this->select = 'COUNT(*) as total';
        $this->orderBy = [];
        $this->limit = null;
        $result = $this->get();
        return (int)($result[0]['total'] ?? 0);
    }

    public function insert(array $data): int {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $this->sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $this->bindings = array_values($data);
        $conn = Database::getConnection();
        $stmt = $conn->prepare($this->sql);
        if (!$stmt) throw new \Exception("Prepare failed: " . $conn->error);
        $stmt->bind_param(str_repeat('s', count($this->bindings)), ...$this->bindings);
        $stmt->execute();
        $stmt->close();
        return $conn->insert_id;
    }

    public function update(array $data): int {
        $sets = [];
        foreach (array_keys($data) as $key) { $sets[] = "$key = ?"; }
        $this->sql = "UPDATE {$this->table} SET " . implode(', ', $sets);
        if (!empty($this->where)) { $this->sql .= " WHERE " . implode(' AND ', $this->where); }
        $this->bindings = array_merge(array_values($data), $this->bindings);
        $conn = Database::getConnection();
        $stmt = $conn->prepare($this->sql);
        if (!$stmt) throw new \Exception("Prepare failed: " . $conn->error);
        $stmt->bind_param(str_repeat('s', count($this->bindings)), ...$this->bindings);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected;
    }

    public function delete(): int {
        $this->sql = "DELETE FROM {$this->table}";
        if (!empty($this->where)) { $this->sql .= " WHERE " . implode(' AND ', $this->where); }
        if ($this->limit) { $this->sql .= " LIMIT {$this->limit}"; }
        $conn = Database::getConnection();
        $stmt = $conn->prepare($this->sql);
        if (!$stmt) throw new \Exception("Prepare failed: " . $conn->error);
        if (!empty($this->bindings)) {
            $stmt->bind_param(str_repeat('s', count($this->bindings)), ...$this->bindings);
        }
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected;
    }

    private function buildSelect(): void {
        $this->sql = "SELECT {$this->select} FROM {$this->table}";
        if ($this->join) $this->sql .= $this->join;
        if (!empty($this->where)) $this->sql .= " WHERE " . implode(' AND ', $this->where);
        if ($this->groupBy) $this->sql .= " " . $this->groupBy;
        if ($this->having) $this->sql .= " " . $this->having;
        if (!empty($this->orderBy)) $this->sql .= " ORDER BY " . implode(', ', $this->orderBy);
        if ($this->limit) $this->sql .= " LIMIT {$this->limit}";
        if ($this->offset) $this->sql .= " OFFSET {$this->offset}";
    }

    public function toSql(): string { $this->buildSelect(); return $this->sql; }
    public function getBindings(): array { return $this->bindings; }
}
