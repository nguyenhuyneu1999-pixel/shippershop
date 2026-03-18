<?php
ini_set('display_errors',1);error_reporting(E_ALL);
echo "step1\n";
define('APP_ACCESS', true);
echo "step2\n";
require_once __DIR__ . '/../includes/config.php';
echo "step3\n";
require_once __DIR__ . '/../includes/db.php';
echo "step4\n";
require_once __DIR__ . '/../includes/functions.php';
echo "step5\n";
$db = db();
echo "step6\n";
$plans = $db->fetchAll("SELECT * FROM subscription_plans WHERE is_active=1 ORDER BY sort_order ASC", []);
echo "Plans: " . count($plans) . "\n";
echo json_encode(['success'=>true,'data'=>$plans], JSON_UNESCAPED_UNICODE);
