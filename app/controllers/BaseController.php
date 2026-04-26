<?php
/**
 * Base Controller
 */

require_once __DIR__ . '/../core/Logger.php';

class BaseController {
    protected array $currentUser = [];

    public function __construct() {
        $this->initializeAuth();
    }

    protected function initializeAuth(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['user_id'])) {
            $this->currentUser = [
                'user_id' => $_SESSION['user_id'],
                'email' => $_SESSION['user_email'] ?? '',
                'name' => $_SESSION['user_name'] ?? '',
                'role' => $_SESSION['user_role'] ?? ''
            ];
        }
    }

    protected function requireAuth(): array {
        if (empty($this->currentUser)) {
            throw new AuthenticationException('Authentication required');
        }
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
        $data = json_decode($input, true);
        
        return $data ?? [];
    }

    protected function getInput(string $key = null, $default = null) {
        if ($key === null) {
            return array_merge($_GET, $_POST);
        }
        
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function getInt(string $key, int $default = 0): int {
        return (int)($this->getInput($key, $default));
    }

    protected function getFloat(string $key, float $default = 0.0): float {
        return (float)($this->getInput($key, $default));
    }

    protected function getBool(string $key, bool $default = false): bool {
        $value = $this->getInput($key, $default);
        
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
        }
        
        return (bool)$value;
    }

    protected function successResponse(string $message = '', $data = null, int $statusCode = 200): void {
        http_response_code($statusCode);
        
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ]);
        
        exit;
    }

    protected function errorResponse(string $message = '', $data = null, int $statusCode = 400): void {
        http_response_code($statusCode);
        
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ]);
        
        exit;
    }

    protected function createdResponse(string $message = '', $data = null): void {
        $this->successResponse($message, $data, 201);
    }

    protected function notFoundResponse(string $message = 'Resource not found'): void {
        $this->errorResponse($message, null, 404);
    }

    protected function unauthorizedResponse(string $message = 'Unauthorized'): void {
        $this->errorResponse($message, null, 401);
    }

    protected function forbiddenResponse(string $message = 'Forbidden'): void {
        $this->errorResponse($message, null, 403);
    }

    protected function validationError(string $message = 'Validation failed', array $errors = []): void {
        http_response_code(422);
        
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => false,
            'message' => $message,
            'data' => ['errors' => $errors],
            'timestamp' => date('c')
        ]);
        
        exit;
    }

    protected function paginate(array $data, int $page, int $perPage, int $total): array {
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int)ceil($total / $perPage),
                'next_page' => $page < ceil($total / $perPage) ? $page + 1 : null,
                'prev_page' => $page > 1 ? $page - 1 : null
            ]
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

    protected function validateEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validatePhone(string $phone): bool {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return strlen($phone) >= 10;
    }

    protected function log(string $level, string $message, array $context = []): void {
        Logger::$level($message, $context);
    }
}
