<?php

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            // Create database directory if it doesn't exist
            $dbPath = DB_PATH;
            $dbDir = dirname($dbPath);

            error_log("Database path: " . $dbPath);
            error_log("Database directory: " . $dbDir);

            if (!is_dir($dbDir)) {
                if (!mkdir($dbDir, 0755, true)) {
                    throw new Exception("Failed to create database directory: " . $dbDir);
                }
                error_log("Created database directory: " . $dbDir);
            }

            // Check if directory is writable
            if (!is_writable($dbDir)) {
                throw new Exception("Database directory is not writable: " . $dbDir);
            }

            // Create database connection
            $this->pdo = new PDO('sqlite:' . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Enable foreign keys
            $this->pdo->exec('PRAGMA foreign_keys = ON');

            error_log("Database connected successfully");

            // Initialize database if needed
            $this->initializeDatabase();
        } catch (PDOException $e) {
            error_log('Database PDO error: ' . $e->getMessage());
            die('Database connection failed: ' . $e->getMessage());
        } catch (Exception $e) {
            error_log('Database error: ' . $e->getMessage());
            die('Database error: ' . $e->getMessage());
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
        $this->applyMigrations();
    }

    /**
     * Apply simple schema migrations when necessary.
     */
    private function applyMigrations() {
        $columns = $this->pdo->query('PRAGMA table_info(users)')->fetchAll();
        $columnNames = array_column($columns, 'name');

        if (!in_array('forum_user_id', $columnNames, true)) {
            $this->pdo->exec('ALTER TABLE users ADD COLUMN forum_user_id INTEGER');
        }

        // Remove legacy admin_users table (handled via forum IDs now)
        $adminUsersTable = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='admin_users'")->fetch();
        if ($adminUsersTable) {
            $this->pdo->exec('DROP TABLE IF EXISTS admin_users');
        }

        $orderColumns = $this->pdo->query('PRAGMA table_info(orders)')->fetchAll();
        $orderColumnNames = array_column($orderColumns, 'name');

        if (!in_array('order_number', $orderColumnNames, true)) {
            $this->pdo->exec('ALTER TABLE orders ADD COLUMN order_number TEXT');
            $orders = $this->pdo->query('SELECT id FROM orders')->fetchAll(PDO::FETCH_COLUMN);
            foreach ($orders as $orderId) {
                $this->assignOrderNumber($orderId);
            }
        }

        $this->pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_orders_order_number ON orders(order_number)');
    }

    /**
     * Assign a unique 6-digit order number to an order.
     */
    private function assignOrderNumber($orderId) {
        $number = $this->generateUniqueOrderNumber();
        $stmt = $this->pdo->prepare('UPDATE orders SET order_number = ? WHERE id = ?');
        $stmt->execute([$number, $orderId]);
    }

    private function generateUniqueOrderNumber() {
        do {
            $number = (string)random_int(100000, 999999);
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM orders WHERE order_number = ?');
            $stmt->execute([$number]);
        } while ($stmt->fetchColumn() > 0);

        return $number;
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
