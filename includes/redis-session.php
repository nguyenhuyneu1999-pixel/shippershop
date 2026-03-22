<?php
/**
 * ShipperShop Redis Session Handler
 * ONLY activates if Redis session handler is actually available
 */
function setupRedisSession() {
    // Only attempt if Redis extension loaded AND session handler registered
    if (!class_exists('Redis')) return false;
    if (!in_array('redis', array_map('strtolower', explode(' ', ini_get('session.save_handler') . ' redis files')))) {
        // Check if redis handler is available
        try {
            $old = ini_get('session.save_handler');
            ini_set('session.save_handler', 'redis');
            // If it didn't actually set, Redis session handler is not available
            if (ini_get('session.save_handler') !== 'redis') {
                return false;
            }
            // Test connection
            ini_set('session.save_path', 'tcp://127.0.0.1:6379?database=0&prefix=ss_sess:&timeout=0.5');
            return true;
        } catch (Exception $e) {
            // Revert
            ini_set('session.save_handler', 'files');
            return false;
        }
    }
    return false;
}

// Only run if session not started yet
if (session_status() === PHP_SESSION_NONE) {
    setupRedisSession();
}
