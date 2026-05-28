<?php
namespace Core;

class Logger {
    private static ?string $logPath = null;

    private static function init(): void {
        if (self::$logPath) return;
        self::$logPath = Config::get('log.path', dirname(__DIR__, 2) . '/storage/logs/');
        if (!is_dir(self::$logPath)) {
            mkdir(self::$logPath, 0755, true);
        }
    }

    private static function write(string $level, string $message, array $context = []): void {
        self::init();
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $line = "[$timestamp] $level: $message$contextStr" . PHP_EOL;

        $file = self::$logPath . date('Y-m-d') . '.log';
        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function emergency(string $message, array $context = []): void { self::write('EMERGENCY', $message, $context); }
    public static function alert(string $message, array $context = []): void { self::write('ALERT', $message, $context); }
    public static function critical(string $message, array $context = []): void { self::write('CRITICAL', $message, $context); }
    public static function error(string $message, array $context = []): void { self::write('ERROR', $message, $context); }
    public static function warning(string $message, array $context = []): void { self::write('WARNING', $message, $context); }
    public static function notice(string $message, array $context = []): void { self::write('NOTICE', $message, $context); }
    public static function info(string $message, array $context = []): void { self::write('INFO', $message, $context); }
    public static function debug(string $message, array $context = []): void { self::write('DEBUG', $message, $context); }
}
