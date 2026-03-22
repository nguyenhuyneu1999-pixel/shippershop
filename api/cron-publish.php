<?php
/**
 * ShipperShop — Auto-publish scheduled posts
 * Cron: every 5 min — curl /api/cron-publish.php?key=ss_pub_cron
 */
if (($_GET['key'] ?? '') !== 'ss_pub_cron') { http_response_code(403); exit; }
session_start();
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
$d = db();

// Find scheduled posts ready to publish
$scheduled = $d->fetchAll(
    "SELECT id, user_id, content FROM posts WHERE `status` = 'active' AND is_draft = 0 AND scheduled_at IS NOT NULL AND scheduled_at <= NOW() AND scheduled_at > DATE_SUB(NOW(), INTERVAL 1 DAY)",
    []
);

$published = 0;
foreach ($scheduled ?: [] as $post) {
    // Already active and past scheduled time = already published (no action needed)
    // But if status was 'scheduled', change to 'active'
    $published++;
}

// Also check drafts scheduled for now
$drafts = $d->fetchAll(
    "SELECT id FROM posts WHERE is_draft = 1 AND scheduled_at IS NOT NULL AND scheduled_at <= NOW()",
    []
);
foreach ($drafts ?: [] as $draft) {
    $d->query("UPDATE posts SET is_draft = 0, `status` = 'active' WHERE id = ?", [$draft['id']]);
    $published++;
}

// Flush feed cache if anything published
if ($published > 0) {
    require_once __DIR__ . '/../includes/api-cache.php';
    api_cache_flush('feed_');
}

echo json_encode(['success' => true, 'published' => $published, 'checked_scheduled' => count($scheduled ?: []), 'checked_drafts' => count($drafts ?: [])]);
