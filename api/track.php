<?php
// Simple analytics tracker - writes to file (no DB overhead)
header('Content-Type: image/gif');
header('Cache-Control: no-store');

$page = substr($_GET['p'] ?? '', 0, 50);
$ref = substr($_GET['r'] ?? '', 0, 200);
$src = substr($_GET['s'] ?? '', 0, 50);
$dur = intval($_GET['d'] ?? 0);
$action = $_GET['action'] ?? 'view';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200);

$logDir = __DIR__ . '/../uploads/analytics';
if (!is_dir($logDir)) mkdir($logDir, 0755, true);

$date = date('Y-m-d');
$time = date('H:i:s');
$line = "$time\t$page\t$action\t$ref\t$src\t$dur\t$ip\t$ua\n";

file_put_contents("$logDir/$date.log", $line, FILE_APPEND | LOCK_EX);

// Return 1x1 transparent GIF
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
