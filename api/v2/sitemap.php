<?php
// ShipperShop API v2 — Dynamic Sitemap Generator
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/xml; charset=utf-8');

$d=db();
$base='https://shippershop.vn';

$xml=cache_remember('sitemap_xml', function() use($d,$base) {
    $out='<?xml version="1.0" encoding="UTF-8"?>';
    $out.='<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

    // Static pages
    $pages=[''=>'daily','login.html'=>'monthly','register.html'=>'monthly','marketplace.html'=>'daily','groups.html'=>'daily','traffic.html'=>'hourly','people.html'=>'daily','wallet.html'=>'weekly','map.html'=>'weekly','gioi-thieu.html'=>'monthly'];
    foreach($pages as $p=>$freq){
        $out.='<url><loc>'.$base.'/'.$p.'</loc><changefreq>'.$freq.'</changefreq><priority>'.($p===''?'1.0':'0.7').'</priority></url>';
    }

    // Recent posts (top 200)
    $posts=$d->fetchAll("SELECT id,created_at FROM posts WHERE `status`='active' AND is_draft=0 ORDER BY created_at DESC LIMIT 200");
    foreach($posts as $p){
        $out.='<url><loc>'.$base.'/post-detail.html?id='.$p['id'].'</loc><lastmod>'.date('Y-m-d',strtotime($p['created_at'])).'</lastmod><changefreq>weekly</changefreq><priority>0.6</priority></url>';
    }

    // Groups
    $groups=$d->fetchAll("SELECT id FROM `groups` ORDER BY id");
    foreach($groups as $g){
        $out.='<url><loc>'.$base.'/group.html?id='.$g['id'].'</loc><changefreq>daily</changefreq><priority>0.5</priority></url>';
    }

    // Active users (top 100)
    $users=$d->fetchAll("SELECT id FROM users WHERE `status`='active' ORDER BY total_posts DESC LIMIT 100");
    foreach($users as $u){
        $out.='<url><loc>'.$base.'/user.html?id='.$u['id'].'</loc><changefreq>weekly</changefreq><priority>0.4</priority></url>';
    }

    // Marketplace
    $listings=$d->fetchAll("SELECT id FROM marketplace_listings WHERE `status`='active' ORDER BY created_at DESC LIMIT 50");
    foreach($listings as $l){
        $out.='<url><loc>'.$base.'/listing.html?id='.$l['id'].'</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>';
    }

    $out.='</urlset>';
    return $out;
}, 3600); // Cache 1 hour

echo $xml;
