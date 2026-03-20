<?php
/**
 * ShipperShop Rate Limiter — Uses existing rate_limits table
 * Schema: id, ip, endpoint, hits, window_start
 * Usage: rate_enforce('login', 5, 300); // auto IP, 429 if exceeded
 *        if(!rate_check('post', $ip, 10, 3600)) { ... }
 */

function rate_check($endpoint, $ip, $max, $window) {
    $d = db();
    $now = date('Y-m-d H:i:s');
    $cutoff = date('Y-m-d H:i:s', time() - $window);
    try { $d->query("DELETE FROM rate_limits WHERE window_start < ?", [$cutoff]); } catch (\Throwable $e) {}
    
    $row = $d->fetchOne("SELECT id, hits FROM rate_limits WHERE ip=? AND endpoint=? AND window_start>? LIMIT 1", [$ip, $endpoint, $cutoff]);
    
    if (!$row) {
        try { $d->query("INSERT INTO rate_limits (ip,endpoint,hits,window_start) VALUES (?,?,1,?)", [$ip,$endpoint,$now]); } catch (\Throwable $e) {}
        return true;
    }
    if (intval($row['hits']) >= $max) return false;
    try { $d->query("UPDATE rate_limits SET hits=hits+1 WHERE id=?", [$row['id']]); } catch (\Throwable $e) {}
    return true;
}

function rate_remaining($endpoint, $ip, $max, $window) {
    $cutoff = date('Y-m-d H:i:s', time() - $window);
    $row = db()->fetchOne("SELECT hits FROM rate_limits WHERE ip=? AND endpoint=? AND window_start>? LIMIT 1", [$ip,$endpoint,$cutoff]);
    return max(0, $max - intval($row ? $row['hits'] : 0));
}

function rate_reset($endpoint, $ip = null) {
    try {
        if ($ip) db()->query("DELETE FROM rate_limits WHERE endpoint=? AND ip=?", [$endpoint,$ip]);
        else db()->query("DELETE FROM rate_limits WHERE endpoint=?", [$endpoint]);
    } catch (\Throwable $e) {}
}

function rate_enforce($endpoint, $max, $window) {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    if (!rate_check($endpoint, $ip, $max, $window)) {
        http_response_code(429);
        echo json_encode(['success'=>false,'message'=>'Quá nhiều yêu cầu. Thử lại sau.','retry_after'=>$window]);
        exit;
    }
}
