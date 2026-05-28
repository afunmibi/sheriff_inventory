<?php
/**
 * Database Setup Script
 * Run this file to initialize the database and create necessary tables
 * Usage: php setup.php
 */

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__DIR__));

require_once ROOT . DS . 'config' . DS . 'DatabaseConnection.php';

class DatabaseSetup {
    private $conn;
    private $sqlFile;
    
    public function __construct() {
        $this->sqlFile = ROOT . DS . 'database' . DS . 'schema.sql';
    }
    
    public function run(): void {
        echo "===========================================\n";
        echo "Tech Application - Database Setup\n";
        echo "===========================================\n\n";
        
        try {
            $this->conn = new mysqli(
                getenv('DB_HOST') ?: 'localhost',
                getenv('DB_USERNAME') ?: 'root',
                getenv('DB_PASSWORD') ?: '',
                getenv('DB_DATABASE') ?: 'shevvy_sheriff_inventory',
                (int)(getenv('DB_PORT') ?: 3306)
            );
            
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            echo "[OK] Database connected successfully\n\n";
            
            $this->createDatabase();
            $this->runSchema();
            $this->createAdminUser();
            $this->createDirectories();
            
            echo "\n===========================================\n";
            echo "Setup completed successfully!\n";
            echo "===========================================\n";
            echo "\nDefault Admin Credentials:\n";
            echo "Email: admin@techapp.com\n";
            echo "Password: Admin@123\n";
            echo "\nPlease change the default password after first login!\n";
            
        } catch (Exception $e) {
            echo "[ERROR] " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    private function createDatabase(): void {
        $dbName = getenv('DB_DATABASE') ?: 'shevvy_sheriff_inventory';
        
        $sql = "CREATE DATABASE IF NOT EXISTS `$dbName` 
                CHARACTER SET utf8mb4 
                COLLATE utf8mb4_unicode_ci";
        
        if ($this->conn->query($sql)) {
            echo "[OK] Database '$dbName' created or already exists\n";
        } else {
            throw new Exception("Failed to create database: " . $this->conn->error);
        }
        
        $this->conn->select_db($dbName);
    }
    
    private function runSchema(): void {
        if (!file_exists($this->sqlFile)) {
            throw new Exception("Schema file not found: " . $this->sqlFile);
        }
        
        $schema = file_get_contents($this->sqlFile);
        
        $statements = array_filter(
            array_map('trim', explode(';', $schema)),
            fn($s) => !empty($s) && strpos($s, '--') !== 0
        );
        
        $count = 0;
        foreach ($statements as $statement) {
            if (stripos($statement, 'DELIMITER') !== false) {
                continue;
            }
            
            if (!empty(trim($statement))) {
                if ($this->conn->query($statement)) {
                    $count++;
                } else {
                    $error = $this->conn->error;
                    if (strpos($error, 'already exists') === false) {
                        echo "[WARNING] " . substr($error, 0, 100) . "...\n";
                    }
                }
            }
        }
        
        echo "[OK] Database schema loaded ($count statements)\n";
    }
    
    private function createAdminUser(): void {
        $email = 'admin@techapp.com';
        $password = password_hash('Admin@123', PASSWORD_BCRYPT);
        
        $stmt = $this->conn->prepare(
            "INSERT IGNORE INTO users (name, email, password_hash, role, is_active) 
             VALUES (?, ?, ?, 'admin', 1)"
        );
        
        $name = 'System Administrator';
        $stmt->bind_param('sss', $name, $email, $password);
        
        if ($stmt->execute()) {
            echo "[OK] Default admin user created\n";
        } else {
            echo "[INFO] Admin user already exists or creation failed\n";
        }
        
        $stmt->close();
    }
    
    private function createDirectories(): void {
        $directories = [
            ROOT . DS . 'uploads',
            ROOT . DS . 'uploads' . DS . 'products',
            ROOT . DS . 'uploads' . DS . 'logos',
            ROOT . DS . 'logs',
            ROOT . DS . 'cache',
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        
        echo "[OK] Required directories created\n";
    }
}

if (php_sapi_name() === 'cli') {
    $setup = new DatabaseSetup();
    $setup->run();
}
