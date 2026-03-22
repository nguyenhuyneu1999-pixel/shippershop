<?php
/**
 * ShipperShop Smart Cache — Auto-detect Redis or File
 * Shared hosting: dùng file cache (/tmp)
 * VPS: tự động dùng Redis (nhanh gấp 100x)
 * 
 * Code KHÔNG CẦN thay đổi khi chuyển hosting → VPS
 */

class SmartCache {
    private static $instance = null;
    private $redis = null;
    private $useRedis = false;
    
    private function __construct() {
        // Auto-detect Redis
        if (class_exists('Redis')) {
            try {
                $this->redis = new Redis();
                $this->redis->connect('127.0.0.1', 6379, 1);
                $this->redis->select(2); // DB 2 for cache
                $this->useRedis = true;
            } catch (Exception $e) {
                $this->useRedis = false;
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function get($key) {
        if ($this->useRedis) {
            $val = $this->redis->get('ss:' . $key);
            return $val !== false ? json_decode($val, true) : null;
        }
        return $this->fileGet($key);
    }
    
    public function set($key, $value, $ttl = 60) {
        if ($this->useRedis) {
            $this->redis->setex('ss:' . $key, $ttl, json_encode($value));
            return true;
        }
        return $this->fileSet($key, $value, $ttl);
    }
    
    public function del($key) {
        if ($this->useRedis) {
            $this->redis->del('ss:' . $key);
            return true;
        }
        return $this->fileDel($key);
    }
    
    public function flush($prefix = '') {
        if ($this->useRedis) {
            if ($prefix) {
                $keys = $this->redis->keys('ss:' . $prefix . '*');
                if ($keys) $this->redis->del($keys);
            } else {
                $this->redis->flushDB();
            }
            return true;
        }
        return $this->fileFlush($prefix);
    }
    
    public function isRedis() {
        return $this->useRedis;
    }
    
    // === File fallback (shared hosting) ===
    private function fileGet($key) {
        $path = '/tmp/ss_cache/' . md5($key) . '.cache';
        if (!file_exists($path)) return null;
        $raw = @file_get_contents($path);
        if (!$raw) return null;
        $data = @unserialize($raw);
        if (!$data || !isset($data['exp']) || $data['exp'] < time()) {
            @unlink($path);
            return null;
        }
        return $data['val'];
    }
    
    private function fileSet($key, $value, $ttl) {
        $dir = '/tmp/ss_cache';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $path = $dir . '/' . md5($key) . '.cache';
        $data = serialize(['exp' => time() + $ttl, 'val' => $value, 'key' => $key]);
        return @file_put_contents($path, $data, LOCK_EX) !== false;
    }
    
    private function fileDel($key) {
        $path = '/tmp/ss_cache/' . md5($key) . '.cache';
        return @unlink($path);
    }
    
    private function fileFlush($prefix) {
        $dir = '/tmp/ss_cache';
        if (!is_dir($dir)) return true;
        $files = glob($dir . '/*.cache');
        $count = 0;
        foreach ($files ?: [] as $f) {
            if ($prefix) {
                $data = @unserialize(@file_get_contents($f));
                if ($data && isset($data['key']) && strpos($data['key'], $prefix) === 0) {
                    @unlink($f); $count++;
                }
            } else {
                @unlink($f); $count++;
            }
        }
        return $count;
    }
}

// Shortcut function
function scache() {
    return SmartCache::getInstance();
}
