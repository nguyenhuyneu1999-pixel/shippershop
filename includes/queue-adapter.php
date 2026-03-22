<?php
/**
 * ShipperShop Queue Adapter
 * Shared hosting: Xử lý ngay (sync)
 * VPS + Redis: Đẩy vào queue (async)
 * KHÔNG CẦN THAY ĐỔI CODE khi chuyển hosting
 */

class QueueAdapter {
    private static $instance = null;
    private $redis = null;
    private $useRedis = false;
    
    private function __construct() {
        if (class_exists('Redis')) {
            try {
                $this->redis = new Redis();
                $this->redis->connect('127.0.0.1', 6379, 1);
                $this->redis->select(3); // DB 3 for queue
                $this->useRedis = true;
            } catch (Exception $e) {
                $this->useRedis = false;
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    
    /**
     * Dispatch a job
     * On shared hosting: runs immediately (sync)
     * On VPS with Redis: pushes to queue (async)
     */
    public function dispatch($jobType, $data) {
        if ($this->useRedis) {
            // Async: push to Redis queue
            $job = json_encode([
                'type' => $jobType,
                'data' => $data,
                'created_at' => date('c'),
                'id' => uniqid('job_', true)
            ]);
            $this->redis->lPush('ss:queue:' . $jobType, $job);
            return true;
        }
        
        // Sync: process immediately
        return $this->processJob($jobType, $data);
    }
    
    /**
     * Process a job synchronously
     */
    public function processJob($jobType, $data) {
        try {
            switch ($jobType) {
                case 'notification':
                    return $this->jobNotification($data);
                case 'optimize_image':
                    return $this->jobOptimizeImage($data);
                case 'update_stats':
                    return $this->jobUpdateStats($data);
                case 'send_email':
                    return $this->jobSendEmail($data);
                case 'audit_log':
                    return $this->jobAuditLog($data);
                default:
                    error_log("Unknown job type: $jobType");
                    return false;
            }
        } catch (Throwable $e) {
            error_log("Queue job error [$jobType]: " . $e->getMessage());
            return false;
        }
    }
    
    // === JOB HANDLERS ===
    
    private function jobNotification($data) {
        $d = db();
        $userId = intval($data['user_id'] ?? 0);
        $title = $data['title'] ?? '';
        $message = $data['message'] ?? '';
        $type = $data['type'] ?? 'general';
        $link = $data['link'] ?? '';
        
        if (!$userId || !$title) return false;
        
        $d->query("INSERT INTO notifications (user_id, type, title, message, data, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())", 
            [$userId, $type, $title, $message, json_encode(['link' => $link])]);
        return true;
    }
    
    private function jobOptimizeImage($data) {
        $path = $data['path'] ?? '';
        if (!$path || !file_exists($path)) return false;
        
        require_once __DIR__ . '/image-optimizer.php';
        return optimizeImage($path, $path, 1200, 82);
    }
    
    private function jobUpdateStats($data) {
        $d = db();
        $type = $data['type'] ?? '';
        $id = intval($data['id'] ?? 0);
        
        if ($type === 'post_likes') {
            $count = intval($d->fetchOne("SELECT COUNT(*) as c FROM likes WHERE post_id = ?", [$id])['c'] ?? 0);
            $d->query("UPDATE posts SET likes_count = ? WHERE id = ?", [$count, $id]);
        }
        if ($type === 'post_comments') {
            $count = intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE post_id = ? AND `status` = 'active'", [$id])['c'] ?? 0);
            $d->query("UPDATE posts SET comments_count = ? WHERE id = ?", [$count, $id]);
        }
        return true;
    }
    
    private function jobSendEmail($data) {
        // Placeholder — implement with Brevo/SMTP when needed
        error_log("Email job: to={$data['to']} subject={$data['subject']}");
        return true;
    }
    
    private function jobAuditLog($data) {
        $d = db();
        $d->query("INSERT INTO audit_log (user_id, action, details, ip, created_at) VALUES (?, ?, ?, ?, NOW())",
            [intval($data['user_id'] ?? 0), $data['action'] ?? '', $data['details'] ?? '', $data['ip'] ?? '']);
        return true;
    }
    
    /**
     * Get queue stats (for monitoring)
     */
    public function stats() {
        if (!$this->useRedis) {
            return ['mode' => 'sync', 'queues' => []];
        }
        
        $queues = ['notification', 'optimize_image', 'update_stats', 'send_email', 'audit_log'];
        $stats = [];
        foreach ($queues as $q) {
            $stats[$q] = $this->redis->lLen('ss:queue:' . $q);
        }
        return ['mode' => 'async', 'queues' => $stats];
    }
}

// Shortcut
function queue() {
    return QueueAdapter::getInstance();
}
