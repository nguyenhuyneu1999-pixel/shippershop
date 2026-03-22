<?php
// ShipperShop API v2 — Drafts Sync
// Cross-device draft synchronization with conflict resolution
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

function ds_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='drafts_sync_'.$uid;

// GET: all synced drafts
if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $drafts=$row?json_decode($row['value'],true):[];
    // Sort by updated_at desc
    usort($drafts,function($a,$b){return strcmp($b['updated_at']??'',$a['updated_at']??'');});
    ds_ok('OK',['drafts'=>$drafts,'count'=>count($drafts),'last_sync'=>date('c')]);
}

// POST: sync drafts
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $drafts=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='save'){
        $draftId=$input['draft_id']??('draft_'.time().'_'.rand(100,999));
        $content=trim($input['content']??'');
        $title=trim($input['title']??'');
        $postType=$input['post_type']??'general';
        if(!$content&&!$title) ds_ok('Nhap noi dung');

        // Find existing or create new
        $found=false;
        foreach($drafts as &$dr){
            if(($dr['id']??'')===$draftId){
                $dr['content']=$content;$dr['title']=$title;$dr['post_type']=$postType;$dr['updated_at']=date('c');$dr['version']=intval($dr['version']??0)+1;
                $found=true;break;
            }
        }unset($dr);
        if(!$found){
            $drafts[]=['id'=>$draftId,'content'=>$content,'title'=>$title,'post_type'=>$postType,'created_at'=>date('c'),'updated_at'=>date('c'),'version'=>1];
        }
        if(count($drafts)>50) $drafts=array_slice($drafts,0,50);
    }

    if($action==='delete'){
        $draftId=$input['draft_id']??'';
        $drafts=array_values(array_filter($drafts,function($dr) use($draftId){return ($dr['id']??'')!==$draftId;}));
    }

    if($action==='clear'){$drafts=[];}

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($drafts)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($drafts))]);
    ds_ok($action==='delete'?'Da xoa':($action==='clear'?'Da xoa tat ca':'Da dong bo!'),['count'=>count($drafts)]);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
