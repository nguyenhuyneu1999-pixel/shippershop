<?php
/**
 * ShipperShop Redis Session Handler
 * Shared hosting: native file sessions (default)
 * VPS: Redis sessions (shared across PHP workers)
 * 
 * Include BEFORE session_start() in config.php
 */

function setupRedisSession() {
    if (!class_exists('Redis')) return false;
    
    try {
        $r = new Redis();
        $r->connect('127.0.0.1', 6379, 0.5);
        $r->select(0); // DB 0 for sessions
        
        // Set session handler to Redis
        ini_set('session.save_handler', 'redis');
        ini_set('session.save_path', 'tcp://127.0.0.1:6379?database=0&prefix=ss_sess:');
        ini_set('session.gc_maxlifetime', 86400); // 24 hours
        
        return true;
    } catch (Exception $e) {
        return false; // Fall back to file sessions
    }
}

// Auto-setup
setupRedisSession();
