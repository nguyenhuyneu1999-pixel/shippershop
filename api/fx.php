<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$d = db();

// Set default banner colors for groups without banner_image
$colors = ['#7C3AED', '#1877F2', '#00b14f', '#EE4D2D', '#F59E0B', '#0EA5E9', '#E74C3C', '#8B5CF6', '#00b14f', '#ff6600', '#d32f2f', '#ffc107', '#c41230', '#f5a623'];
$groups = $d->fetchAll("SELECT id, banner_color FROM `groups`");
$i = 0;
foreach ($groups ?: [] as $g) {
    if (empty($g['banner_color'])) {
        $color = $colors[$i % count($colors)];
        $d->query("UPDATE `groups` SET banner_color = ? WHERE id = ?", [$color, $g['id']]);
        $i++;
    }
}

// Set default cover for users without cover_image
$d->query("UPDATE users SET cover_image = '/uploads/covers/default-cover.jpg' WHERE cover_image IS NULL OR cover_image = ''");

echo json_encode(['success' => true, 'groups_updated' => $i]);
