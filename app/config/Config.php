<?php
/**
 * Configuration Loader
 * Loads environment variables from .env file
 */

class Config {
    private static $loaded = false;
    private static $config = [];

    public static function load(): void {
        if (self::$loaded) return;

        $envFile = dirname(dirname(__DIR__)) . '/.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                if (!array_key_exists($key, $_ENV) && !array_key_exists($key, $_SERVER)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }

        self::$config = [
            'db' => [
                'host' => getenv('DB_HOST') ?: 'localhost',
                'port' => getenv('DB_PORT') ?: '3306',
                'database' => getenv('DB_DATABASE') ?: 'shevvy_sheriff_inventory',
                'username' => getenv('DB_USERNAME') ?: 'root',
                'password' => getenv('DB_PASSWORD') ?: '',
                'charset' => 'utf8mb4'
            ],
            'app' => [
                'env' => getenv('APP_ENV') ?: 'development',
                'debug' => filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN),
                'timezone' => getenv('TIMEZONE') ?: 'Africa/Lagos',
                'url' => getenv('APP_URL') ?: 'http://localhost:8080'
            ],
            'paystack' => [
                'public_key' => getenv('PAYSTACK_PUBLIC_KEY') ?: '',
                'secret_key' => getenv('PAYSTACK_SECRET_KEY') ?: '',
                'mode' => getenv('PAYSTACK_MODE') ?: 'test'
            ],
            'session' => [
                'timeout' => (int)(getenv('SESSION_TIMEOUT') ?: 900)
            ],
            'upload' => [
                'max_size' => (int)(getenv('UPLOAD_MAX_SIZE') ?: 10485760),
                'path' => getenv('UPLOAD_PATH') ?: dirname(__DIR__) . '/uploads/'
            ],
            'log' => [
                'level' => getenv('LOG_LEVEL') ?: 'debug',
                'path' => getenv('LOG_PATH') ?: dirname(__DIR__) . '/logs/'
            ]
        ];

        date_default_timezone_set(self::$config['app']['timezone']);
        self::$loaded = true;
    }

    public static function get(string $key, $default = null) {
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
