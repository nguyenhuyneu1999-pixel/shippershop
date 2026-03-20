<?php
error_reporting(E_ALL); ini_set("display_errors",1);
header("Content-Type: text/plain");
require_once __DIR__ . '/../includes/db.php';
$d = db();

// Fix titles that start with "FB:" or "TK:" - remove prefix
$d->query("UPDATE content_queue SET title = TRIM(SUBSTR(title, 4)) WHERE title LIKE 'FB: %' OR title LIKE 'TK: %'");
echo "✅ Cleaned titles\n";

// Add hashtags + CTA to Facebook posts that don't have them
$fbPosts = $d->fetchAll("SELECT id, content FROM content_queue WHERE type = 'facebook' AND `status` = 'pending' AND content NOT LIKE '%#shipper%' LIMIT 50");
$count = 0;
foreach ($fbPosts as $p) {
    $extra = "\n\n📱 Tham gia cộng đồng shipper: shippershop.vn\n\n#shipper #giaohang #congdongshipper #GHTK #GHN #shippershop";
    $d->query("UPDATE content_queue SET content = CONCAT(content, ?) WHERE id = ?", [$extra, $p['id']]);
    $count++;
}
echo "✅ Added hashtags to $count FB posts\n";

// Fix TikTok posts - ensure they have hashtags
$tkPosts = $d->fetchAll("SELECT id, content FROM content_queue WHERE type = 'tiktok' AND `status` = 'pending' AND content NOT LIKE '%#shipper%' LIMIT 50");
$count2 = 0;
foreach ($tkPosts as $p) {
    $extra = "\n\n#shipper #giaohang #tiktokshipper #GHTK #GHN #fyp #viral";
    $d->query("UPDATE content_queue SET content = CONCAT(content, ?) WHERE id = ?", [$extra, $p['id']]);
    $count2++;
}
echo "✅ Added hashtags to $count2 TK posts\n";

// Show stats
$stats = $d->fetchAll("SELECT type, `status`, COUNT(*) c FROM content_queue GROUP BY type, `status` ORDER BY type, `status`");
echo "\n📊 Queue stats:\n";
foreach ($stats as $s) {
    echo "  {$s['type']} / {$s['status']}: {$s['c']}\n";
}
