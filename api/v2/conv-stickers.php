<?php
// ShipperShop API v2 — Conversation Stickers
// Sticker packs for conversations (shipper-themed)
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$PACKS=[
    ['id'=>'shipper','name'=>'Shipper','stickers'=>['🏍️','📦','🚛','📋','💰','✅','❌','⏰','📍','🔔','🙏','💪','🔥','⭐','👍','👎','😊','😢','😡','🤔']],
    ['id'=>'weather','name'=>'Thoi tiet','stickers'=>['☀️','🌧️','⛈️','🌪️','❄️','🌈','💨','🌊','🌡️','☁️']],
    ['id'=>'emotions','name'=>'Cam xuc','stickers'=>['😀','😂','🥰','😎','🤩','😤','😭','🥺','😱','🤯','🎉','💔','❤️','💯','🙌']],
    ['id'=>'food','name'=>'An uong','stickers'=>['🍜','🍚','🍗','☕','🧋','🍔','🍕','🍲','🥤','🍰']],
    ['id'=>'animals','name'=>'Thu cung','stickers'=>['🐕','🐈','🐟','🐦','🐢','🦜','🐹','🐰','🦊','🐸']],
];

try {

$action=$_GET['action']??'';
$packId=$_GET['pack']??'';

if(!$action||$action==='packs'){
    $result=$PACKS;
    if($packId){
        $result=array_values(array_filter($result,function($p) use($packId){return $p['id']===$packId;}));
    }
    echo json_encode(['success'=>true,'data'=>['packs'=>$result,'total_stickers'=>array_sum(array_map(function($p){return count($p['stickers']);},$PACKS))]],JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success'=>true,'data'=>[]]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
