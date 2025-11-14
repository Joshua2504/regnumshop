<?php

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            // Create database directory if it doesn't exist
            $dbPath = DB_PATH;
            $dbDir = dirname($dbPath);

            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }

            // Create database connection
            $this->pdo = new PDO('sqlite:' . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Enable foreign keys
            $this->pdo->exec('PRAGMA foreign_keys = ON');

            // Initialize database if needed
            $this->initializeDatabase();
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    private function initializeDatabase() {
        // Check if database is already initialized
        $result = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");

        if ($result->fetch() === false) {
            // Read and execute init.sql
            $initSql = file_get_contents(__DIR__ . '/../database/init.sql');
            $this->pdo->exec($initSql);
        }
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('Database query error: ' . $e->getMessage());
            return false;
        }
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollback() {
        return $this->pdo->rollBack();
    }
}
