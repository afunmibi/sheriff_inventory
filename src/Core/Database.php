<?php
namespace Core;

class Database {
    private static ?\mysqli $instance = null;
    private static int $connectionAttempts = 0;
    private const MAX_RETRY = 3;

    public static function getConnection(): \mysqli {
        if (self::$instance === null || !self::$instance->ping()) {
            self::connect();
        }
        return self::$instance;
    }

    private static function connect(): void {
        Config::load();
        $config = Config::get('db');

        try {
            self::$instance = new \mysqli(
                $config['host'],
                $config['username'],
                $config['password'],
                $config['database'],
                (int)$config['port']
            );

            if (self::$instance->connect_error) {
                throw new \Exception("Connection failed: " . self::$instance->connect_error);
            }

            self::$instance->set_charset($config['charset']);
            self::$connectionAttempts = 0;

        } catch (\Exception $e) {
            self::$connectionAttempts++;
            if (self::$connectionAttempts < self::MAX_RETRY) {
                sleep(pow(2, self::$connectionAttempts));
                self::connect();
            } else {
                throw new \Exception("DB connection failed after " . self::MAX_RETRY . " attempts: " . $e->getMessage());
            }
        }
    }

    public static function beginTransaction(): bool {
        return self::getConnection()->begin_transaction();
    }

    public static function commit(): bool {
        return self::getConnection()->commit();
    }

    public static function rollback(): bool {
        return self::getConnection()->rollback();
    }

    public static function lastInsertId(): int {
        return (int)self::getConnection()->insert_id;
    }

    public static function affectedRows(): int {
        return self::getConnection()->affected_rows;
    }

    public static function escape(string $value): string {
        return self::getConnection()->real_escape_string($value);
    }

    public static function close(): void {
        if (self::$instance !== null) {
            self::$instance->close();
            self::$instance = null;
        }
    }

    public static function prepare(string $sql): ?\mysqli_stmt {
        return self::getConnection()->prepare($sql);
    }

    public static function query(string $sql) {
        return self::getConnection()->query($sql);
    }

    public static function table(string $table): QueryBuilder {
        return new QueryBuilder($table);
    }
}
