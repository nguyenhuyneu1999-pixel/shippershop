<?php
// ShipperShop API v2 — Admin Backup
// Export database summary, settings backup, system snapshot
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$pdo=$d->getConnection();$action=$_GET['action']??'';

function bk_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function bk_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') bk_fail('Admin only',403);

// System snapshot
if(!$action||$action==='snapshot'){
    $tables=$pdo->query("SELECT TABLE_NAME,TABLE_ROWS,DATA_LENGTH FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()")->fetchAll(PDO::FETCH_ASSOC);
    $totalRows=0;$totalSize=0;$tableList=[];
    foreach($tables as $t){$totalRows+=intval($t['TABLE_ROWS']);$totalSize+=intval($t['DATA_LENGTH']);$tableList[]=['name'=>$t['TABLE_NAME'],'rows'=>intval($t['TABLE_ROWS'])];}

    $settings=$d->fetchAll("SELECT `key`,LENGTH(value) as size FROM settings ORDER BY LENGTH(value) DESC LIMIT 20");
    $apiCount=count(glob(__DIR__.'/*.php'))-1;

    bk_ok('OK',[
        'snapshot_at'=>date('c'),
        'database'=>['tables'=>count($tables),'rows'=>$totalRows,'size_mb'=>round($totalSize/1048576,2)],
        'tables'=>$tableList,
        'settings_count'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM settings")['c']),
        'top_settings'=>$settings,
        'api_files'=>$apiCount,
        'php_version'=>phpversion(),
    ]);
}

// Export settings as JSON
if($action==='export_settings'){
    $settings=$d->fetchAll("SELECT `key`,value FROM settings ORDER BY `key`");
    bk_ok('OK',['settings'=>$settings,'count'=>count($settings),'exported_at'=>date('c')]);
}

// Import settings (restore)
if($action==='import_settings'&&$_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $items=$input['settings']??[];
    if(!is_array($items)) bk_fail('Invalid format');
    $imported=0;
    foreach($items as $s){
        $k=$s['key']??'';$v=$s['value']??'';
        if(!$k) continue;
        $exists=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$k]);
        if($exists) $d->query("UPDATE settings SET value=? WHERE `key`=?",[$v,$k]);
        else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$k,$v]);
        $imported++;
    }
    bk_ok('Da import '.$imported.' settings');
}

bk_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
