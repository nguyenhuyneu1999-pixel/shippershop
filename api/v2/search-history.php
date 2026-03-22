<?php
// ShipperShop API v2 — Search History
// Save/clear user search history for suggestions
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

function sh_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='search_history_'.$uid;

// Get history
if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $history=$row?json_decode($row['value'],true):[];
    sh_ok('OK',['history'=>array_slice($history,0,20),'count'=>count($history)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);

    // Add search term
    if(!$action||$action==='add'){
        $term=trim($input['query']??'');
        if(!$term||mb_strlen($term)<2) sh_ok('skip');
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        $history=$row?json_decode($row['value'],true):[];
        // Remove duplicates, add to front
        $history=array_values(array_filter($history,function($h) use($term){return $h!==$term;}));
        array_unshift($history,$term);
        if(count($history)>50) $history=array_slice($history,0,50);
        $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
        if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($history),$key]);
        else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($history)]);
        sh_ok('OK');
    }

    // Clear history
    if($action==='clear'){
        $d->query("DELETE FROM settings WHERE `key`=?",[$key]);
        sh_ok('Da xoa lich su');
    }

    // Remove single item
    if($action==='remove'){
        $term=trim($input['query']??'');
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        $history=$row?json_decode($row['value'],true):[];
        $history=array_values(array_filter($history,function($h) use($term){return $h!==$term;}));
        $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($history),$key]);
        sh_ok('Da xoa');
    }
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
