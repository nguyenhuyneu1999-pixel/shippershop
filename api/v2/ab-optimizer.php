<?php
// ShipperShop API v2 — A/B Content Optimizer
// Test multiple content variations and recommend best performer
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$action=$_GET['action']??'';

function abo_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='ab_optimizer_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $tests=$row?json_decode($row['value'],true):[];
    $active=array_values(array_filter($tests,function($t){return ($t['status']??'')==='active';}));
    $completed=array_values(array_filter($tests,function($t){return ($t['status']??'')==='completed';}));
    abo_ok('OK',['active'=>$active,'completed'=>array_slice($completed,0,10),'total'=>count($tests)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $tests=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='create'){
        $title=trim($input['title']??'');
        $variants=$input['variants']??[];// [{content, image}]
        if(!$title||count($variants)<2) abo_ok('Nhap tieu de va it nhat 2 phien ban');
        $maxId=0;foreach($tests as $t){if(intval($t['id']??0)>$maxId)$maxId=intval($t['id']);}
        foreach($variants as &$v){$v['likes']=0;$v['comments']=0;$v['views']=0;$v['ctr']=0;}unset($v);
        $tests[]=['id'=>$maxId+1,'title'=>$title,'variants'=>$variants,'status'=>'active','winner'=>null,'created_at'=>date('c')];
    }

    if($action==='record'){
        $testId=intval($input['test_id']??0);$variantIdx=intval($input['variant']??0);$metric=$input['metric']??'views';
        foreach($tests as &$t){
            if(intval($t['id']??0)===$testId&&isset($t['variants'][$variantIdx])){
                $t['variants'][$variantIdx][$metric]=intval($t['variants'][$variantIdx][$metric]??0)+1;
            }
        }unset($t);
    }

    if($action==='complete'){
        $testId=intval($input['test_id']??0);
        foreach($tests as &$t){
            if(intval($t['id']??0)===$testId){
                $t['status']='completed';
                $bestIdx=0;$bestScore=0;
                foreach($t['variants'] as $idx=>$v){
                    $score=intval($v['likes']??0)*3+intval($v['comments']??0)*5+intval($v['views']??0);
                    if($score>$bestScore){$bestScore=$score;$bestIdx=$idx;}
                }
                $t['winner']=$bestIdx;$t['completed_at']=date('c');
            }
        }unset($t);
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($tests)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($tests))]);
    abo_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
