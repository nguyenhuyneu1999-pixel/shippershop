<?php
// ShipperShop API v2 — Voice Notes
// Metadata for voice messages in conversations (duration, waveform)
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

function vn_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

// GET: voice notes in conversation
if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) vn_ok('OK',['notes'=>[]]);
    $key='voice_notes_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $notes=$row?json_decode($row['value'],true):[];
    // Enrich with sender info
    foreach($notes as &$n){
        $u=$d->fetchOne("SELECT fullname,avatar FROM users WHERE id=?",[intval($n['sender_id']??0)]);
        if($u){$n['sender_name']=$u['fullname'];$n['sender_avatar']=$u['avatar'];}
    }unset($n);
    vn_ok('OK',['notes'=>$notes,'count'=>count($notes)]);
}

// POST: add voice note metadata
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    $duration=intval($input['duration']??0); // seconds
    $messageId=intval($input['message_id']??0);
    if(!$convId) vn_ok('Missing conversation_id');

    $key='voice_notes_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $notes=$row?json_decode($row['value'],true):[];
    $maxId=0;foreach($notes as $n){if(intval($n['id']??0)>$maxId)$maxId=intval($n['id']);}
    $notes[]=['id'=>$maxId+1,'sender_id'=>$uid,'message_id'=>$messageId,'duration'=>$duration,'created_at'=>date('c')];
    if(count($notes)>500) $notes=array_slice($notes,-500);

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($notes),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($notes)]);
    vn_ok('Da luu voice note!',['id'=>$maxId+1]);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
