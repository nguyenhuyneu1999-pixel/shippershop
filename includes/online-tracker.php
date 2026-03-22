<?php
/**
 * Track user online status — lightweight, runs on every auth request
 * Updates last_active timestamp (max once per 30s to reduce DB writes)
 */
function trackOnline($userId) {
    if (!$userId) return;
    static $tracked = false;
    if ($tracked) return;
    $tracked = true;
    
    // Use SmartCache to throttle (1 update per 30s per user)
    $key = 'online:' . $userId;
    try {
        if (class_exists('SmartCache')) {
            require_once __DIR__ . '/smart-cache.php';
            $last = scache()->get($key);
            if ($last && (time() - intval($last)) < 30) return;
            scache()->set($key, time(), 60);
        }
    } catch (Throwable $e) {}
    
    try {
        db()->query("UPDATE users SET is_online = 1, last_active = NOW() WHERE id = ?", [$userId]);
    } catch (Throwable $e) {}
}
