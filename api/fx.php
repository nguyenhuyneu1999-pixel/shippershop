<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$d = db();
$r = [];

// Add cover_image column if not exists
try {
    $d->query("ALTER TABLE `groups` ADD COLUMN cover_image VARCHAR(255) DEFAULT NULL");
    $r[] = 'groups.cover_image added';
} catch (Throwable $e) { $r[] = 'groups.cover_image: exists'; }

// Add cover_image to users if not exists
try {
    $d->query("ALTER TABLE users ADD COLUMN cover_image VARCHAR(255) DEFAULT NULL");
    $r[] = 'users.cover_image added';
} catch (Throwable $e) { $r[] = 'users.cover_image: exists'; }

// Seed cover images for groups
$covers = [
    'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=800&h=300&fit=crop',
    'https://images.unsplash.com/photo-1566576912321-d58ddd7a6088?w=800&h=300&fit=crop',
    'https://images.unsplash.com/photo-1519003722824-194d4455a60c?w=800&h=300&fit=crop',
    'https://images.unsplash.com/photo-1530099486328-e021101a494a?w=800&h=300&fit=crop',
    'https://images.unsplash.com/photo-1543872084-c7bd3822856f?w=800&h=300&fit=crop',
];

$groups = $d->fetchAll("SELECT id FROM `groups` WHERE cover_image IS NULL OR cover_image = ''");
$updated = 0;
foreach ($groups ?: [] as $i => $g) {
    $cover = $covers[$i % count($covers)];
    $d->query("UPDATE `groups` SET cover_image = ? WHERE id = ?", [$cover, $g['id']]);
    $updated++;
}
$r[] = "groups cover: $updated updated";

// Seed cover images for users (top 50)
$userCovers = [
    'https://images.unsplash.com/photo-1557683316-973673baf926?w=800&h=300&fit=crop',
    'https://images.unsplash.com/photo-1557682250-33bd709cbe85?w=800&h=300&fit=crop',
    'https://images.unsplash.com/photo-1557682224-5b8590cd9ec5?w=800&h=300&fit=crop',
    'https://images.unsplash.com/photo-1557682260-96773eb01377?w=800&h=300&fit=crop',
    'https://images.unsplash.com/photo-1557682268-e3955ed5d83f?w=800&h=300&fit=crop',
];

$users = $d->fetchAll("SELECT id FROM users WHERE (cover_image IS NULL OR cover_image = '') LIMIT 100");
$uUpdated = 0;
foreach ($users ?: [] as $i => $u) {
    $cover = $userCovers[$i % count($userCovers)];
    $d->query("UPDATE users SET cover_image = ? WHERE id = ?", [$cover, $u['id']]);
    $uUpdated++;
}
$r[] = "users cover: $uUpdated updated";

echo json_encode(['success' => true, 'results' => $r]);
