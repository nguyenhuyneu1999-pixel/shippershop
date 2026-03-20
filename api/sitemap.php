<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/xml; charset=utf-8');
$d = db();
$base = 'https://shippershop.vn';
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// Static pages
$pages = ['','login.html','register.html','marketplace.html','traffic.html','groups.html','people.html','map.html','wallet.html','landing.html','leaderboard.html','guide.html','landing.html'];
foreach ($pages as $p) {
    echo "<url><loc>$base/$p</loc><changefreq>daily</changefreq><priority>".($p===''?'1.0':'0.7')."</priority></url>";
}

// Posts (top 200)
$posts = $d->fetchAll("SELECT id, created_at FROM posts WHERE `status`='active' ORDER BY created_at DESC LIMIT 200");
foreach ($posts as $p) {
    $date = substr($p['created_at'], 0, 10);
    echo "<url><loc>$base/post-detail.html?id={$p['id']}</loc><lastmod>$date</lastmod><changefreq>weekly</changefreq><priority>0.6</priority></url>";
}

// Groups
$groups = $d->fetchAll("SELECT id FROM `groups` WHERE `status`='active'");
foreach ($groups as $g) {
    echo "<url><loc>$base/group.html?id={$g['id']}</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>";
}

// Marketplace
$listings = $d->fetchAll("SELECT id, created_at FROM marketplace_listings WHERE `status`='active' ORDER BY created_at DESC LIMIT 100");
foreach ($listings as $l) {
    $date = substr($l['created_at'], 0, 10);
    echo "<url><loc>$base/listing.html?id={$l['id']}</loc><lastmod>$date</lastmod><changefreq>weekly</changefreq><priority>0.5</priority></url>";
}

echo '</urlset>';
