<?php
/**
 * SELF-TRIGGERING CRON
 * Được gọi bởi mỗi page load qua <img> tag ẩn
 * Kiểm tra nếu đã >1 giờ từ lần chạy cuối → trigger marketing engine
 * Không block page load (async)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Return 1x1 transparent pixel immediately
header('Content-Type: image/gif');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

// Check if should run (max 1x per hour)
$lockFile = sys_get_temp_dir() . '/ss_cron_lock';
$lastRun = file_exists($lockFile) ? (int)file_get_contents($lockFile) : 0;
$now = time();

if ($now - $lastRun < 3600) exit; // Already ran this hour

// Update lock
file_put_contents($lockFile, $now);

// Run in background (non-blocking)
$cronKey = 'ss_mkt_' . substr(md5(JWT_SECRET . 'marketing'), 0, 16);
$url = 'https://shippershop.vn/api/auto-content.php?action=run&key=' . $cronKey;

// Use fsockopen for non-blocking request
$parts = parse_url($url);
$fp = @fsockopen('ssl://' . $parts['host'], 443, $errno, $errstr, 5);
if ($fp) {
    $path = $parts['path'] . '?' . $parts['query'];
    $out = "GET {$path} HTTP/1.1\r\n";
    $out .= "Host: {$parts['host']}\r\n";
    $out .= "Connection: Close\r\n\r\n";
    fwrite($fp, $out);
    fclose($fp); // Don't wait for response
}
