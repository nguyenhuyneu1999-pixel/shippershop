<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$d = db();

// Set cover for groups without one
$groups = $d->fetchAll("SELECT id, name, cover_image FROM `groups`");
$covers = [
    '/uploads/posts/seed_v2_1.jpg', '/uploads/posts/seed_v2_2.jpg',
    '/uploads/posts/seed_v2_3.jpg', '/uploads/posts/seed_v2_4.jpg',
    '/uploads/posts/seed_v2_5.jpg', '/uploads/posts/seed_v2_6.jpg',
];
$updated = 0;
foreach ($groups as $g) {
    if (empty($g['cover_image'])) {
        $cover = $covers[$g['id'] % count($covers)];
        $d->query("UPDATE `groups` SET cover_image = ? WHERE id = ?", [$cover, $g['id']]);
        $updated++;
    }
}

// Seed cover for users without one
$d->query("UPDATE users SET cover_image = '/uploads/posts/seed_v2_1.jpg' WHERE cover_image IS NULL OR cover_image = '' LIMIT 100");
$d->query("UPDATE users SET cover_image = '/uploads/posts/seed_v2_2.jpg' WHERE (cover_image IS NULL OR cover_image = '') AND id % 6 = 0 LIMIT 100");
$d->query("UPDATE users SET cover_image = '/uploads/posts/seed_v2_3.jpg' WHERE (cover_image IS NULL OR cover_image = '') AND id % 6 = 1 LIMIT 100");
$d->query("UPDATE users SET cover_image = '/uploads/posts/seed_v2_4.jpg' WHERE (cover_image IS NULL OR cover_image = '') AND id % 6 = 2 LIMIT 100");
$d->query("UPDATE users SET cover_image = '/uploads/posts/seed_v2_5.jpg' WHERE (cover_image IS NULL OR cover_image = '') AND id % 6 = 3 LIMIT 100");
$d->query("UPDATE users SET cover_image = '/uploads/posts/seed_v2_6.jpg' WHERE (cover_image IS NULL OR cover_image = '') AND id % 6 = 4 LIMIT 100");

$nocover_users = $d->fetchOne("SELECT COUNT(*) as c FROM users WHERE cover_image IS NULL OR cover_image = ''");
$nocover_groups = $d->fetchOne("SELECT COUNT(*) as c FROM `groups` WHERE cover_image IS NULL OR cover_image = ''");

echo json_encode(['groups_updated' => $updated, 'users_no_cover' => $nocover_users['c'], 'groups_no_cover' => $nocover_groups['c']]);
