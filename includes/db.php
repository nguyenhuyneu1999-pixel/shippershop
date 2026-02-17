<?php
/**
 * ============================================
 * DATABASE CONNECTION CLASS
 * ============================================
 */

// Prevent direct access
defined('APP_ACCESS') or define('APP_ACCESS', true);

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $connection;
    private $lastInsertId;
    
    /**
     * Private constructor - Singleton pattern
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Database connection failed: " . $e->getMessage());
            } else {
                error_log("Database connection error: " . $e->getMessage());
                die("Database connection failed. Please contact administrator.");
            }
        }
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute query with parameters
     * 
     * @param string $sql SQL query
     * @param array $params Parameters to bind
     * @return PDOStatement
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                throw new Exception("Query error: " . $e->getMessage() . "\nSQL: " . $sql);
            } else {
                error_log("Query error: " . $e->getMessage() . "\nSQL: " . $sql);
                throw new Exception("Database query failed");
            }
        }
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Fetch single row
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Fetch single column value
     */
    public function fetchColumn($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }
    
    /**
     * Insert data
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return int Last insert ID
     */
    public function insert($table, $data) {
        $columns = array_keys($data);
        $values = array_values($data);
        
        $columnList = implode(', ', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        
        $sql = "INSERT INTO $table ($columnList) VALUES ($placeholders)";
        
        $this->query($sql, $values);
        $this->lastInsertId = $this->connection->lastInsertId();
        
        return $this->lastInsertId;
    }
    
    /**
     * Update data
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @param string $where WHERE clause
     * @param array $whereParams Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        $values = [];
        
        foreach ($data as $column => $value) {
            $setParts[] = "$column = ?";
            $values[] = $value;
        }
        
        $setClause = implode(', ', $setParts);
        $sql = "UPDATE $table SET $setClause WHERE $where";
        
        $allParams = array_merge($values, $whereParams);
        $stmt = $this->query($sql, $allParams);
        
        return $stmt->rowCount();
    }
    
    /**
     * Delete data
     * 
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $params Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function delete($table, $where, $params = []) {
        $sql = "UPDATE $table SET status = 'deleted' WHERE $where";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Hard delete (permanently remove)
     */
    public function hardDelete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Get last insert ID
     */
    public function getLastInsertId() {
        return $this->lastInsertId;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollBack();
    }
    
    /**
     * Check if table exists
     */
    public function tableExists($table) {
        $sql = "SHOW TABLES LIKE ?";
        $stmt = $this->query($sql, [$table]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Count rows
     */
    public function count($table, $where = '1=1', $params = []) {
        $sql = "SELECT COUNT(*) FROM $table WHERE $where";
        return $this->fetchColumn($sql, $params);
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Helper function to get database instance
function db() {
    return Database::getInstance();
}
