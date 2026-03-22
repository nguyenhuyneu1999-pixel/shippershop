<?php
// ShipperShop API v2 — Trending Topics V2
// Real-time trending: keywords, hashtags, provinces, companies
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

try {

$hours=min(intval($_GET['hours']??24),168);

$data=cache_remember('trending_v2_'.$hours, function() use($d,$hours) {
    $posts=$d->fetchAll("SELECT content,province,district FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR)");

    // Extract keywords
    $wordFreq=[];$hashFreq=[];$provinceFreq=[];
    $stopWords=['la','va','cua','cho','trong','voi','nhung','cac','mot','da','duoc','co','khong','den','tu','se','tai','o','di','ve','nha','em','anh','chi','oi','nhe','a','ah','thi','ma','bai','hay','nay','day','roi','gi','sao','lam','het','qua','nhu','rat','cung','vay','the','nao','minh','ban','ho','no','tren','duoi'];
    foreach($posts as $p){
        $content=mb_strtolower($p['content']??'');
        // Hashtags
        preg_match_all('/#([a-zA-Z0-9_\x{00C0}-\x{024F}]+)/u',$content,$hm);
        foreach(($hm[1]??[]) as $h){$hashFreq[$h]=($hashFreq[$h]??0)+1;}
        // Words
        $words=preg_split('/[\s,.\-!?:;()\[\]{}]+/u',$content);
        foreach($words as $w){$w=trim($w,'#@');if(mb_strlen($w)>=3&&!in_array($w,$stopWords)){$wordFreq[$w]=($wordFreq[$w]??0)+1;}}
        // Provinces
        if(!empty($p['province'])){$provinceFreq[$p['province']]=($provinceFreq[$p['province']]??0)+1;}
    }

    arsort($wordFreq);arsort($hashFreq);arsort($provinceFreq);
    $topWords=[];foreach(array_slice($wordFreq,0,15,true) as $w=>$c){$topWords[]=['word'=>$w,'count'=>$c];}
    $topHash=[];foreach(array_slice($hashFreq,0,10,true) as $h=>$c){$topHash[]=['tag'=>'#'.$h,'count'=>$c];}
    $topProv=[];foreach(array_slice($provinceFreq,0,10,true) as $p=>$c){$topProv[]=['province'=>$p,'count'=>$c];}

    return ['keywords'=>$topWords,'hashtags'=>$topHash,'provinces'=>$topProv,'posts_analyzed'=>count($posts),'window_hours'=>$hours];
}, 300);

echo json_encode(['success'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
