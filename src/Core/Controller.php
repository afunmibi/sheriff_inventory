<?php
namespace Core;

class Controller {
    protected array $currentUser = [];

    public function __construct() {
        $this->initializeAuth();
    }

    protected function initializeAuth(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id']) && isset($_SESSION['user']['user_id'])) {
            $_SESSION['user_id'] = $_SESSION['user']['user_id'];
            $_SESSION['user_email'] = $_SESSION['user']['email'] ?? '';
            $_SESSION['user_name'] = $_SESSION['user']['name'] ?? '';
            $_SESSION['user_role'] = strtolower($_SESSION['user']['role'] ?? '');
        }

        if (isset($_SESSION['user_id'])) {
            $this->currentUser = [
                'user_id' => $_SESSION['user_id'],
                'email'   => $_SESSION['user_email'] ?? '',
                'name'    => $_SESSION['user_name'] ?? '',
                'role'    => $_SESSION['user_role'] ?? '',
            ];
        }
    }

    protected function requireAuth(): array {
        if (empty($this->currentUser)) {
            throw new AuthenticationException('Authentication required');
        }
        $timeout = Config::get('session.timeout', 900);
        $lastActivity = (int)($_SESSION['last_activity'] ?? 0);
        if ($lastActivity > 0 && time() - $lastActivity > $timeout) {
            $_SESSION = [];
            session_destroy();
            throw new AuthenticationException('Session expired');
        }
        $_SESSION['last_activity'] = time();
        return $this->currentUser;
    }

    protected function requireRole(string $role): array {
        $user = $this->requireAuth();
        if ($user['role'] !== $role && $user['role'] !== 'admin') {
            throw new AuthorizationException('Insufficient permissions');
        }
        return $user;
    }

    protected function requireAnyRole(array $roles): array {
        $user = $this->requireAuth();
        if (!in_array($user['role'], $roles) && $user['role'] !== 'admin') {
            throw new AuthorizationException('Insufficient permissions');
        }
        return $user;
    }

    protected function getJsonInput(): array {
        $input = file_get_contents('php://input');
        if ($input === false || trim($input) === '') return [];

        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            throw new ValidationException('Invalid JSON payload');
        }

        return $data;
    }

    protected function getInput(?string $key = null, mixed $default = null): mixed {
        if ($key === null) return array_merge($_GET, $_POST);
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function getInt(string $key, int $default = 0): int { return (int)($this->getInput($key, $default)); }
    protected function getFloat(string $key, float $default = 0.0): float { return (float)($this->getInput($key, $default)); }

    protected function getBool(string $key, bool $default = false): bool {
        $value = $this->getInput($key, $default);
        if (is_bool($value)) return $value;
        if (is_string($value)) return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
        return (bool)$value;
    }

    protected function json($data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function success(string $message = '', mixed $data = null, int $statusCode = 200): void {
        $this->json(['success' => true, 'message' => $message, 'data' => $data, 'timestamp' => date('c')], $statusCode);
    }

    protected function error(string $message = '', mixed $data = null, int $statusCode = 400): void {
        $this->json(['success' => false, 'message' => $message, 'data' => $data, 'timestamp' => date('c')], $statusCode);
    }

    protected function created(string $message = '', mixed $data = null): void { $this->success($message, $data, 201); }
    protected function notFound(string $message = 'Resource not found'): void { $this->error($message, null, 404); }
    protected function unauthorized(string $message = 'Unauthorized'): void { $this->error($message, null, 401); }
    protected function forbidden(string $message = 'Forbidden'): void { $this->error($message, null, 403); }

    protected function validationError(string $message = 'Validation failed', array $errors = []): void {
        $this->json([
            'success' => false,
            'message' => $message,
            'data'    => ['errors' => $errors],
            'timestamp' => date('c'),
        ], 422);
    }

    protected function paginate(array $data, int $page, int $perPage, int $total): array {
        return [
            'data'       => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => (int)ceil($total / $perPage),
                'next_page'    => $page < ceil($total / $perPage) ? $page + 1 : null,
                'prev_page'    => $page > 1 ? $page - 1 : null,
            ],
        ];
    }

    protected function validateRequired(array $data, array $fields): array {
        $errors = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                $errors[$field] = "The $field field is required";
            }
        }
        return $errors;
    }

    protected function log(string $level, string $message, array $context = []): void {
        Logger::$level($message, $context);
    }
}
