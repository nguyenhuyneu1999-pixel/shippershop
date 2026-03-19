<?php
/**
 * SELF-SUSTAINING CRON - Không cần dịch vụ bên ngoài
 * 
 * Cơ chế: Mỗi khi được gọi, nó schedule chính nó chạy lại sau 1 giờ
 * bằng cách gửi async request tới chính nó với delay
 * 
 * Kết hợp với auto-cron.php pixel → chạy liên tục 24/7
 */
ignore_user_abort(true);
set_time_limit(0);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$key = $_GET['key'] ?? '';
$cronKey = 'ss_mkt_' . substr(md5(JWT_SECRET . 'marketing'), 0, 16);

// Return fast response
header('Content-Type: application/json');
echo json_encode(['success' => true, 'time' => date('Y-m-d H:i:s')]);

if (ob_get_level()) ob_end_flush();
flush();

// Only proceed if key is valid
if ($key !== $cronKey) exit;

// Lock: max 1 run per hour
$lockFile = sys_get_temp_dir() . '/ss_selfcron_lock';
$lastRun = file_exists($lockFile) ? (int)file_get_contents($lockFile) : 0;
$now = time();
if ($now - $lastRun < 3500) exit; // 58 min guard
file_put_contents($lockFile, $now);

// === RUN MARKETING ENGINE ===
$d = db();
$hour = (int)date('G');
$log = date('Y-m-d H:i:s') . " | Hour: $hour | ";

// 1. Generate content at specific hours
$contentHours = [6, 8, 12, 15, 18, 21];
if (in_array($hour, $contentHours)) {
    $url = "https://shippershop.vn/api/auto-content.php?action=run&key=$cronKey";
    @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 30]]));
    $log .= "content_generated ";
}

// 2. AI content 3x/day
if (in_array($hour, [6, 12, 18])) {
    $url = "https://shippershop.vn/api/auto-content.php?action=generate_ai&key=$cronKey";
    @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 60]]));
    $log .= "ai_content ";
}

// 3. Auto-publish pending
$url = "https://shippershop.vn/api/auto-publish.php?action=run&key=$cronKey";
@file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 30]]));
$log .= "published ";

// 4. Weekly report on Monday 9AM
if (date('N') === '1' && $hour === 9) {
    $url = "https://shippershop.vn/api/auto-content.php?action=weekly_report&key=$cronKey";
    @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 30]]));
    $log .= "weekly_report ";
}

// Log run
try {
    $d->query("INSERT INTO marketing_analytics (date, channel, impressions, data) VALUES (CURDATE(), 'cron_run', 1, ?) ON DUPLICATE KEY UPDATE impressions = impressions + 1, data = ?",
        [$log, $log]);
} catch (Throwable $e) {}

// === SELF-SCHEDULE: ping myself again after ~55 minutes ===
// This creates a recursive loop that runs forever
sleep(1);
$selfUrl = "https://shippershop.vn/api/self-cron.php?key=$cronKey";
$fp = @fsockopen('ssl://shippershop.vn', 443, $errno, $errstr, 5);
if ($fp) {
    $path = "/api/self-cron.php?key=$cronKey";
    $out = "GET $path HTTP/1.1\r\nHost: shippershop.vn\r\nConnection: Close\r\n\r\n";
    fwrite($fp, $out);
    // Don't read response, just fire and forget
    fclose($fp);
}
