<?php
// ShipperShop API v2 — Trend Detector
// Detect trending topics, hashtags, keywords in recent posts
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();$action=$_GET['action']??'';

function td_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$hours=min(intval($_GET['hours']??24),168);

if(!$action||$action==='keywords'){
    $data=cache_remember('trends_'.$hours, function() use($d,$hours) {
        $posts=$d->fetchAll("SELECT content FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR) LIMIT 200");
        $wordCounts=[];
        $stopWords=['la','va','cua','cho','trong','toi','ban','nay','voi','nhung','cac','mot','da','duoc','co','khong','den','tu','se','tai','o','di','ve','len','xuong','ra','vao','tren','duoi','day','do','the','nhu','ma'];
        foreach($posts as $p){
            $words=preg_split('/[\s,.\-!?#@()]+/u',mb_strtolower($p['content']??''));
            foreach($words as $w){
                $w=trim($w);
                if(mb_strlen($w)>=3&&!in_array($w,$stopWords)&&!is_numeric($w)){
                    $wordCounts[$w]=($wordCounts[$w]??0)+1;
                }
            }
        }
        arsort($wordCounts);
        $trending=[];
        foreach(array_slice($wordCounts,0,20,true) as $word=>$count){
            $trending[]=['keyword'=>$word,'count'=>$count,'is_hashtag'=>false];
        }

        // Hashtag trends
        $hashCounts=[];
        foreach($posts as $p){
            preg_match_all('/#([a-zA-Z0-9_\x{00C0}-\x{024F}]+)/u',$p['content']??'',$m);
            foreach(($m[1]??[]) as $h){$h=mb_strtolower($h);$hashCounts[$h]=($hashCounts[$h]??0)+1;}
        }
        arsort($hashCounts);
        $hashTrends=[];
        foreach(array_slice($hashCounts,0,10,true) as $tag=>$count){
            $hashTrends[]=['hashtag'=>'#'.$tag,'count'=>$count];
        }

        return ['keywords'=>$trending,'hashtags'=>$hashTrends,'posts_analyzed'=>count($posts),'window_hours'=>$hours];
    }, 300);
    td_ok('OK',$data);
}

// Rising: compare current window vs previous window
if($action==='rising'){
    $current=$d->fetchAll("SELECT content FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR) LIMIT 100");
    $previous=$d->fetchAll("SELECT content FROM posts WHERE `status`='active' AND created_at BETWEEN DATE_SUB(NOW(), INTERVAL ".($hours*2)." HOUR) AND DATE_SUB(NOW(), INTERVAL $hours HOUR) LIMIT 100");

    $countWords=function($posts) {
        $wc=[];
        foreach($posts as $p){foreach(preg_split('/\s+/u',mb_strtolower($p['content']??'')) as $w){if(mb_strlen($w)>=3) $wc[$w]=($wc[$w]??0)+1;}}
        return $wc;
    };
    $cur=$countWords($current);$prev=$countWords($previous);
    $rising=[];
    foreach($cur as $w=>$c){
        $prevC=$prev[$w]??0;
        if($c>=3&&$c>$prevC*1.5){
            $rising[]=['keyword'=>$w,'current'=>$c,'previous'=>$prevC,'growth'=>$prevC>0?round(($c-$prevC)/$prevC*100):999];
        }
    }
    usort($rising,function($a,$b){return $b['growth']-$a['growth'];});
    td_ok('OK',['rising'=>array_slice($rising,0,10)]);
}

td_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
