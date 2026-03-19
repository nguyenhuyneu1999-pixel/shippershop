<?php
/**
 * AUTO-CRON PIXEL + SELF-CRON KICKSTARTER
 * 1x1 gif pixel trên mọi trang → trigger marketing engine mỗi giờ
 * Cũng kick-start self-cron nếu nó chưa chạy
 */
ignore_user_abort(true);
require_once __DIR__ . '/../includes/config.php';

// Return pixel ngay lập tức (không block page load)
header('Content-Type: image/gif');
header('Cache-Control: no-store, no-cache');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
if (ob_get_level()) ob_end_flush();
flush();

// Lock: max 1x/55 phút  
$lock = sys_get_temp_dir() . '/ss_autocron';
$last = file_exists($lock) ? (int)file_get_contents($lock) : 0;
if (time() - $last < 3300) exit;
file_put_contents($lock, time());

// Fire marketing engine (non-blocking)
$cronKey = 'ss_mkt_' . substr(md5(JWT_SECRET . 'marketing'), 0, 16);
$urls = [
    "/api/auto-content.php?action=run&key=$cronKey",
    "/api/auto-publish.php?action=run&key=$cronKey",
];

foreach ($urls as $path) {
    $fp = @fsockopen('ssl://shippershop.vn', 443, $e1, $e2, 3);
    if ($fp) {
        fwrite($fp, "GET $path HTTP/1.1\r\nHost: shippershop.vn\r\nConnection: Close\r\n\r\n");
        fclose($fp);
    }
}
