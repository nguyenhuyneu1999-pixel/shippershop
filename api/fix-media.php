<?php
set_time_limit(120);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain; charset=utf-8');
$db = db();

// Get all available images
$imgDir = __DIR__ . '/../uploads/posts/';
$allImgs = [];
foreach (glob($imgDir . 'seed_*.jpg') as $f) {
    $allImgs[] = '/uploads/posts/' . basename($f);
}
echo "Available images: " . count($allImgs) . "\n";

$allVids = [
    '/uploads/videos/vid_3_1772440538_b41b51fe.mp4',
    '/uploads/videos/vid_2_1772428966_bc9dc62b.mp4',
    '/uploads/videos/vid_3_1772415179_e1326d96.mp4',
];

// 1. Get posts without images (limit 80)
$emptyPosts = $db->fetchAll("SELECT id FROM posts WHERE `status`='active' AND (images IS NULL OR images='[]' OR images='') AND video_url IS NULL ORDER BY RAND() LIMIT 80", []);
echo "Posts without media: " . count($emptyPosts) . "\n";

$imgIdx = 0;
$updatedImg = 0;
$updatedVid = 0;

foreach ($emptyPosts as $ep) {
    $pid = $ep['id'];
    
    // 70% get images, 10% get video, 20% stay text
    $roll = rand(1, 100);
    if ($roll <= 70 && count($allImgs) > 0) {
        // Add 1-2 images
        $n = rand(1, 2);
        $selected = [];
        for ($j = 0; $j < $n; $j++) {
            $selected[] = $allImgs[$imgIdx % count($allImgs)];
            $imgIdx++;
        }
        $db->query("UPDATE posts SET images=? WHERE id=?", [json_encode($selected), $pid]);
        $updatedImg++;
    } elseif ($roll <= 80 && count($allVids) > 0) {
        $db->query("UPDATE posts SET video_url=? WHERE id=?", [$allVids[array_rand($allVids)], $pid]);
        $updatedVid++;
    }
}
echo "Added images to {$updatedImg} posts\n";
echo "Added video to {$updatedVid} posts\n";

// 2. Spread post dates more evenly - make sure recent ones have media
$recentPosts = $db->fetchAll("SELECT id FROM posts WHERE `status`='active' ORDER BY created_at DESC LIMIT 30", []);
foreach ($recentPosts as $rp) {
    $post = $db->fetchOne("SELECT images, video_url FROM posts WHERE id=?", [$rp['id']]);
    if ((!$post['images'] || $post['images'] === '[]') && !$post['video_url']) {
        // Add image to recent posts that are empty
        $n = rand(1, 2);
        $selected = [];
        for ($j = 0; $j < $n; $j++) {
            $selected[] = $allImgs[$imgIdx % count($allImgs)];
            $imgIdx++;
        }
        $db->query("UPDATE posts SET images=? WHERE id=?", [json_encode($selected), $rp['id']]);
    }
}
echo "Ensured recent 30 posts have media\n";

// 3. Verify
$total = $db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'", []);
$withImg = $db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND images IS NOT NULL AND images != '[]' AND images != ''", []);
$withVid = $db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND video_url IS NOT NULL AND video_url != ''", []);
echo "\nFinal counts:\n";
echo "  Total posts: " . $total['c'] . "\n";
echo "  With images: " . $withImg['c'] . "\n";
echo "  With video: " . $withVid['c'] . "\n";
echo "DONE!\n";
