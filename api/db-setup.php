<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

echo "=== STEP 1: Add Shipper Plus plan ===\n";
$exists=$d->fetchOne("SELECT id FROM subscription_plans WHERE slug='plus'");
if($exists){
    echo "Already exists id={$exists['id']}, updating...\n";
    $d->query("UPDATE subscription_plans SET name='Shipper Plus', price=29000, duration_days=30, badge='💜 PLUS', badge_color='#7C3AED', max_posts_per_day=9999, priority_support=1, is_active=1, sort_order=1, features=? WHERE slug='plus'",
        [json_encode(['Đăng bài không giới hạn','Tin nhắn không giới hạn','Gọi điện không giới hạn','Tham gia nhóm không giới hạn','20 sản phẩm Marketplace','Bài viết ưu tiên trong feed','Thống kê chi tiết profile','Lọc khu vực nâng cao','Hỗ trợ ưu tiên'],JSON_UNESCAPED_UNICODE)]);
} else {
    echo "Creating new plan...\n";
    $d->query("INSERT INTO subscription_plans (name,slug,price,duration_days,features,badge,badge_color,max_posts_per_day,priority_support,is_active,sort_order) VALUES ('Shipper Plus','plus',29000,30,?,'\xF0\x9F\x92\x9C PLUS','#7C3AED',9999,1,1,1)",
        [json_encode(['Đăng bài không giới hạn','Tin nhắn không giới hạn','Gọi điện không giới hạn','Tham gia nhóm không giới hạn','20 sản phẩm Marketplace','Bài viết ưu tiên trong feed','Thống kê chi tiết profile','Lọc khu vực nâng cao','Hỗ trợ ưu tiên'],JSON_UNESCAPED_UNICODE)]);
}

echo "\n=== STEP 2: Update Free plan features ===\n";
$d->query("UPDATE subscription_plans SET max_posts_per_day=10, features=?, sort_order=0 WHERE slug='free'",
    [json_encode(['10 bài đăng/ngày','Xem feed, comment, like','Cảnh báo giao thông','50 tin nhắn/tháng','Tham gia 10 nhóm','3 sản phẩm Marketplace','Bản đồ đầy đủ','Gọi điện 10 phút/ngày'],JSON_UNESCAPED_UNICODE)]);
echo "Free plan updated\n";

echo "\n=== STEP 3: Deactivate old plans ===\n";
$d->query("UPDATE subscription_plans SET is_active=0, sort_order=99 WHERE slug IN ('pro','vip','premium')");
echo "Pro/VIP/Premium deactivated\n";

echo "\n=== STEP 4: Migrate existing paid subscribers to Plus ===\n";
$plusPlan=$d->fetchOne("SELECT id FROM subscription_plans WHERE slug='plus'");
if($plusPlan){
    $migrated=$d->query("UPDATE user_subscriptions SET plan_id=? WHERE plan_id IN (2,3,4) AND `status`='active'", [$plusPlan['id']]);
    echo "Migrated active subscribers to Plus (plan_id={$plusPlan['id']})\n";
}

echo "\n=== STEP 5: Add yearly price column if needed ===\n";
try{
    $d->query("ALTER TABLE subscription_plans ADD COLUMN yearly_price DECIMAL(10,2) DEFAULT NULL AFTER price");
    echo "Added yearly_price column\n";
}catch(Throwable $e){
    echo "yearly_price already exists\n";
}
$d->query("UPDATE subscription_plans SET yearly_price=249000 WHERE slug='plus'");
echo "Plus yearly=249000\n";

echo "\n=== FINAL STATE ===\n";
$plans=$d->fetchAll("SELECT id,name,slug,price,yearly_price,duration_days,badge,is_active,sort_order,max_posts_per_day FROM subscription_plans ORDER BY sort_order");
foreach($plans as $p){
    $active=$p['is_active']?'✅':'❌';
    echo "{$active} id={$p['id']} {$p['name']} ({$p['slug']}) {$p['price']}đ/month yearly={$p['yearly_price']} {$p['duration_days']}d badge={$p['badge']} posts/day={$p['max_posts_per_day']}\n";
}

echo "\nDONE\n";
