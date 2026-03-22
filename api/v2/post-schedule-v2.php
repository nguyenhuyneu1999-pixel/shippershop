<?php
// ShipperShop API v2 — Post Schedule v2 (Enhanced)
// Multi-post scheduling, recurring posts, optimal time suggestions
// session removed: JWT auth only
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

function psv2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

// Optimal posting times (based on engagement data)
if($action==='optimal_times'){
    $data=cache_remember('optimal_post_times', function() use($d) {
        $hourly=$d->fetchAll("SELECT HOUR(created_at) as hour,AVG(likes_count+comments_count) as avg_engagement,COUNT(*) as post_count FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY HOUR(created_at) ORDER BY avg_engagement DESC");
        $daily=$d->fetchAll("SELECT DAYOFWEEK(created_at) as dow,AVG(likes_count+comments_count) as avg_engagement,COUNT(*) as post_count FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DAYOFWEEK(created_at) ORDER BY avg_engagement DESC");
        $dayNames=['','CN','T2','T3','T4','T5','T6','T7'];
        foreach($daily as &$dd){$dd['day_name']=$dayNames[intval($dd['dow'])]??'';}unset($dd);
        // Top 3 best times
        $bestTimes=[];
        foreach(array_slice($hourly,0,3) as $h){$bestTimes[]=$h['hour'].':00 ('.round(floatval($h['avg_engagement']),1).' avg)';}
        return ['hourly'=>$hourly,'daily'=>$daily,'best_times'=>$bestTimes];
    }, 3600);
    psv2_ok('OK',$data);
}

// Bulk schedule
if($action==='bulk'&&$_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);
    $items=$input['items']??[];
    if(!is_array($items)||count($items)<1) psv2_ok('No items');
    if(count($items)>20) psv2_ok('Max 20 items');

    $pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    $created=0;
    foreach($items as $item){
        $content=trim($item['content']??'');
        $schedAt=$item['scheduled_at']??'';
        if(!$content||!$schedAt) continue;
        $pdo->prepare("INSERT INTO content_queue (user_id,content,`status`,scheduled_at,created_at) VALUES (?,?,'scheduled',?,NOW())")->execute([$uid,$content,$schedAt]);
        $created++;
    }
    psv2_ok('Da len lich '.$created.' bai',['created'=>$created]);
}

// Recurring schedule setup
if($action==='recurring'&&$_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);
    $content=trim($input['content']??'');
    $frequency=$input['frequency']??'daily'; // daily, weekly, biweekly
    $count=min(intval($input['count']??7),30);
    $startDate=$input['start_date']??date('Y-m-d',strtotime('+1 day'));
    $time=$input['time']??'08:00';

    if(!$content) psv2_ok('Missing content');

    $pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    $intervals=['daily'=>'+1 day','weekly'=>'+1 week','biweekly'=>'+2 weeks'];
    $interval=$intervals[$frequency]??'+1 day';
    $created=0;$currentDate=strtotime($startDate);
    for($i=0;$i<$count;$i++){
        $schedAt=date('Y-m-d',$currentDate).' '.$time.':00';
        $pdo->prepare("INSERT INTO content_queue (user_id,content,`status`,scheduled_at,created_at) VALUES (?,?,'scheduled',?,NOW())")->execute([$uid,$content,$schedAt]);
        $created++;
        $currentDate=strtotime($interval,$currentDate);
    }
    psv2_ok('Da tao '.$created.' bai dinh ky',['created'=>$created,'frequency'=>$frequency]);
}

psv2_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
