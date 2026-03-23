<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$d = db();
$u = $d->fetchOne("SELECT id, username, email, password, `status` FROM users WHERE id = 2");
$verify = password_verify('123456', $u['password']);
$attempts = $d->fetchAll("SELECT ip, email, success, created_at FROM login_attempts ORDER BY created_at DESC LIMIT 5");
echo json_encode([
    'user_id' => $u['id'],
    'username' => $u['username'],
    'email' => $u['email'],
    'status' => $u['status'],
    'pw_verify_123456' => $verify,
    'recent_attempts' => $attempts
]);
