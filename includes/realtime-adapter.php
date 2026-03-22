<?php
/**
 * ShipperShop Realtime Adapter
 * Shared hosting: long-polling (check every 3s)
 * VPS: WebSocket push (instant)
 * Code chuyển tự động
 */

class RealtimeAdapter {
    private static $instance = null;
    private $redis = null;
    private $useRedis = false;
    
    private function __construct() {
        if (class_exists('Redis')) {
            try {
                $this->redis = new Redis();
                $this->redis->connect('127.0.0.1', 6379, 1);
                $this->redis->select(4); // DB 4 for pubsub
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
     * Publish event (khi có tin nhắn mới, notification, etc.)
     * Redis: push to channel → WebSocket server broadcast
     * No Redis: noop (client polls)
     */
    public function publish($channel, $data) {
        if ($this->useRedis) {
            $this->redis->publish('ss:' . $channel, json_encode($data));
            // Also store in list for polling fallback
            $this->redis->lPush('ss:events:' . $channel, json_encode(array_merge($data, ['ts' => time()])));
            $this->redis->lTrim('ss:events:' . $channel, 0, 99); // Keep last 100
            $this->redis->expire('ss:events:' . $channel, 300); // 5 min TTL
            return true;
        }
        return false; // Client will poll
    }
    
    /**
     * Get recent events since timestamp (for polling clients)
     */
    public function poll($channel, $since = 0) {
        if ($this->useRedis) {
            $events = $this->redis->lRange('ss:events:' . $channel, 0, 49);
            $result = [];
            foreach ($events ?: [] as $e) {
                $data = json_decode($e, true);
                if ($data && ($data['ts'] ?? 0) > $since) {
                    $result[] = $data;
                }
            }
            return $result;
        }
        // No Redis: return empty, client uses DB polling
        return [];
    }
    
    public function info() {
        return ['mode' => $this->useRedis ? 'redis_pubsub' : 'db_polling'];
    }
}

function realtime() {
    return RealtimeAdapter::getInstance();
}
