<?php
/**
 * Application Bootstrap
 * Autoloader, error handling, and app initialization
 */

// ── Autoloader ──────────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    $prefixes = [
        'Core\\'       => __DIR__ . '/src/Core/',
        'Auth\\'       => __DIR__ . '/src/Auth/',
        'Inventory\\'  => __DIR__ . '/src/Inventory/',
        'Ecommerce\\'  => __DIR__ . '/src/Ecommerce/',
        'Helpers\\'    => __DIR__ . '/src/Helpers/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix)) {
            $relative = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
            // Fallback: check if class is in a grouped file (e.g. Exceptions)
            $grouped = $baseDir . 'Exceptions.php';
            if (file_exists($grouped)) {
                require_once $grouped;
                if (class_exists($class, false)) return;
            }
        }
    }
});

// ── Config ───────────────────────────────────────────────────
require_once __DIR__ . '/src/Core/Config.php';
Core\Config::load();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// ── Error Handling ──────────────────────────────────────────
$debug = Core\Config::get('app.debug', false);
if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

set_exception_handler(function (Throwable $e): void {
    $statusCode = $e instanceof Core\AppException ? $e->getStatusCode() : 500;
    http_response_code($statusCode);
    header('Content-Type: application/json');
    if (!Core\Config::get('app.debug') && $statusCode >= 500) {
        Core\Logger::error($e->getMessage(), ['exception' => get_class($e)]);
    }

    echo json_encode([
        'success' => false,
        'message' => Core\Config::get('app.debug') || $statusCode < 500 ? $e->getMessage() : 'An unexpected error occurred.',
        'file'    => Core\Config::get('app.debug') ? $e->getFile() . ':' . $e->getLine() : null,
        'trace'   => Core\Config::get('app.debug') ? $e->getTrace() : null,
        'timestamp' => date('c'),
    ]);
    exit;
});

// ── Session ──────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_name(Core\Config::get('session.name', 'sheriff_inventory_session'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ── Timezone ─────────────────────────────────────────────────
date_default_timezone_set(Core\Config::get('app.timezone', 'Africa/Lagos'));

// ── Helper functions ─────────────────────────────────────────
require_once __DIR__ . '/src/Helpers/helpers.php';
