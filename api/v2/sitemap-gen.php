<?php
// ShipperShop API v2 — Dynamic Sitemap Generator
// Generates sitemap.xml from actual content
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/cache.php';

$format=$_GET['format']??'xml';
$d=db();

$sitemap=cache_remember('sitemap_xml', function() use($d) {
    $baseUrl='https://shippershop.vn';
    $urls=[];

    // Static pages
    $pages=['','login.html','register.html','groups.html','marketplace.html','traffic.html','map.html','people.html','wallet.html'];
    foreach($pages as $p){
        $urls[]=['loc'=>$baseUrl.'/'.($p?:'/'), 'priority'=>$p?'0.7':'1.0', 'changefreq'=>'daily'];
    }

    // Recent posts (last 200)
    $posts=$d->fetchAll("SELECT id,created_at FROM posts WHERE `status`='active' ORDER BY created_at DESC LIMIT 200");
    foreach($posts as $p){
        $urls[]=['loc'=>$baseUrl.'/post-detail.html?id='.$p['id'], 'lastmod'=>date('Y-m-d',strtotime($p['created_at'])), 'priority'=>'0.6', 'changefreq'=>'weekly'];
    }

    // Users with posts (top 100)
    $users=$d->fetchAll("SELECT id FROM users WHERE `status`='active' AND total_posts>0 ORDER BY total_posts DESC LIMIT 100");
    foreach($users as $u){
        $urls[]=['loc'=>$baseUrl.'/user.html?id='.$u['id'], 'priority'=>'0.5', 'changefreq'=>'weekly'];
    }

    // Groups
    $groups=$d->fetchAll("SELECT id FROM `groups` LIMIT 50");
    foreach($groups as $g){
        $urls[]=['loc'=>$baseUrl.'/group.html?id='.$g['id'], 'priority'=>'0.5', 'changefreq'=>'weekly'];
    }

    // Marketplace listings
    $listings=$d->fetchAll("SELECT id FROM marketplace_listings WHERE `status`='active' LIMIT 50");
    foreach($listings as $l){
        $urls[]=['loc'=>$baseUrl.'/listing.html?id='.$l['id'], 'priority'=>'0.5', 'changefreq'=>'monthly'];
    }

    return $urls;
}, 3600); // Cache 1 hour

if($format==='json'){
    header('Content-Type: application/json');
    echo json_encode(['success'=>true,'data'=>['urls'=>$sitemap,'count'=>count($sitemap)]]);
    exit;
}

// XML format
header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
foreach($sitemap as $url){
    echo "  <url>\n";
    echo "    <loc>".htmlspecialchars($url['loc'])."</loc>\n";
    if(isset($url['lastmod'])) echo "    <lastmod>".$url['lastmod']."</lastmod>\n";
    echo "    <changefreq>".($url['changefreq']??'weekly')."</changefreq>\n";
    echo "    <priority>".($url['priority']??'0.5')."</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>';
