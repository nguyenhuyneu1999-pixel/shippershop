<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$d = db();
$u = $d->fetchOne("SELECT id, username, email, `status`, LEFT(password,20) as pw_prefix FROM users WHERE id = 2");
echo json_encode($u);
