<?php
/**
 * Helper Functions
 */

require_once __DIR__ . '/../config/Config.php';

function formatCurrency(float $amount, string $currency = 'NGN'): string {
    $symbols = ['NGN' => '₦', 'USD' => '$', 'GBP' => '£'];
    $symbol = $symbols[$currency] ?? $currency;
    
    return $symbol . number_format($amount, 2);
}

function parseCurrency(string $value): float {
    $value = preg_replace('/[^0-9.-]/', '', $value);
    return (float)$value;
}

function formatPhoneNumber(string $phone): string {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (strlen($phone) === 10 && $phone[0] === '0') {
        return '+234' . substr($phone, 1);
    }
    
    if (strlen($phone) === 11 && $phone[0] === '0') {
        return '+234' . substr($phone, 1);
    }
    
    if (strlen($phone) === 9) {
        return '+234' . $phone;
    }
    
    return $phone;
}

function validateNUBAN(string $accountNumber): bool {
    $accountNumber = preg_replace('/[^0-9]/', '', $accountNumber);
    return preg_match('/^\d{10,11}$/', $accountNumber);
}

function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone(string $phone): bool {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) >= 10 && strlen($phone) <= 14;
}

function formatDate(string $date, string $format = 'd M, Y'): string {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

function formatDateTime(string $datetime, string $format = 'd M, Y h:i A'): string {
    if (empty($datetime)) return '';
    return date($format, strtotime($datetime));
}

function getNigerianStates(): array {
    return [
        'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue',
        'Borno', 'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu',
        'Gombe', 'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi',
        'Kogi', 'Kwara', 'Lagos', 'Nasarawa', 'Niger', 'Ogun', 'Ondo',
        'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara',
        'FCT'
    ];
}

function getCategories(): array {
    return [
        'chargers' => 'Chargers',
        'cables' => 'Cables',
        'adapters' => 'Adapters',
        'power_supplies' => 'Power Supplies',
        'hubs' => 'USB Hubs',
        'other' => 'Other'
    ];
}

function getPaymentMethods(): array {
    return [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'paystack' => 'Paystack (Card/Transfer)',
        'pos' => 'POS Terminal'
    ];
}

function generateUuid(): string {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        random_int(0, 0xffff),
        random_int(0, 0xffff),
        random_int(0, 0xffff),
        random_int(0, 0x0fff) | 0x4000,
        random_int(0, 0x3fff) | 0x8000,
        random_int(0, 0xffff),
        random_int(0, 0xffff),
        random_int(0, 0xffff)
    );
}

function sanitizeInput(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function arrayGet(array $array, string $key, $default = null) {
    $keys = explode('.', $key);
    
    foreach ($keys as $k) {
        if (!isset($array[$k])) {
            return $default;
        }
        $array = $array[$k];
    }
    
    return $array;
}

function response(bool $success, string $message = '', $data = null, int $statusCode = 200): void {
    http_response_code($statusCode);
    
    header('Content-Type: application/json');
    
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ];
    
    echo json_encode($response);
    exit;
}

function successResponse(string $message = '', $data = null, int $statusCode = 200): void {
    response(true, $message, $data, $statusCode);
}

function errorResponse(string $message = '', $data = null, int $statusCode = 400): void {
    response(false, $message, $data, $statusCode);
}

function paginate(array $data, int $page, int $perPage, int $total): array {
    return [
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
            'next_page' => $page < ceil($total / $perPage) ? $page + 1 : null,
            'prev_page' => $page > 1 ? $page - 1 : null
        ]
    ];
}

function requireAuth(): ?array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        throw new AuthenticationException('Authentication required');
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'] ?? '',
        'name' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['user_role'] ?? ''
    ];
}

function requireRole(string $role): array {
    $user = requireAuth();
    
    if ($user['role'] !== $role && $user['role'] !== 'admin') {
        throw new AuthorizationException('Insufficient permissions');
    }
    
    return $user;
}

function requireAnyRole(array $roles): array {
    $user = requireAuth();
    
    if (!in_array($user['role'], $roles) && $user['role'] !== 'admin') {
        throw new AuthorizationException('Insufficient permissions');
    }
    
    return $user;
}

function getSettings(): array {
    $settings = DatabaseConnection::table('settings')->get();
    
    $result = [];
    foreach ($settings as $setting) {
        $result[$setting['setting_key']] = $setting['setting_value'];
    }
    
    return $result;
}

function getSetting(string $key, $default = null) {
    $result = DatabaseConnection::table('settings')
        ->where('setting_key', $key)
        ->first();
    
    return $result ? $result['setting_value'] : $default;
}

function setSetting(string $key, $value): bool {
    $existing = DatabaseConnection::table('settings')
        ->where('setting_key', $key)
        ->first();
    
    if ($existing) {
        return DatabaseConnection::table('settings')
            ->where('setting_key', $key)
            ->update(['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]) > 0;
    }
    
    return DatabaseConnection::table('settings')->insert([
        'setting_key' => $key,
        'setting_value' => $value,
        'category' => 'general',
        'created_at' => date('Y-m-d H:i:s')
    ]) > 0;
}
