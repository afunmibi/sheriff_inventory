<?php
/**
 * Global Helper Functions
 * These are function aliases that bridge old global-style calls
 * to the new namespaced classes.
 */

use Core\Database;

if (!function_exists('formatCurrency')) {
    function formatCurrency(float $amount, string $currency = 'NGN'): string {
        $symbols = ['NGN' => '₦', 'USD' => '$', 'GBP' => '£'];
        $symbol = $symbols[$currency] ?? $currency;
        return $symbol . number_format($amount, 2);
    }
}

if (!function_exists('parseCurrency')) {
    function parseCurrency(string $value): float {
        return (float)preg_replace('/[^0-9.-]/', '', $value);
    }
}

if (!function_exists('formatPhoneNumber')) {
    function formatPhoneNumber(string $phone): string {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) === 10 && $phone[0] === '0') return '+234' . substr($phone, 1);
        if (strlen($phone) === 11 && $phone[0] === '0') return '+234' . substr($phone, 1);
        if (strlen($phone) === 9) return '+234' . $phone;
        return $phone;
    }
}

if (!function_exists('validateNUBAN')) {
    function validateNUBAN(string $accountNumber): bool {
        return (bool)preg_match('/^\d{10,11}$/', preg_replace('/[^0-9]/', '', $accountNumber));
    }
}

if (!function_exists('validateEmail')) {
    function validateEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('validatePhone')) {
    function validatePhone(string $phone): bool {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return strlen($phone) >= 10 && strlen($phone) <= 14;
    }
}

if (!function_exists('formatDate')) {
    function formatDate(string $date, string $format = 'd M, Y'): string {
        return empty($date) ? '' : date($format, strtotime($date));
    }
}

if (!function_exists('formatDateTime')) {
    function formatDateTime(string $datetime, string $format = 'd M, Y h:i A'): string {
        return empty($datetime) ? '' : date($format, strtotime($datetime));
    }
}

if (!function_exists('getNigerianStates')) {
    function getNigerianStates(): array {
        return ['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara','FCT'];
    }
}

if (!function_exists('generateUuid')) {
    function generateUuid(): string {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff), random_int(0, 0xffff),
            random_int(0, 0xffff), random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff));
    }
}

if (!function_exists('sanitizeInput')) {
    function sanitizeInput(string $value): string {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('arrayGet')) {
    function arrayGet(array $array, string $key, $default = null) {
        $keys = explode('.', $key);
        foreach ($keys as $k) {
            if (!isset($array[$k])) return $default;
            $array = $array[$k];
        }
        return $array;
    }
}

if (!function_exists('getSetting')) {
    function getSetting(string $key, $default = null) {
        $result = Database::table('settings')->where('setting_key', $key)->first();
        return $result ? $result['setting_value'] : $default;
    }
}

if (!function_exists('setSetting')) {
    function setSetting(string $key, $value): bool {
        $existing = Database::table('settings')->where('setting_key', $key)->first();
        if ($existing) {
            return Database::table('settings')
                ->where('setting_key', $key)
                ->update(['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]) > 0;
        }
        return Database::table('settings')->insert([
            'setting_key'   => $key,
            'setting_value' => $value,
            'category'      => 'general',
            'created_at'    => date('Y-m-d H:i:s'),
        ]) > 0;
    }
}

if (!function_exists('response')) {
    function response(bool $success, string $message = '', $data = null, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success, 'message' => $message,
            'data' => $data, 'timestamp' => date('c'),
        ]);
        exit;
    }
}

if (!function_exists('successResponse')) {
    function successResponse(string $message = '', $data = null, int $statusCode = 200): void {
        response(true, $message, $data, $statusCode);
    }
}

if (!function_exists('errorResponse')) {
    function errorResponse(string $message = '', $data = null, int $statusCode = 400): void {
        response(false, $message, $data, $statusCode);
    }
}
