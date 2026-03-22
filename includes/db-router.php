<?php
/**
 * ShipperShop DB Router — Auto Read/Write Split
 * Shared hosting: 1 DB (same connection for read+write)
 * VPS: Primary (write) + Replica (read)
 * KHÔNG CẦN ĐỔI CODE khi thêm replica
 * 
 * Config: tạo file includes/db-replica.php nếu có replica
 */

class DBRouter {
    private static $instance = null;
    private $primary = null;  // Write DB
    private $replica = null;  // Read DB
    private $hasReplica = false;
    
    private function __construct() {
        $this->primary = db(); // Always available
        
        // Check for replica config
        $replicaConfig = __DIR__ . '/db-replica.php';
        if (file_exists($replicaConfig)) {
            try {
                require_once $replicaConfig;
                if (defined('DB_REPLICA_HOST')) {
                    $dsn = 'mysql:host=' . DB_REPLICA_HOST . ';dbname=' . (defined('DB_REPLICA_NAME') ? DB_REPLICA_NAME : DB_NAME) . ';charset=' . DB_CHARSET;
                    $user = defined('DB_REPLICA_USER') ? DB_REPLICA_USER : DB_USER;
                    $pass = defined('DB_REPLICA_PASS') ? DB_REPLICA_PASS : DB_PASS;
                    $this->replica = new PDO($dsn, $user, $pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_PERSISTENT => true
                    ]);
                    $this->hasReplica = true;
                }
            } catch (Exception $e) {
                error_log("DB Replica error: " . $e->getMessage());
                $this->hasReplica = false;
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    
    /** Read query — uses replica if available */
    public function read($sql, $params = []) {
        if ($this->hasReplica) {
            $stmt = $this->replica->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $this->primary->fetchAll($sql, $params);
    }
    
    /** Read one row — uses replica if available */
    public function readOne($sql, $params = []) {
        if ($this->hasReplica) {
            $stmt = $this->replica->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        return $this->primary->fetchOne($sql, $params);
    }
    
    /** Write query — always uses primary */
    public function write($sql, $params = []) {
        return $this->primary->query($sql, $params);
    }
    
    /** Info */
    public function info() {
        return [
            'has_replica' => $this->hasReplica,
            'mode' => $this->hasReplica ? 'primary+replica' : 'single'
        ];
    }
}

function dbRouter() {
    return DBRouter::getInstance();
}
