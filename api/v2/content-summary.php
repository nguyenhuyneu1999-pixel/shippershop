<?php
// ShipperShop API v2 — Content Summary
// AI-style summary of trending content, hot topics, platform pulse
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

$data=cache_remember('content_summary_'.$hours, function() use($d,$hours) {
    // Top hashtags
    $posts=$d->fetchAll("SELECT content FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR) AND content LIKE '%#%'");
    $tags=[];
    foreach($posts as $p){
        preg_match_all('/#([a-zA-Z0-9_\x{00C0}-\x{024F}]+)/u',$p['content']??'',$m);
        foreach($m[1]??[] as $t){$t=mb_strtolower($t);$tags[$t]=($tags[$t]??0)+1;}
    }
    arsort($tags);$topTags=array_slice($tags,0,10,true);

    // Most active hours
    $hourStats=$d->fetchAll("SELECT HOUR(created_at) as h,COUNT(*) as c FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR) GROUP BY HOUR(created_at) ORDER BY c DESC LIMIT 5");

    // Content types distribution
    $types=$d->fetchAll("SELECT type,COUNT(*) as c FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR) GROUP BY type ORDER BY c DESC");

    // Engagement rate
    $engPosts=$d->fetchAll("SELECT likes_count,comments_count,views FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR) AND views>0");
    $totalEng=0;$totalViews=0;
    foreach($engPosts as $ep){$totalEng+=intval($ep['likes_count'])+intval($ep['comments_count']);$totalViews+=intval($ep['views']);}
    $engRate=$totalViews>0?round($totalEng/$totalViews*100,2):0;

    // Word cloud (top words from content)
    $allContent=$d->fetchAll("SELECT content FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR) LIMIT 50");
    $words=[];$stopWords=['la','cua','va','cho','trong','nay','den','duoc','khong','da','se','co','tu','voi','tai'];
    foreach($allContent as $ac){
        $w=preg_split('/[\s,.\-!?]+/u',mb_strtolower($ac['content']??''));
        foreach($w as $word){
            $word=trim($word);
            if(mb_strlen($word)>=3&&!in_array($word,$stopWords)&&!preg_match('/^[#@\d]/',$word)){
                $words[$word]=($words[$word]??0)+1;
            }
        }
    }
    arsort($words);$topWords=array_slice($words,0,20,true);

    return [
        'period_hours'=>$hours,
        'hashtags'=>$topTags,
        'peak_hours'=>$hourStats,
        'content_types'=>$types,
        'engagement_rate'=>$engRate,
        'top_words'=>$topWords,
        'total_posts_in_period'=>count($engPosts),
    ];
}, 300);

echo json_encode(['success'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
