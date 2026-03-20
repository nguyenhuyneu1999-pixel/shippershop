<?php
error_reporting(E_ALL); ini_set("display_errors",1);
header("Content-Type: text/plain");
require_once __DIR__ . '/../includes/db.php';
$d = db();

// FIX 1: Strip HTML from blog posts that got HTML content
echo "FIX 1: Strip HTML from posts\n";
$htmlPosts = $d->fetchAll("SELECT id, content FROM posts WHERE content LIKE '%<h1>%' OR content LIKE '%<blockquote>%' OR content LIKE '%<p>%'");
$fixed = 0;
foreach ($htmlPosts as $p) {
    $clean = strip_tags($p['content']);
    $clean = trim(preg_replace('/\s+/', ' ', $clean));
    // Make it look like a normal post
    $clean = str_replace(['  '], ["\n\n"], $clean);
    $d->query("UPDATE posts SET content = ? WHERE id = ?", [$clean, $p['id']]);
    $fixed++;
    echo "  Fixed #{$p['id']}: " . mb_substr($clean, 0, 50) . "...\n";
}
echo "Cleaned $fixed posts\n\n";

// FIX 2: Set proper types for real posts (currently empty)
echo "FIX 2: Set types for real posts\n";
$emptyType = $d->fetchAll("SELECT id, content FROM posts WHERE (type = '' OR type IS NULL) AND LENGTH(content) > 100");
$typeFixed = 0;
foreach ($emptyType as $p) {
    $c = mb_strtolower($p['content']);
    $type = 'discussion'; // default
    
    if (strpos($c, 'tips') !== false || strpos($c, 'mẹo') !== false || strpos($c, 'checklist') !== false || strpos($c, 'cách') !== false || strpos($c, 'lưu ý') !== false) {
        $type = 'tips';
    } elseif (strpos($c, 'hỏi') !== false || strpos($c, '?') !== false && substr_count($c, '?') >= 2) {
        $type = 'question';
    } elseif (strpos($c, 'cảnh báo') !== false || strpos($c, '⚠') !== false || strpos($c, 'cẩn thận') !== false) {
        $type = 'warning';
    } elseif (strpos($c, 'review') !== false || strpos($c, 'so sánh') !== false || strpos($c, 'ưu điểm') !== false) {
        $type = 'review';
    } elseif (strpos($c, '😂') !== false || strpos($c, 'hài') !== false || strpos($c, 'vui') !== false) {
        $type = 'fun';
    } elseif (strpos($c, 'thu nhập') !== false || strpos($c, 'lương') !== false) {
        $type = 'discussion';
    } elseif (strpos($c, '📍') !== false || strpos($c, 'khu') !== false) {
        $type = 'tips';
    }
    
    $d->query("UPDATE posts SET type = ? WHERE id = ?", [$type, $p['id']]);
    $typeFixed++;
    echo "  #{$p['id']} → $type\n";
}
echo "Set types for $typeFixed posts\n\n";

// FIX 3: Check subscription plans
echo "FIX 3: Check subscription plans\n";
$plans = $d->fetchAll("SELECT * FROM subscription_plans ORDER BY id");
echo "Current plans: " . count($plans) . "\n";
foreach ($plans as $p) {
    echo "  #{$p['id']} {$p['name']} - {$p['price']}đ\n";
}

// If only 2 plans, restore the 4 original plans
if (count($plans) < 4) {
    echo "\n⚠️ Plans missing! Restoring original 4...\n";
    
    // Check what's there
    $existIds = array_column($plans, 'id');
    
    $allPlans = [
        ['id' => 1, 'name' => 'Miễn phí', 'slug' => 'free', 'price' => 0, 'duration_days' => 99999, 'badge' => null, 'badge_color' => null,
         'features' => json_encode(['3 bài đăng/ngày','Xem feed, comment, like','Cảnh báo giao thông','Tin nhắn cơ bản'])],
        ['id' => 2, 'name' => 'Shipper Pro', 'slug' => 'pro', 'price' => 49000, 'duration_days' => 30, 'badge' => '⭐ PRO', 'badge_color' => '#ff9800',
         'features' => json_encode(['20 bài đăng/ngày','Badge ⭐ PRO','Tin nhắn không giới hạn','Ưu tiên hiển thị','Gọi điện 30 phút/ngày'])],
        ['id' => 3, 'name' => 'Shipper VIP', 'slug' => 'vip', 'price' => 99000, 'duration_days' => 30, 'badge' => '👑 VIP', 'badge_color' => '#7C3AED',
         'features' => json_encode(['Unlimited bài đăng','Badge 👑 VIP','Mọi tính năng Pro','Marketplace ưu tiên','Gọi điện không giới hạn'])],
        ['id' => 4, 'name' => 'Shipper Premium', 'slug' => 'premium', 'price' => 199000, 'duration_days' => 30, 'badge' => '💎 PREMIUM', 'badge_color' => '#EE4D2D',
         'features' => json_encode(['Tất cả tính năng VIP','Badge 💎 PREMIUM','Hỗ trợ ưu tiên 24/7','Quảng cáo bài viết','Analytics chi tiết'])],
    ];
    
    foreach ($allPlans as $plan) {
        if (!in_array($plan['id'], $existIds)) {
            try {
                $d->query("INSERT INTO subscription_plans (id, name, slug, price, duration_days, badge, badge_color, features) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", [
                    $plan['id'], $plan['name'], $plan['slug'], $plan['price'], $plan['duration_days'], $plan['badge'], $plan['badge_color'], $plan['features']
                ]);
                echo "  ✅ Added: {$plan['name']}\n";
            } catch (Exception $e) {
                echo "  ⚠️ {$plan['name']}: " . $e->getMessage() . "\n";
            }
        }
    }
}

echo "\nDone!\n";
