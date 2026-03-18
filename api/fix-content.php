<?php
set_time_limit(300);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain; charset=utf-8');
$db = db();

// ===== STEP 1: Remove mismatched images =====
// Posts about questions, tips, confessions, discussions should NOT have random landscape images
echo "=== Step 1: Remove mismatched images ===\n";

// Keywords that indicate text-only posts (no landscape photo needed)
$textKeywords = ['hỏi','cho hỏi','ai biết','có ai','mẹo','tips','confession','thú thật','thú nhận',
    'nghĩ sao','bàn về','nên mua','review','đánh giá','hàng dễ vỡ','app','bug','lỗi','phí ship',
    'lương','tuyển','đăng ký','bảo hiểm','COD','đơn hàng','thu nhập','tổng kết','cuối tháng',
    'câu hỏi','chia sẻ mẹo','tip:','mẹo:','kinh nghiệm','cơ chế','chính sách','khiếu nại',
    'bom hàng','đánh giá 1 sao','shipper bị','phạt','kẹt xe','ngập','tránh','cẩn thận',
    'test','Test','a','1','Hi','hello'];

$allPosts = $db->fetchAll("SELECT id, content, images, video_url FROM posts WHERE `status`='active' AND images IS NOT NULL AND images != '[]' AND images != ''", []);
$removed = 0;

foreach ($allPosts as $p) {
    $content = mb_strtolower($p['content'] ?? '', 'UTF-8');
    $shouldRemove = false;
    
    // Check if content matches text-only patterns
    foreach ($textKeywords as $kw) {
        if (mb_strpos($content, mb_strtolower($kw, 'UTF-8')) !== false) {
            $shouldRemove = true;
            break;
        }
    }
    
    // Keep images for scenic/photo posts
    $keepKeywords = ['đẹp quá','phong cảnh','chụp','view','hoàng hôn','bình minh','biển','sông','cầu','ruộng','sương mù','Hạ Long','Sa Pa','Đà Lạt','Ninh Bình','Hội An','Phú Quốc','Tam Đảo','hồ','núi','ảnh'];
    foreach ($keepKeywords as $kk) {
        if (mb_strpos($content, mb_strtolower($kk, 'UTF-8')) !== false) {
            $shouldRemove = false;
            break;
        }
    }
    
    // Very short posts (< 20 chars) = probably test, remove images
    if (mb_strlen($p['content'] ?? '') < 20) $shouldRemove = true;
    
    if ($shouldRemove) {
        $db->query("UPDATE posts SET images='[]' WHERE id=?", [$p['id']]);
        $removed++;
    }
}
echo "Removed images from {$removed} mismatched posts\n\n";

// ===== STEP 2: Delete low quality/test posts =====
echo "=== Step 2: Clean test posts ===\n";
$deleted = 0;
$testPosts = $db->fetchAll("SELECT id, content FROM posts WHERE `status`='active' AND (content IN ('a','1','test','Test','Hi','hello','Test seed post','Test query post','test check') OR LENGTH(content) < 3)", []);
foreach ($testPosts as $tp) {
    $db->query("UPDATE posts SET `status`='deleted' WHERE id=?", [$tp['id']]);
    $deleted++;
}
echo "Deleted {$deleted} test posts\n\n";

// ===== STEP 3: Count remaining =====
$total = $db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'", []);
$withImg = $db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND images IS NOT NULL AND images != '[]'", []);
$withVid = $db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND video_url IS NOT NULL AND video_url != ''", []);
echo "Current: {$total['c']} posts, {$withImg['c']} with images, {$withVid['c']} with video\n";
echo "DONE!\n";
