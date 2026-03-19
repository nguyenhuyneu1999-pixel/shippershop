<?php
header('Content-Type: application/xml; charset=utf-8');
require_once __DIR__ . '/includes/db.php';
$d = db();
$base = 'https://shippershop.vn';

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// Static pages
$pages = [
    ['/', '2026-03-19', 'daily', '1.0'],
    ['/login.html', '2026-03-01', 'monthly', '0.5'],
    ['/register.html', '2026-03-01', 'monthly', '0.5'],
    ['/marketplace.html', '2026-03-19', 'daily', '0.8'],
    ['/traffic.html', '2026-03-19', 'hourly', '0.7'],
    ['/groups.html', '2026-03-19', 'daily', '0.8'],
    ['/people.html', '2026-03-19', 'daily', '0.6'],
    ['/map.html', '2026-03-19', 'daily', '0.6'],
];
foreach ($pages as $p) {
    echo '<url><loc>' . $base . $p[0] . '</loc><lastmod>' . $p[1] . '</lastmod><changefreq>' . $p[2] . '</changefreq><priority>' . $p[3] . '</priority></url>';
}

// Posts (latest 500)
$posts = $d->fetchAll("SELECT id, created_at FROM posts WHERE `status` = 'active' ORDER BY id DESC LIMIT 500");
foreach ($posts as $p) {
    echo '<url><loc>' . $base . '/post-detail.html?id=' . $p['id'] . '</loc><lastmod>' . substr($p['created_at'], 0, 10) . '</lastmod><changefreq>weekly</changefreq><priority>0.6</priority></url>';
}

// Groups
$groups = $d->fetchAll("SELECT slug, updated_at FROM `groups` WHERE `status` = 'active'");
foreach ($groups as $g) {
    $mod = $g['updated_at'] ? substr($g['updated_at'], 0, 10) : '2026-03-19';
    echo '<url><loc>' . $base . '/group.html?slug=' . $g['slug'] . '</loc><lastmod>' . $mod . '</lastmod><changefreq>daily</changefreq><priority>0.7</priority></url>';
}

// Marketplace
$listings = $d->fetchAll("SELECT id, created_at FROM marketplace_listings WHERE `status` = 'active' ORDER BY id DESC LIMIT 100");
foreach ($listings as $l) {
    echo '<url><loc>' . $base . '/listing.html?id=' . $l['id'] . '</loc><lastmod>' . substr($l['created_at'], 0, 10) . '</lastmod><changefreq>weekly</changefreq><priority>0.6</priority></url>';
}

// User profiles (active users with posts)
$users = $d->fetchAll("SELECT DISTINCT u.id FROM users u JOIN posts p ON p.user_id = u.id WHERE u.status = 'active' LIMIT 200");
foreach ($users as $u) {
    echo '<url><loc>' . $base . '/user.html?id=' . $u['id'] . '</loc><changefreq>weekly</changefreq><priority>0.4</priority></url>';
}

echo '</urlset>';
