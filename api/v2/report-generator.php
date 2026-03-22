<?php
// ShipperShop API v2 — Report Generator
// Generate weekly/monthly delivery reports with stats summary
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

function rg_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$period=$_GET['period']??'week'; // week, month
$startDate=$period==='month'?date('Y-m-01'):date('Y-m-d',strtotime('monday this week'));
$endDate=date('Y-m-d H:i:s');

$user=$d->fetchOne("SELECT fullname,shipping_company,total_posts FROM users WHERE id=?",[$uid]);

$posts=$d->fetchAll("SELECT DATE(created_at) as day, COUNT(*) as posts, SUM(likes_count) as likes, SUM(comments_count) as comments FROM posts WHERE user_id=? AND `status`='active' AND created_at BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY day",[$uid,$startDate,$endDate]);

$totalPosts=array_sum(array_column($posts,'posts'));
$totalLikes=array_sum(array_column($posts,'likes'));
$totalComments=array_sum(array_column($posts,'comments'));
$activeDays=count($posts);
$avgPerDay=$activeDays>0?round($totalPosts/$activeDays,1):0;

// Top post
$topPost=$d->fetchOne("SELECT id,LEFT(content,80) as preview,likes_count,comments_count FROM posts WHERE user_id=? AND `status`='active' AND created_at BETWEEN ? AND ? ORDER BY likes_count+comments_count DESC LIMIT 1",[$uid,$startDate,$endDate]);

// Followers gained
$newFollowers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=? AND created_at BETWEEN ? AND ?",[$uid,$startDate,$endDate])['c']);

// Province breakdown
$byProvince=$d->fetchAll("SELECT province, COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active' AND province IS NOT NULL AND province!='' AND created_at BETWEEN ? AND ? GROUP BY province ORDER BY c DESC LIMIT 5",[$uid,$startDate,$endDate]);

$report=['period'=>$period,'start'=>$startDate,'end'=>date('Y-m-d'),'user'=>$user['fullname'],'company'=>$user['shipping_company'],'summary'=>['total_posts'=>$totalPosts,'total_likes'=>$totalLikes,'total_comments'=>$totalComments,'active_days'=>$activeDays,'avg_per_day'=>$avgPerDay,'new_followers'=>$newFollowers,'engagement_rate'=>$totalPosts>0?round(($totalLikes+$totalComments)/$totalPosts,1):0],'daily'=>$posts,'top_post'=>$topPost,'by_province'=>$byProvince,'generated_at'=>date('c')];

rg_ok('OK',$report);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
