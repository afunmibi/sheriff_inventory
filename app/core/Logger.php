<?php
/**
 * Logger Class
 * Simple file-based logging
 */

require_once __DIR__ . '/../config/Config.php';

class Logger {
    private static string $logPath;
    private static string $logLevel = 'debug';
    
    private const LEVELS = [
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3,
        'critical' => 4
    ];

    public static function initialize(): void {
        Config::load();
        self::$logPath = Config::get('log.path', dirname(__DIR__, 2) . '/logs/');
        self::$logLevel = Config::get('log.level', 'debug');
        
        if (!is_dir(self::$logPath)) {
            mkdir(self::$logPath, 0755, true);
        }
    }

    private static function shouldLog(string $level): bool {
        $configLevel = self::LEVELS[self::$logLevel] ?? 0;
        $messageLevel = self::LEVELS[$level] ?? 0;
        return $messageLevel >= $configLevel;
    }

    private static function write(string $level, string $message, array $context = []): void {
        self::initialize();
        
        if (!self::shouldLog($level)) return;
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logEntry = "[$timestamp] " . strtoupper($level) . ": $message$contextStr\n";
        
        $filename = self::$logPath . date('Y-m-d') . '.log';
        file_put_contents($filename, $logEntry, FILE_APPEND);
    }

    public static function debug(string $message, array $context = []): void {
        self::write('debug', $message, $context);
    }

    public static function info(string $message, array $context = []): void {
        self::write('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void {
        self::write('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void {
        self::write('error', $message, $context);
    }

    public static function critical(string $message, array $context = []): void {
        self::write('critical', $message, $context);
    }
}
