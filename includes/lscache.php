<?php
/**
 * LiteSpeed Cache Helper for ShipperShop
 * 
 * Usage:
 *   lscache_set(15);           // Cache this response for 15s
 *   lscache_set(0);            // Don't cache (POST, auth-sensitive)
 *   lscache_purge('feed');     // Purge all feed cache after POST
 *   lscache_purge('*');        // Purge everything
 */

function lscache_set($ttl = 15, $tag = '') {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header('X-LiteSpeed-Cache-Control: no-cache');
        return;
    }
    // Don't cache authenticated requests (personalized data)
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        header('X-LiteSpeed-Cache-Control: no-cache');
        return;
    }
    if ($ttl > 0) {
        header('X-LiteSpeed-Cache-Control: public, max-age=' . $ttl);
        if ($tag) header('X-LiteSpeed-Tag: ' . $tag);
    } else {
        header('X-LiteSpeed-Cache-Control: no-cache');
    }
}

function lscache_purge($tag = '*') {
    if ($tag === '*') {
        header('X-LiteSpeed-Purge: *');
    } else {
        header('X-LiteSpeed-Purge: tag=' . $tag);
    }
}
