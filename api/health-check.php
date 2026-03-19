<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

echo "=== SHIPPERSHOP HEALTH CHECK ===\n\n";

echo "1. Double-path images: ";
$bad=$d->fetchAll("SELECT id FROM posts WHERE images LIKE '%/uploads%/uploads%'");
echo count($bad).($bad?" ❌":" ✅")."\n";

echo "2. Empty active posts: ";
echo $d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE (content IS NULL OR content='') AND `status`='active'")['c']."\n";

echo "3. Invalid user status: ";
echo $d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status` NOT IN ('active','banned','deleted','suspended')")['c']."\n";

echo "4. Orphaned comments: ";
$orph=$d->fetchOne("SELECT COUNT(*) as c FROM comments c LEFT JOIN posts p ON c.post_id=p.id WHERE p.id IS NULL");
echo $orph['c']."\n";

echo "5. wallet-api.php getPdo: ";
$wc=file_get_contents(__DIR__.'/wallet-api.php');
echo (strpos($wc,'getPdo')!==false?"❌ FOUND":"✅ Clean")."\n";

echo "6. Push subscriptions: ";
echo $d->fetchOne("SELECT COUNT(*) as c FROM push_subscriptions")['c']."\n";

echo "7. Conversations with invalid status: ";
try{echo $d->fetchOne("SELECT COUNT(*) as c FROM conversations WHERE `status` NOT IN ('active','pending')")['c']."\n";}
catch(Throwable $e){echo "N/A\n";}

echo "8. Posts without user: ";
$noUser=$d->fetchOne("SELECT COUNT(*) as c FROM posts p LEFT JOIN users u ON p.user_id=u.id WHERE u.id IS NULL");
echo $noUser['c']."\n";

echo "9. Group posts without group: ";
$noGrp=$d->fetchOne("SELECT COUNT(*) as c FROM group_posts gp LEFT JOIN `groups` g ON gp.group_id=g.id WHERE g.id IS NULL");
echo $noGrp['c']."\n";

echo "10. Marketplace listings without user: ";
$noMkUser=$d->fetchOne("SELECT COUNT(*) as c FROM marketplace_listings l LEFT JOIN users u ON l.user_id=u.id WHERE u.id IS NULL");
echo $noMkUser['c']."\n";

echo "11. sitemap.xml works: ";
$sm=@file_get_contents('https://shippershop.vn/sitemap.xml');
echo ($sm&&strpos($sm,'<urlset')!==false?"✅":"❌")."\n";

echo "12. robots.txt works: ";
$rb=@file_get_contents('https://shippershop.vn/robots.txt');
echo ($rb&&strpos($rb,'User-agent')!==false?"✅":"❌")."\n";

echo "13. share.php works: ";
$sh=@file_get_contents('https://shippershop.vn/share.php?type=post&id=575');
echo ($sh&&strpos($sh,'og:title')!==false?"✅":"❌")."\n";

echo "14. OG image exists: ";
$og=@get_headers('https://shippershop.vn/icons/og-image.png');
echo ($og&&strpos($og[0],'200')!==false?"✅":"❌")."\n";

echo "15. VAPID keys consistent: ";
require_once __DIR__.'/../includes/vapid_keys.php';
echo (defined('VAPID_PUBLIC_KEY')&&strlen(VAPID_PUBLIC_KEY)>50?"✅":"❌")."\n";

echo "16. Tables count: ";
$tables=$d->fetchAll("SHOW TABLES");
echo count($tables)."\n";

echo "\n=== SUMMARY ===\n";
echo "Users: ".$d->fetchOne("SELECT COUNT(*) as c FROM users")['c']."\n";
echo "Posts: ".$d->fetchOne("SELECT COUNT(*) as c FROM posts")['c']."\n";
echo "Comments: ".$d->fetchOne("SELECT COUNT(*) as c FROM comments")['c']."\n";
echo "Groups: ".$d->fetchOne("SELECT COUNT(*) as c FROM `groups`")['c']."\n";
echo "Group Posts: ".$d->fetchOne("SELECT COUNT(*) as c FROM group_posts")['c']."\n";
echo "Marketplace: ".$d->fetchOne("SELECT COUNT(*) as c FROM marketplace_listings")['c']."\n";
echo "Messages: ".$d->fetchOne("SELECT COUNT(*) as c FROM messages")['c']."\n";
echo "DB size: ".$d->fetchOne("SELECT ROUND(SUM(data_length+index_length)/1024/1024,2) as mb FROM information_schema.tables WHERE table_schema=DATABASE()")['mb']." MB\n";
echo "PHP: ".PHP_VERSION."\n";
echo "Server time: ".date('Y-m-d H:i:s')."\n";
