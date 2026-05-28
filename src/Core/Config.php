<?php
namespace Core;

class Config {
    private static bool $loaded = false;
    private static array $config = [];
    private static string $envPath = '';

    public static function setEnvPath(string $path): void {
        self::$envPath = $path;
    }

    public static function load(): void {
        if (self::$loaded) return;

        $envFile = self::$envPath ?: dirname(__DIR__, 2) . '/.env';

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) continue;
                if (!str_contains($line, '=')) continue;

                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                if (
                    strlen($value) >= 2
                    && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))
                ) {
                    $value = substr($value, 1, -1);
                }

                if (!array_key_exists($key, $_ENV) && !array_key_exists($key, $_SERVER)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }

        $rootPath = dirname(__DIR__, 2);
        $uploadsPath = self::resolvePath(getenv('UPLOAD_PATH') ?: $rootPath . '/public/uploads/', $rootPath);
        $logsPath = self::resolvePath(getenv('LOG_PATH') ?: $rootPath . '/storage/logs/', $rootPath);

        self::$config = [
            'db' => [
                'host'     => getenv('DB_HOST') ?: 'localhost',
                'port'     => getenv('DB_PORT') ?: '3306',
                'database' => getenv('DB_DATABASE') ?: 'shevvy_sheriff_inventory',
                'username' => getenv('DB_USERNAME') ?: 'root',
                'password' => getenv('DB_PASSWORD') ?: '',
                'charset'  => 'utf8mb4',
            ],
            'app' => [
                'env'      => getenv('APP_ENV') ?: 'development',
                'debug'    => filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN),
                'key'      => getenv('APP_KEY') ?: '',
                'name'     => getenv('APP_NAME') ?: 'Sheriff Shevvy Inventory',
                'timezone' => getenv('TIMEZONE') ?: 'Africa/Lagos',
                'url'      => getenv('APP_URL') ?: 'http://localhost:8080',
                'allowed_origins' => array_filter(array_map('trim', explode(',', getenv('ALLOWED_ORIGINS') ?: ''))),
            ],
            'paystack' => [
                'public_key' => getenv('PAYSTACK_PUBLIC_KEY') ?: '',
                'secret_key' => getenv('PAYSTACK_SECRET_KEY') ?: '',
                'mode'       => getenv('PAYSTACK_MODE') ?: 'test',
            ],
            'session' => [
                'timeout' => max(60, (int)(getenv('SESSION_TIMEOUT') ?: 900)),
                'name'    => getenv('SESSION_NAME') ?: 'sheriff_inventory_session',
            ],
            'mail' => [
                'business_email' => getenv('MAIL_BUSINESS_EMAIL') ?: 'akintundesheriff09@gmail.com',
                'driver'         => getenv('MAIL_DRIVER') ?: 'mail',
                'host'           => getenv('MAIL_HOST') ?: '',
                'port'           => (int)(getenv('MAIL_PORT') ?: 587),
                'username'       => getenv('MAIL_USERNAME') ?: '',
                'password'       => getenv('MAIL_PASSWORD') ?: '',
                'encryption'     => getenv('MAIL_ENCRYPTION') ?: 'tls',
            ],
            'upload' => [
                'max_size' => (int)(getenv('UPLOAD_MAX_SIZE') ?: 10485760),
                'path'     => rtrim($uploadsPath, '/\\') . '/',
            ],
            'log' => [
                'level' => getenv('LOG_LEVEL') ?: 'debug',
                'path'  => rtrim($logsPath, '/\\') . '/',
            ],
        ];

        date_default_timezone_set(self::$config['app']['timezone']);
        self::$loaded = true;
    }

    private static function resolvePath(string $path, string $rootPath): string {
        if ($path === '') {
            return $rootPath;
        }

        $path = str_replace('\\', '/', $path);
        if (preg_match('#^[A-Za-z]:/#', $path) || str_starts_with($path, '/')) {
            return $path;
        }

        return $rootPath . '/' . ltrim($path, './');
    }

    public static function get(string $key, mixed $default = null): mixed {
        self::load();
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) return $default;
            $value = $value[$k];
        }

        return $value;
    }

    public static function all(): array {
        self::load();
        return self::$config;
    }
}
