<?php
// PHP + session + config + db connect (no query)
session_start();
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
echo '{"ok":true,"db":true}';
