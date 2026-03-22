<?php
// ShipperShop Quick Test Runner — DB + file checks only (no external HTTP)
set_time_limit(120);
header('Content-Type: application/json; charset=utf-8');
if(($_GET['key']??'')!=='ss_test_secret'){http_response_code(403);echo '{"error":"key"}';exit;}

require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/functions.php';

$d=db();$pdo=$d->getConnection();
$R=[];$P=0;$F=0;

function t($n,$ok){global $R,$P,$F;if($ok){$P++;$R[]=['n'=>$n,'s'=>'PASS'];}else{$F++;$R[]=['n'=>$n,'s'=>'FAIL'];}}

// ============ DB TESTS ============
t('DB: connection',!!$pdo);
$tables=$pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
t('DB: 84+ tables',count($tables)>=84);
t('DB: users table',in_array('users',$tables));
t('DB: posts table',in_array('posts',$tables));
t('DB: post_likes table',in_array('post_likes',$tables));
t('DB: comments table',in_array('comments',$tables));
t('DB: groups table',in_array('groups',$tables));
t('DB: messages table',in_array('messages',$tables));
t('DB: wallets table',in_array('wallets',$tables));
t('DB: conversations table',in_array('conversations',$tables));
t('DB: follows table',in_array('follows',$tables));
t('DB: user_xp table',in_array('user_xp',$tables));
t('DB: user_badges table',in_array('user_badges',$tables));
t('DB: user_streaks table',in_array('user_streaks',$tables));
t('DB: content_queue table',in_array('content_queue',$tables));
t('DB: analytics_views table',in_array('analytics_views',$tables));
t('DB: subscription_plans table',in_array('subscription_plans',$tables));
t('DB: notification_reads table',in_array('notification_reads',$tables));
t('DB: traffic_alerts table',in_array('traffic_alerts',$tables));
t('DB: marketplace_listings table',in_array('marketplace_listings',$tables));
t('DB: audit_log table',in_array('audit_log',$tables));

$userCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c']);
t('DB: 700+ users',$userCount>=700);
$postCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'")['c']);
t('DB: 700+ posts',$postCount>=700);
$likeCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_likes")['c']);
t('DB: likes exist',$likeCount>0);
$commentCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments")['c']);
t('DB: comments exist',$commentCount>0);

// User id=1 should not exist, id=2 is admin
$u1=$d->fetchOne("SELECT id FROM users WHERE id=1");
t('DB: user id=1 not exist',$u1===null||$u1===false);
$u2=$d->fetchOne("SELECT id,role FROM users WHERE id=2");
t('DB: user id=2 admin',$u2&&$u2['role']==='admin');

// Subscription plans
$plans=$d->fetchAll("SELECT id,name,price FROM subscription_plans ORDER BY id");
t('DB: 4+ plans',count($plans)>=4);
t('DB: free plan',intval($plans[0]['price']??-1)===0);

// ============ PHP API v2 FILES ============
$apiDir=__DIR__.'/v2/';
$apiFiles=array_filter(scandir($apiDir),function($f){return substr($f,-4)==='.php'&&$f!=='index.php';});
t('File: 240+ API files',count($apiFiles)>=240);

// Check critical API files exist
$critical=['posts.php','users.php','wallet.php','groups.php','messages.php','notifications.php','social.php','friends.php','search.php','gamification.php','stories.php','traffic.php','marketplace.php','admin.php','health.php','status.php','site-config.php','feature-flags.php','smart-schedule.php','reputation-tiers.php','delivery-map.php','calendar-view.php','engagement-heatmap.php','content-score.php','user-ratings.php','weekly-challenge.php','weather-alerts.php','ai-suggest.php','conv-templates.php','content-moderate.php','delivery-stats-v2.php','growth-metrics.php','vehicle-manager.php','fuel-tracker.php','trend-detector.php','cohort-analysis.php','retention-score.php','conv-stickers.php'];
foreach($critical as $f){t('File: api/v2/'.$f,file_exists($apiDir.$f));}

