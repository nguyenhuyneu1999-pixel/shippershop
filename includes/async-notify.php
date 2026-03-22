<?php
/**
 * ShipperShop Async Notification Dispatcher
 * On Redis (VPS): queue dispatch → non-blocking
 * On shared hosting: direct DB insert → still fast (1 query)
 * 
 * Does NOT require push-helper.php or vapid_keys.php
 */

function asyncNotify($userId, $title, $body = '', $type = 'general', $link = '') {
    if (!$userId || !$title) return false;
    
    // Try Redis queue (non-blocking)
    if (class_exists('Redis')) {
        try {
            $r = new Redis();
            $r->connect('127.0.0.1', 6379, 0.3);
            $r->select(3); // DB 3 for queue
            $job = json_encode([
                'type' => 'notification',
                'data' => compact('userId', 'title', 'body', 'type', 'link'),
                'ts' => time()
            ]);
            $r->lPush('ss:queue:notification', $job);
            return true; // Queued! Non-blocking
        } catch (Throwable $e) {}
    }
    
    // Fallback: direct DB insert (still fast, 1 query)
    try {
        $d = db();
        $d->query(
            "INSERT INTO notifications (user_id, type, title, message, data, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())",
            [$userId, $type, $title, $body, json_encode(['link' => $link])]
        );
        return true;
    } catch (Throwable $e) {
        return false;
    }
}
