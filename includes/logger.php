<?php
/**
 * ShipperShop Structured Logger
 * Logs to /tmp/ss_logs/ with daily rotation
 * Usage: ss_log('error', 'Payment failed', ['user' => 5, 'amount' => 49000]);
 */
function ss_log($level, $message, $context = []) {
    $dir = '/tmp/ss_logs';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    
    $file = $dir . '/' . date('Y-m-d') . '.log';
    $entry = json_encode([
        'ts' => date('c'),
        'level' => $level,
        'msg' => $message,
        'ctx' => $context,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'uri' => $_SERVER['REQUEST_URI'] ?? '',
    ], JSON_UNESCAPED_UNICODE) . "\n";
    
    @file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
    
    // Cleanup: delete logs older than 7 days
    static $cleaned = false;
    if (!$cleaned) {
        $cleaned = true;
        foreach (glob($dir . '/*.log') as $f) {
            if (filemtime($f) < time() - 604800) @unlink($f);
        }
    }
}
