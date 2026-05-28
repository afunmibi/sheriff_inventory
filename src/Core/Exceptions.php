<?php
namespace Core;

class AppException extends \Exception {
    protected int $statusCode = 500;
    public function getStatusCode(): int { return $this->statusCode; }
}

class DatabaseException extends AppException {
    protected int $statusCode = 500;
    public function __construct(string $message = 'Database error occurred', ?\Throwable $previous = null) {
        parent::__construct($message, 0, $previous);
        Logger::error("DatabaseException: " . $message);
    }
}

class ValidationException extends AppException {
    protected int $statusCode = 422;
    private array $errors = [];
    public function __construct(string $message = 'Validation failed', array $errors = [], ?\Throwable $previous = null) {
        parent::__construct($message, 0, $previous);
        $this->errors = $errors;
    }
    public function getErrors(): array { return $this->errors; }
    public function setErrors(array $errors): self { $this->errors = $errors; return $this; }
}

class AuthenticationException extends AppException {
    protected int $statusCode = 401;
    public function __construct(string $message = 'Authentication required', ?\Throwable $previous = null) {
        parent::__construct($message, 0, $previous);
    }
}

class AuthorizationException extends AppException {
    protected int $statusCode = 403;
    public function __construct(string $message = 'Access denied', ?\Throwable $previous = null) {
        parent::__construct($message, 0, $previous);
    }
}

class NotFoundException extends AppException {
    protected int $statusCode = 404;
    public function __construct(string $message = 'Resource not found', ?\Throwable $previous = null) {
        parent::__construct($message, 0, $previous);
    }
}

class ConflictException extends AppException {
    protected int $statusCode = 409;
    public function __construct(string $message = 'Conflict occurred', ?\Throwable $previous = null) {
        parent::__construct($message, 0, $previous);
    }
}

class PaymentException extends AppException {
    protected int $statusCode = 402;
    public function __construct(string $message = 'Payment failed', ?\Throwable $previous = null) {
        parent::__construct($message, 0, $previous);
    }
}

class InsufficientStockException extends AppException {
    protected int $statusCode = 400;
    private int $availableStock;
    public function __construct(string $message = 'Insufficient stock', int $availableStock = 0, ?\Throwable $previous = null) {
        parent::__construct($message, 0, $previous);
        $this->availableStock = $availableStock;
    }
    public function getAvailableStock(): int { return $this->availableStock; }
}
