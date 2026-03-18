<?php
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
header('Content-Type: text/plain');

// Check what IP auth.php sees
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ip = explode(',', $ip)[0];
echo "Request IP: $ip\n";
echo "REMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? 'null') . "\n";
echo "X-Forwarded-For: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'null') . "\n";

// Check login_attempts
$r = db()->fetchAll("SELECT ip, email, success, created_at FROM login_attempts ORDER BY created_at DESC LIMIT 10", []);
echo "\nRecent attempts: " . count($r) . "\n";
foreach ($r as $row) echo "  IP={$row['ip']} email={$row['email']} s={$row['success']} {$row['created_at']}\n";

// Count per IP
$ips = db()->fetchAll("SELECT ip, COUNT(*) as cnt FROM login_attempts WHERE success=0 AND created_at > ? GROUP BY ip", [date('Y-m-d H:i:s', time() - 900)]);
echo "\nFailed per IP (15min):\n";
foreach ($ips as $i) echo "  {$i['ip']}: {$i['cnt']}\n";
