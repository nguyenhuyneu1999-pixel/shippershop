<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
require_once '/home/nhshiw2j/public_html/includes/functions.php';
echo "Config OK\n";
$db = db();
echo "DB OK\n";
$pdo = db()->getConnection();
echo "PDO OK\n";
$plans = $db->fetchAll("SELECT id,name FROM subscription_plans WHERE is_active=1 ORDER BY sort_order ASC", []);
echo "Plans: " . count($plans) . "\n";
