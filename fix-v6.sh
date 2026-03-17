#!/bin/bash
cd ~/public_html || exit 1
cp index.html index.html.bak.$(date +%Y%m%d%H%M%S)

echo "=== Fix API paths ==="
# Fix auth-check path in all 4 new API files
sed -i "s|require_once __DIR__ . '/../includes/auth-check.php';|require_once __DIR__ . '/auth-check.php';|" api/groups.php api/messages-api.php api/friends.php api/user-page.php
echo "✅ API paths fixed"

echo "=== Fix FAB size + function ==="
# Thu nhỏ FAB camera + mở modal đăng bài
sed -i 's|<div class="mnav-fab" style="width:44px;height:44px"><i class="fas fa-camera" id="fabIcon" style="font-size:18px"></i></div>|<div class="mnav-fab" style="width:42px;height:42px"><i class="fas fa-camera" id="fabIcon" style="font-size:16px"></i></div>|' index.html
echo "✅ FAB size"

echo "=== Fix post spacing ==="
# Giảm khoảng cách giữa bài viết
sed -i 's|.post-card{background:var(--card);border:none;border-radius:0;margin-bottom:8px;display:flex;transition:.1s;overflow:hidden;}|.post-card{background:var(--card);border:none;border-radius:0;margin-bottom:1px;display:flex;transition:.1s;overflow:hidden;}|' index.html
echo "✅ Post spacing"

echo "=== Fix feed header - 4 items no wrap ==="
# Sort buttons đều nhau trên 1 dòng
sed -i 's|.feed-header{background:var(--card);border:none;border-radius:0;padding:8px 4px;display:flex;gap:0;align-items:center;margin-bottom:8px;flex-wrap:nowrap;}|.feed-header{background:var(--card);border:none;border-radius:0;padding:6px 0;display:flex;gap:0;align-items:center;margin-bottom:1px;flex-wrap:nowrap;}|' index.html
echo "✅ Feed header"

echo "=== Fix tab bar cut off ==="
# Tăng padding-top cho body
sed -i 's|body{padding-top:96px;}|body{padding-top:100px;}|' index.html
echo "✅ Tab bar padding"

echo "=== Fix conversations table ==="
# Add missing columns to conversations table via PHP
cat > /tmp/fix_conv.php << 'PHPFIX'
<?php
require_once '/home/nhshiw2j/public_html/includes/db.php';
try {
    $pdo = db();
    $pdo->exec("ALTER TABLE conversations ADD COLUMN IF NOT EXISTS user1_id INT NULL");
    $pdo->exec("ALTER TABLE conversations ADD COLUMN IF NOT EXISTS user2_id INT NULL");
    $pdo->exec("ALTER TABLE conversations ADD COLUMN IF NOT EXISTS last_message TEXT NULL");
    $pdo->exec("ALTER TABLE conversations ADD COLUMN IF NOT EXISTS last_message_at TIMESTAMP NULL");
    $pdo->exec("ALTER TABLE messages ADD COLUMN IF NOT EXISTS conversation_id INT NULL");
    $pdo->exec("ALTER TABLE messages ADD COLUMN IF NOT EXISTS sender_id INT NULL");
    $pdo->exec("ALTER TABLE messages ADD COLUMN IF NOT EXISTS content TEXT NULL");
    $pdo->exec("ALTER TABLE messages ADD COLUMN IF NOT EXISTS is_read TINYINT(1) DEFAULT 0");
    echo "OK";
} catch(Exception $e) { echo "ERR: ".$e->getMessage(); }
PHPFIX
php /tmp/fix_conv.php
echo ""
echo "✅ Conversations table fixed"

echo ""
echo "🎉 Done! Test:"
echo "https://shippershop.vn/api/user-page.php?id=3"
echo "https://shippershop.vn/api/messages-api.php"
