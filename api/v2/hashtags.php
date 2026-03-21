<?php
// ShipperShop API v2 — Hashtags
// Trending hashtags, posts by hashtag, auto-extract from content
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$action=$_GET['action']??'';

function ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

// Trending hashtags (cached 5min)
if(!$action||$action==='trending'){
    $period=$_GET['period']??'week'; // week, month, all
    $limit=min(intval($_GET['limit']??20),50);
    $dateFilter='';
    if($period==='week') $dateFilter="AND p.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)";
    elseif($period==='month') $dateFilter="AND p.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)";

    $tags=cache_remember('trending_tags_'.$period.'_'.$limit, function() use($d,$dateFilter,$limit) {
        // Extract hashtags from recent posts
        $posts=$d->fetchAll("SELECT content FROM posts WHERE `status`='active' $dateFilter ORDER BY created_at DESC LIMIT 500");
        $tagCounts=[];
        foreach($posts as $p){
            preg_match_all('/#([a-zA-Z0-9_\x{00C0}-\x{024F}]+)/u', $p['content']??'', $matches);
            foreach($matches[1] as $tag){
                $tag=mb_strtolower($tag);
                $tagCounts[$tag]=($tagCounts[$tag]??0)+1;
            }
        }
        arsort($tagCounts);
        $result=[];
        $i=0;
        foreach($tagCounts as $tag=>$count){
            if($i>=$limit) break;
            $result[]=['tag'=>$tag,'count'=>$count];
            $i++;
        }
        return $result;
    }, 300);
    ok('OK',$tags);
}

// Posts by hashtag
if($action==='posts'){
    $tag=trim($_GET['tag']??'');
    if(!$tag) fail('Missing tag');
    $page=max(1,intval($_GET['page']??1));$limit=min(intval($_GET['limit']??15),50);$offset=($page-1)*$limit;
    $uid=optional_auth();

    $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND content LIKE ?",['%#'.$tag.'%'])['c']);
    $posts=$d->fetchAll("SELECT p.*,u.fullname as user_name,u.avatar as user_avatar,u.shipping_company FROM posts p LEFT JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.content LIKE ? ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset",['%#'.$tag.'%']);

    if($uid&&$posts){
        $pids=array_column($posts,'id');
        if($pids){
            $ph=implode(',',array_fill(0,count($pids),'?'));
            $liked=$d->fetchAll("SELECT post_id FROM likes WHERE user_id=? AND post_id IN ($ph)",array_merge([$uid],$pids));
            $likedSet=array_flip(array_column($liked,'post_id'));
            foreach($posts as &$p){$p['user_liked']=isset($likedSet[$p['id']]);}unset($p);
        }
    }

    echo json_encode(['success'=>true,'data'=>['tag'=>$tag,'posts'=>$posts,'meta'=>['page'=>$page,'per_page'=>$limit,'total'=>$total,'total_pages'=>max(1,ceil($total/$limit))]]]);exit;
}

// Suggest hashtags (autocomplete)
if($action==='suggest'){
    $q=trim($_GET['q']??'');
    if(mb_strlen($q)<1) ok('OK',[]);
    $tags=cache_remember('tag_suggest_'.md5($q), function() use($d,$q) {
        $posts=$d->fetchAll("SELECT content FROM posts WHERE `status`='active' AND content LIKE ? ORDER BY created_at DESC LIMIT 200",['%#'.$q.'%']);
        $seen=[];$result=[];
        foreach($posts as $p){
            preg_match_all('/#([a-zA-Z0-9_\x{00C0}-\x{024F}]+)/u', $p['content']??'', $m);
            foreach($m[1] as $tag){
                $tag=mb_strtolower($tag);
                if(strpos($tag,$q)===0&&!isset($seen[$tag])){
                    $seen[$tag]=true;
                    $result[]=$tag;
                    if(count($result)>=10) break 2;
                }
            }
        }
        return $result;
    }, 120);
    ok('OK',$tags);
}

ok('OK',[]);
