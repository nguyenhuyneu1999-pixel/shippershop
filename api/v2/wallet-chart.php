<?php
// ShipperShop API v2 — Wallet History Chart
// Transaction chart data for wallet balance visualization
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

function wc_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$days=min(intval($_GET['days']??30),365);

// Daily balance changes
$daily=$d->fetchAll("SELECT DATE(created_at) as day, SUM(CASE WHEN amount>0 THEN amount ELSE 0 END) as income, SUM(CASE WHEN amount<0 THEN ABS(amount) ELSE 0 END) as expense, COUNT(*) as txns FROM wallet_transactions WHERE user_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY DATE(created_at) ORDER BY day",[$uid]);

// Current balance
$wallet=$d->fetchOne("SELECT balance FROM wallets WHERE user_id=?",[$uid]);
$balance=intval($wallet['balance']??0);

// Summary
$totalIncome=0;$totalExpense=0;
foreach($daily as $dd){$totalIncome+=intval($dd['income']);$totalExpense+=intval($dd['expense']);}

// By type
$byType=$d->fetchAll("SELECT type,SUM(amount) as total,COUNT(*) as txns FROM wallet_transactions WHERE user_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY type ORDER BY total DESC",[$uid]);

wc_ok('OK',['daily'=>$daily,'balance'=>$balance,'summary'=>['income'=>$totalIncome,'expense'=>$totalExpense,'net'=>$totalIncome-$totalExpense],'by_type'=>$byType,'period_days'=>$days]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
