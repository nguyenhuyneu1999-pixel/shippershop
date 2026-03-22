<?php
// ShipperShop API v2 — Admin AB Testing
// Create and manage AB tests for features/UI
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$action=$_GET['action']??'';$key='ab_tests';

function ab_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ab_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

// Public: get user's variant for a test
if($_SERVER['REQUEST_METHOD']==='GET'&&$action==='variant'){
    $testId=$_GET['test_id']??'';
    $userId=intval($_GET['user_id']??0);
    if(!$testId) ab_ok('OK',['variant'=>'control']);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $tests=$row?json_decode($row['value'],true):[];
    foreach($tests as $t){
        if(($t['id']??'')===$testId&&($t['active']??false)){
            // Deterministic assignment based on user_id
            $variant=($userId%2===0)?'A':'B';
            ab_ok('OK',['test_id'=>$testId,'variant'=>$variant,'name'=>$t['name']??'']);
        }
    }
    ab_ok('OK',['variant'=>'control']);
}

// Admin: list all tests
if($_SERVER['REQUEST_METHOD']==='GET'&&(!$action||$action==='list')){
    $uid=require_auth();
    $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
    if(!$admin||$admin['role']!=='admin') ab_fail('Admin only',403);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $tests=$row?json_decode($row['value'],true):[];
    ab_ok('OK',['tests'=>$tests,'count'=>count($tests)]);
}

// Admin: create test
if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
    if(!$admin||$admin['role']!=='admin') ab_fail('Admin only',403);
    $input=json_decode(file_get_contents('php://input'),true);

    if(!$action||$action==='create'){
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        $tests=$row?json_decode($row['value'],true):[];
        $tests[]=['id'=>'test_'.count($tests).'_'.time(),'name'=>trim($input['name']??''),'description'=>trim($input['description']??''),'variant_a'=>$input['variant_a']??'Default','variant_b'=>$input['variant_b']??'New','active'=>true,'created_by'=>$uid,'created_at'=>date('c')];
        $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
        if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($tests),$key]);
        else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($tests)]);
        ab_ok('Da tao AB test!');
    }

    if($action==='toggle'){
        $testId=$input['test_id']??'';
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        $tests=$row?json_decode($row['value'],true):[];
        foreach($tests as &$t){if(($t['id']??'')===$testId) $t['active']=!($t['active']??false);}unset($t);
        $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($tests),$key]);
        ab_ok('Da cap nhat');
    }
}

ab_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
