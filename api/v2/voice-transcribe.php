<?php
// ShipperShop API v2 — Voice Transcribe
// Store voice message transcriptions for searchability
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

function vt_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

// GET: transcriptions for conversation
if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    $search=trim($_GET['search']??'');
    if(!$convId) vt_ok('OK',['transcriptions'=>[]]);

    $key='voice_transcripts_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $transcripts=$row?json_decode($row['value'],true):[];

    if($search){
        $searchLower=mb_strtolower($search);
        $transcripts=array_values(array_filter($transcripts,function($t) use($searchLower){
            return mb_strpos(mb_strtolower($t['text']??''),$searchLower)!==false;
        }));
    }

    vt_ok('OK',['transcriptions'=>$transcripts,'count'=>count($transcripts)]);
}

// POST: save transcription
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    $messageId=intval($input['message_id']??0);
    $text=trim($input['text']??'');
    $duration=intval($input['duration']??0);
    if(!$convId||!$text) vt_ok('Missing data');

    $key='voice_transcripts_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $transcripts=$row?json_decode($row['value'],true):[];
    $maxId=0;foreach($transcripts as $t){if(intval($t['id']??0)>$maxId)$maxId=intval($t['id']);}
    $transcripts[]=['id'=>$maxId+1,'message_id'=>$messageId,'sender_id'=>$uid,'text'=>$text,'duration'=>$duration,'created_at'=>date('c')];
    if(count($transcripts)>500) $transcripts=array_slice($transcripts,-500);

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($transcripts),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($transcripts)]);
    vt_ok('Da luu ban ghi am!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
