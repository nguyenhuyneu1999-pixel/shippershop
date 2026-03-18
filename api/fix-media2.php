<?php
set_time_limit(300);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain; charset=utf-8');
$db = db();

$imgDir = __DIR__ . '/../uploads/posts/';
if (!is_dir($imgDir)) mkdir($imgDir, 0755, true);

// Download themed images from Unsplash source (free, no API key needed)
$themes = [
    'road' => ['vietnam+road','motorbike+road','city+traffic','street+asia'],
    'rain' => ['rain+road','rainy+street','umbrella+rain'],
    'food' => ['vietnam+food','street+food+asia','banh+mi'],
    'sunset' => ['sunset+city','sunrise+river','golden+hour+city'],
    'package' => ['delivery+package','cardboard+box','shipping+box'],
    'coffee' => ['vietnamese+coffee','cafe+street','iced+coffee'],
    'bridge' => ['bridge+night+city','vietnam+bridge','river+bridge'],
    'field' => ['rice+field+vietnam','green+rice+paddy','rice+terrace'],
    'market' => ['vietnam+market','street+market+asia','night+market'],
    'helmet' => ['motorcycle+helmet','riding+gear','motorbike+rider'],
];

echo "=== Downloading themed images ===\n";
$themeImages = [];
foreach ($themes as $key => $queries) {
    $themeImages[$key] = [];
    foreach ($queries as $qi => $q) {
        $fname = "theme_{$key}_{$qi}.jpg";
        $path = $imgDir . $fname;
        $dbPath = "/uploads/posts/{$fname}";
        
        if (!file_exists($path)) {
            // Use Unsplash source for themed images
            $w = rand(640,800);
            $h = rand(400,600);
            $url = "https://source.unsplash.com/{$w}x{$h}/?" . urlencode($q);
            $ctx = stream_context_create(['http'=>['timeout'=>10,'follow_location'=>1,'max_redirects'=>3]]);
            $data = @file_get_contents($url, false, $ctx);
            if ($data && strlen($data) > 5000) {
                file_put_contents($path, $data);
                echo "  DL: {$fname} ({$q})\n";
            } else {
                // Fallback to picsum
                $seed = crc32($q) % 1000;
                $url2 = "https://picsum.photos/seed/{$seed}/{$w}/{$h}.jpg";
                $data2 = @file_get_contents($url2);
                if ($data2 && strlen($data2) > 3000) {
                    file_put_contents($path, $data2);
                    echo "  DL(fallback): {$fname}\n";
                }
            }
        } else {
            echo "  EXISTS: {$fname}\n";
        }
        if (file_exists($path)) {
            $themeImages[$key][] = $dbPath;
        }
    }
}

// Videos
$videos = [
    '/uploads/videos/vid_3_1772440538_b41b51fe.mp4',
    '/uploads/videos/vid_2_1772428966_bc9dc62b.mp4',
    '/uploads/videos/vid_3_1772415179_e1326d96.mp4',
    '/uploads/videos/vid_3_1772474536_9349ff13.mp4',
    '/uploads/videos/vid_3_1773586906_b1a3e8ec.mp4',
];

// Content-to-theme mapping keywords
$contentThemeMap = [
    'road' => ['đường','kẹt','kẹt xe','CSGT','chốt','ngã tư','cầu','quốc lộ','KCN','Ciputra','Ecopark','PMH','Q7'],
    'rain' => ['mưa','ngập','ướt','trời mưa','bão','mùa mưa'],
    'food' => ['bánh','pizza','đồ ăn','cơm','nước','trà','cafe','tiệm bánh','quán','bánh giò','bánh mì'],
    'sunset' => ['hoàng hôn','bình minh','đẹp','nắng vàng','chiều','sáng sớm','view','phong cảnh','Hồ Tây','sông Hương','Ninh Kiều','phố cổ','Tràng An','Sa Pa','Mù Cang','Hạ Long','Hội An'],
    'package' => ['hàng dễ vỡ','bọc hàng','thùng ship','COD','giao hàng','túi giữ nhiệt','ba lô','bọc','xếp hàng'],
    'coffee' => ['cafe','nghỉ','ngồi','uống'],
    'bridge' => ['cầu Rồng','cầu Nhật Tân','cầu Vĩnh Tuy','cầu Chương Dương','cầu Tràng Tiền'],
    'field' => ['cánh đồng','ruộng','lúa','quê','bậc thang'],
    'market' => ['chợ','tiệm','cửa hàng'],
    'helmet' => ['mũ bảo hiểm','kính râm','áo khoác','áo mưa','bao tay','chống nắng','review'],
];

// Match posts to themes
echo "\n=== Assigning images to posts ===\n";
$allPosts = $db->fetchAll("SELECT id, content FROM posts WHERE `status`='active' AND (images IS NULL OR images='[]' OR images='') AND video_url IS NULL ORDER BY id DESC LIMIT 120", []);
echo "Posts without media: " . count($allPosts) . "\n";

$updatedImg = 0;
$updatedVid = 0;

foreach ($allPosts as $post) {
    $pid = $post['id'];
    $content = mb_strtolower($post['content'], 'UTF-8');
    $bestTheme = null;
    $bestScore = 0;
    
    // Find best matching theme
    foreach ($contentThemeMap as $theme => $keywords) {
        $score = 0;
        foreach ($keywords as $kw) {
            if (mb_strpos($content, mb_strtolower($kw, 'UTF-8')) !== false) {
                $score++;
            }
        }
        if ($score > $bestScore && !empty($themeImages[$theme])) {
            $bestScore = $score;
            $bestTheme = $theme;
        }
    }
    
    if ($bestTheme && $bestScore >= 1) {
        // Assign themed image
        $imgs = $themeImages[$bestTheme];
        $n = min(rand(1, 2), count($imgs));
        shuffle($imgs);
        $selected = array_slice($imgs, 0, $n);
        $db->query("UPDATE posts SET images=? WHERE id=?", [json_encode($selected), $pid]);
        $updatedImg++;
        echo "  🖼️ [{$pid}] theme={$bestTheme}(score={$bestScore}): " . mb_substr($post['content'], 0, 40, 'UTF-8') . "...\n";
    }
    
    // Assign video to posts mentioning video/record
    if (mb_strpos($content, 'video') !== false || mb_strpos($content, 'quay') !== false || mb_strpos($content, 'record') !== false) {
        $vid = $videos[array_rand($videos)];
        $db->query("UPDATE posts SET video_url=? WHERE id=?", [$vid, $pid]);
        $updatedVid++;
        echo "  🎬 [{$pid}] video: " . mb_substr($post['content'], 0, 40, 'UTF-8') . "...\n";
    }
}

echo "\n=== Results ===\n";
echo "Images assigned: {$updatedImg}\n";
echo "Videos assigned: {$updatedVid}\n";

$total = $db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'", []);
$withImg = $db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND images IS NOT NULL AND images != '[]' AND images != ''", []);
$withVid = $db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND video_url IS NOT NULL AND video_url != ''", []);
$textOnly = $total['c'] - $withImg['c'] - $withVid['c'];
echo "\nTotal: {$total['c']} posts\n";
echo "With images: {$withImg['c']}\n";
echo "With video: {$withVid['c']}\n";
echo "Text only: {$textOnly}\n";
echo "DONE!\n";
