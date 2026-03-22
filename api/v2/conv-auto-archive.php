<?php
// ShipperShop API v2 — Conversation Auto-Archive
// Auto-archive inactive conversations + manual archive rules
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

function caa_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

// GET: archive settings + stats
if($_SERVER['REQUEST_METHOD']==='GET'){
    $key='auto_archive_'.$uid;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $settings=$row?json_decode($row['value'],true):['enabled'=>false,'days_inactive'=>30];

    // Count inactive conversations
    $convs=$d->fetchAll("SELECT cm.conversation_id,MAX(m.created_at) as last_msg FROM conversation_members cm LEFT JOIN messages m ON cm.conversation_id=m.conversation_id WHERE cm.user_id=? GROUP BY cm.conversation_id",[$uid]);
    $inactive=0;$archived=0;
    foreach($convs as $c){
        if(!$c['last_msg']||strtotime($c['last_msg'])<strtotime('-'.$settings['days_inactive'].' days')) $inactive++;
    }

    $archivedKey='archived_convs_'.$uid;
    $archivedRow=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$archivedKey]);
    $archivedList=$archivedRow?json_decode($archivedRow['value'],true):[];

    caa_ok('OK',['settings'=>$settings,'inactive_count'=>$inactive,'archived_count'=>count($archivedList),'total_conversations'=>count($convs)]);
}

// POST: update settings or archive
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);

    if(!$action||$action==='settings'){
        $key='auto_archive_'.$uid;
        $settings=['enabled'=>!empty($input['enabled']),'days_inactive'=>max(7,min(90,intval($input['days_inactive']??30)))];
        $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
        if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($settings),$key]);
        else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($settings)]);
        caa_ok('Da cap nhat!');
    }

    if($action==='archive_now'){
        $convId=intval($input['conversation_id']??0);
        if(!$convId) caa_ok('Missing conversation_id');
        $archivedKey='archived_convs_'.$uid;
        $archivedRow=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$archivedKey]);
        $list=$archivedRow?json_decode($archivedRow['value'],true):[];
        if(!in_array($convId,$list)) $list[]=$convId;
        $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$archivedKey]);
        if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($list),$archivedKey]);
        else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$archivedKey,json_encode($list)]);
        caa_ok('Da luu tru!');
    }
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
