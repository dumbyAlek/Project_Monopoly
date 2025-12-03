<?php
// Database/Database.php
require_once __DIR__ . '/db_config.php';

class Database {
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct() {
        $host = DB_HOST;
        $port = DB_PORT;
        $db   = DB_NAME;
        $user = DB_USER;
        $pass = DB_PASS;
        $charset = DB_CHARSET;

        $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
        if (!empty($port)) {
            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
        }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // Fail early with safe message
            die('Database connection failed. Check Database/db_config.php and DB server.');
        }

        // Optional schema init (won't overwrite existing table)
        $this->initSchema();
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->pdo;
    }

    private function initSchema(): void {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            // Ignore on production where schema is managed separately
        }
    }
}