<?php
// ShipperShop API v2 — Content A/B Split Test
// Test two versions of post content to see which performs better
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$action=$_GET['action']??'';

function abs_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='ab_splits_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $splits=$row?json_decode($row['value'],true):[];
    // Enrich with post data
    foreach($splits as &$s){
        if(!empty($s['post_a_id'])){
            $pa=$d->fetchOne("SELECT likes_count,comments_count,shares_count FROM posts WHERE id=?",[$s['post_a_id']]);
            if($pa) $s['a_engagement']=intval($pa['likes_count'])+intval($pa['comments_count'])+intval($pa['shares_count']);
        }
        if(!empty($s['post_b_id'])){
            $pb=$d->fetchOne("SELECT likes_count,comments_count,shares_count FROM posts WHERE id=?",[$s['post_b_id']]);
            if($pb) $s['b_engagement']=intval($pb['likes_count'])+intval($pb['comments_count'])+intval($pb['shares_count']);
        }
        $s['winner']=($s['a_engagement']??0)>($s['b_engagement']??0)?'A':(($s['b_engagement']??0)>($s['a_engagement']??0)?'B':'tie');
    }unset($s);
    abs_ok('OK',['splits'=>$splits,'count'=>count($splits)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $splits=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='create'){
        $name=trim($input['name']??'');
        $textA=trim($input['text_a']??'');
        $textB=trim($input['text_b']??'');
        $postAId=intval($input['post_a_id']??0);
        $postBId=intval($input['post_b_id']??0);
        if(!$name) abs_ok('Nhap ten test');
        $maxId=0;foreach($splits as $s){if(intval($s['id']??0)>$maxId)$maxId=intval($s['id']);}
        $splits[]=['id'=>$maxId+1,'name'=>$name,'text_a'=>$textA,'text_b'=>$textB,'post_a_id'=>$postAId,'post_b_id'=>$postBId,'a_engagement'=>0,'b_engagement'=>0,'created_at'=>date('c')];
        if(count($splits)>20) abs_ok('Toi da 20 tests');
    }

    if($action==='delete'){
        $splitId=intval($input['split_id']??0);
        $splits=array_values(array_filter($splits,function($s) use($splitId){return intval($s['id']??0)!==$splitId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($splits)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($splits))]);
    abs_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
