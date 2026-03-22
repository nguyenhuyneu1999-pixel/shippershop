<?php
// ShipperShop API v2 — Draft Collaboration
// Tinh nang: Cong tac soan bai viet nhom voi gop y va phe duyet
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

function dc2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='draft_collab_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $drafts=$row?json_decode($row['value'],true):[];
    foreach($drafts as &$dr){
        $dr['feedback_count']=count($dr['feedback']??[]);
        $dr['approved']=count(array_filter($dr['feedback']??[],function($f){return ($f['type']??'')==='approve';}));
    }unset($dr);
    $pending=count(array_filter($drafts,function($d){return ($d['status']??'')==='draft';}));
    dc2_ok('OK',['drafts'=>$drafts,'count'=>count($drafts),'pending'=>$pending]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $drafts=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='create'){
        $title=trim($input['title']??'');$content=trim($input['content']??'');
        $collaborators=array_map('intval',$input['collaborators']??[]);
        if(!$content) dc2_ok('Nhap noi dung');
        $maxId=0;foreach($drafts as $dr){if(intval($dr['id']??0)>$maxId)$maxId=intval($dr['id']);}
        $drafts[]=['id'=>$maxId+1,'title'=>$title,'content'=>$content,'collaborators'=>$collaborators,'feedback'=>[],'status'=>'draft','author_id'=>$uid,'created_at'=>date('c'),'updated_at'=>date('c')];
    }

    if($action==='feedback'){
        $draftId=intval($input['draft_id']??0);$text=trim($input['text']??'');$type=$input['type']??'comment'; // comment, approve, reject
        foreach($drafts as &$dr){
            if(intval($dr['id']??0)===$draftId){
                $dr['feedback'][]=(['user_id'=>$uid,'text'=>$text,'type'=>$type,'at'=>date('c')]);
                $approvals=count(array_filter($dr['feedback'],function($f){return ($f['type']??'')==='approve';}));
                if($approvals>=2) $dr['status']='approved';
            }
        }unset($dr);
    }

    if($action==='update'){
        $draftId=intval($input['draft_id']??0);
        foreach($drafts as &$dr){
            if(intval($dr['id']??0)===$draftId){
                if(isset($input['content'])) $dr['content']=trim($input['content']);
                if(isset($input['title'])) $dr['title']=trim($input['title']);
                $dr['updated_at']=date('c');
            }
        }unset($dr);
    }

    if($action==='publish'){
        $draftId=intval($input['draft_id']??0);
        foreach($drafts as &$dr){if(intval($dr['id']??0)===$draftId) $dr['status']='published';}unset($dr);
    }

    if(count($drafts)>50) $drafts=array_slice($drafts,-50);
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($drafts)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($drafts))]);
    dc2_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