// ============ JS FILES ============
$jsRoot=__DIR__.'/../js/';
t('File: js/core/api.js',file_exists($jsRoot.'core/api.js'));
t('File: js/core/store.js',file_exists($jsRoot.'core/store.js'));
t('File: js/core/ui.js',file_exists($jsRoot.'core/ui.js'));
t('File: js/core/utils.js',file_exists($jsRoot.'core/utils.js'));
t('File: js/ss-bundle.min.js',file_exists($jsRoot.'ss-bundle.min.js'));
t('File: js/ss-prod.js',file_exists($jsRoot.'ss-prod.js'));

$components=array_filter(scandir($jsRoot.'components/'),function($f){return substr($f,-3)==='.js';});
t('File: 230+ JS components',count($components)>=230);

$pages=array_filter(scandir($jsRoot.'pages/'),function($f){return substr($f,-3)==='.js';});
t('File: 28 JS pages',count($pages)>=28);

// ============ CSS + OTHER FILES ============
t('File: design-system.css',file_exists(__DIR__.'/../css/design-system.css'));
t('File: design-system.min.css',file_exists(__DIR__.'/../css/design-system.min.css'));
t('File: sw.js',file_exists(__DIR__.'/../sw.js'));
t('File: cron-run.php',file_exists(__DIR__.'/cron-run.php'));

// ============ QUICK API SMOKE TESTS ============
$base='https://shippershop.vn/api/v2/';
$ctx=stream_context_create(['http'=>['timeout'=>5,'ignore_errors'=>true]]);

// Test ~30 critical public endpoints quickly
$quickTests=[
    'posts.php?limit=1'=>'posts',
    'site-config.php'=>'site_config',
    'feature-flags.php'=>'flags',
    'weather-alerts.php'=>'weather',
    'ai-suggest.php'=>'suggest',
    'conv-templates.php'=>'templates',
    'conv-stickers.php'=>'stickers',
    'content-score.php?post_id=125'=>'score',
    'delivery-map.php'=>'map',
    'engagement-heatmap.php?days=7'=>'heatmap',
    'calendar-view.php?month=3&year=2026'=>'calendar',
    'trend-detector.php?hours=72'=>'trends',
    'user-ratings.php'=>'ratings',
    'smart-schedule.php'=>'schedule',
    'reputation-tiers.php?action=tiers'=>'tiers',
    'skill-tags.php'=>'skills',
    'template-market.php'=>'market',
    'badges-wall.php?action=catalog'=>'badges',
    'milestones.php?user_id=2'=>'milestones',
    'post-reach.php?user_id=2'=>'reach',
    'user-availability.php'=>'avail',
    'plagiarism-check.php?text='.urlencode('shipper giao hang nhanh tphcm kinh nghiem thuc te')=>'plagiarism',
    'engagement-compare.php?post1=125&post2=126'=>'compare',
    'delivery-stats-v2.php?days=7'=>'dstats',
    'content-moderate.php?text='.urlencode('giao hang nhanh shipper')=>'moderate',
    'content-warnings.php'=>'warnings',
    'auto-tag.php?text='.urlencode('shipper ghtk giao hang')=>'autotag',
    'engagement-predict.php?text='.urlencode('shipper giao hang nhanh')=>'predict',
    'post-similar.php?post_id=125'=>'similar',
    'user-summary-card.php?user_id=2'=>'summary',
];

foreach($quickTests as $url=>$label){
    $resp=@json_decode(@file_get_contents($base.$url,false,$ctx),true);
    t('API: '.$label,$resp&&($resp['success']===true||isset($resp['status'])));
}

// ============ BUNDLE SIZE ============
$bundleSize=filesize($jsRoot.'ss-bundle.min.js');
t('Perf: bundle < 600KB',$bundleSize<600000);
t('Perf: bundle > 400KB',$bundleSize>400000);

// ============ RESULTS ============
$total=$P+$F;
$score=$total>0?round($P/$total*100,1).'%':'0%';
echo json_encode(['score'=>$score,'passed'=>$P,'failed'=>$F,'total'=>$total,'runner'=>'quick','results'=>$R],JSON_UNESCAPED_UNICODE);
