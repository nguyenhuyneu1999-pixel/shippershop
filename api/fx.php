<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$d = db();
$groups_cols = $d->fetchAll("SHOW COLUMNS FROM `groups`");
$users_cols = $d->fetchAll("SHOW COLUMNS FROM users WHERE Field LIKE '%cover%' OR Field LIKE '%avatar%'");
echo json_encode([
    'groups' => array_map(function($c){return $c['Field'];}, $groups_cols),
    'users_cover_avatar' => array_map(function($c){return $c['Field'];}, $users_cols),
]);
