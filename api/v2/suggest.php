<?php
// ShipperShop API v2 — Search Suggestions (autocomplete)
// Returns matching users, hashtags, groups for search bar
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();
$q=trim($_GET['q']??'');

if(mb_strlen($q)<1){
    echo json_encode(['success'=>true,'data'=>[]]);exit;
}

$results=cache_remember('suggest_'.md5($q), function() use($d,$q) {
    $data=[];

    // Users (top 5)
    $users=$d->fetchAll("SELECT id,fullname,avatar,shipping_company,'user' as type FROM users WHERE `status`='active' AND fullname LIKE ? ORDER BY total_posts DESC LIMIT 5",['%'.$q.'%']);
    foreach($users as $u) $data[]=$u;

    // Groups (top 3)
    $groups=$d->fetchAll("SELECT id,name as fullname,avatar,'' as shipping_company,'group' as type FROM `groups` WHERE name LIKE ? LIMIT 3",['%'.$q.'%']);
    foreach($groups as $g) $data[]=$g;

    // Hashtags (top 3) — scan recent posts
    $posts=$d->fetchAll("SELECT content FROM posts WHERE `status`='active' AND content LIKE ? ORDER BY created_at DESC LIMIT 100",['%#'.$q.'%']);
    $tags=[];
    foreach($posts as $p){
        preg_match_all('/#([a-zA-Z0-9_\x{00C0}-\x{024F}]+)/u',$p['content']??'',$m);
        foreach($m[1] as $t){
            $t=mb_strtolower($t);
            if(strpos($t,mb_strtolower($q))===0&&!isset($tags[$t])){
                $tags[$t]=true;
                $data[]=['id'=>0,'fullname'=>'#'.$t,'avatar'=>'','shipping_company'=>'','type'=>'hashtag'];
                if(count($tags)>=3) break;
            }
        }
        if(count($tags)>=3) break;
    }

    return $data;
}, 60);

echo json_encode(['success'=>true,'data'=>$results],JSON_UNESCAPED_UNICODE);
