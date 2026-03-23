<?php
// PHP + session + config + db + 1 query
session_start();
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
$r = db()->fetchOne("SELECT 1 as v", []);
header('Content-Type: application/json');
echo json_encode(['ok'=>true,'query'=>$r['v']]);
