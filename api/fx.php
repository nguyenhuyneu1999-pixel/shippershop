<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$d = db();
$cols = $d->fetchAll("SHOW COLUMNS FROM `groups`");
$colNames = array_map(function($c){return $c['Field'];}, $cols);

// Check users table for cover
$ucols = $d->fetchAll("SHOW COLUMNS FROM users");
$ucolNames = array_map(function($c){return $c['Field'];}, $ucols);

// Sample group
$g = $d->fetchOne("SELECT id, name, cover_image, icon_image FROM `groups` LIMIT 1");

echo json_encode([
    'group_cols' => $colNames,
    'has_cover' => in_array('cover_image', $colNames),
    'user_cols_cover' => in_array('cover_image', $ucolNames),
    'sample_group' => $g
]);
