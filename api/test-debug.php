<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');
echo "Step 1\n";
require_once __DIR__.'/../includes/config.php';
echo "Step 2\n";
require_once __DIR__.'/../includes/db.php';
echo "Step 3\n";

$d=db();$pdo=$d->getConnection();
echo "Step 4: DB OK\n";

$R=[];$P=0;$F=0;$_tIdx=0;
$_page=1;$_perPage=200;
$_startIdx=0;$_endIdx=200;

function t($n,$ok,$det=''){global $R,$P,$F,$_tIdx,$_startIdx,$_endIdx;$_tIdx++;if($_tIdx<$_startIdx||$_tIdx>=$_endIdx)return;if($ok){$P++;$R[]=['n'=>$n,'s'=>'PASS'];}else{$F++;$R[]=['n'=>$n,'s'=>'FAIL','d'=>$det];}}

function http_get($url){
    $ctx=stream_context_create(['http'=>['timeout'=>10,'ignore_errors'=>true]]);
    return @file_get_contents($url,false,$ctx);
}

function http_get_ctx($url,$ctx){
    return @file_get_contents($url,false,$ctx);
}

echo "Step 5: Functions OK\n";

t('test1', true);
t('test2', true);
echo "Step 6: t() OK, P=$P\n";

$resp=http_get('https://shippershop.vn/api/v2/status.php');
echo "Step 7: http_get OK, len=".strlen($resp)."\n";

echo "ALL OK";
