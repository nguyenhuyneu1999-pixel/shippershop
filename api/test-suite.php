<?php
// ShipperShop Test Suite v2 — Direct PHP testing (no self-curl)
session_start();
header('Content-Type: application/json; charset=utf-8');
if(($_GET['key']??'')!=='ss_test_secret'){http_response_code(403);echo '{"error":"key"}';exit;}

require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/cache.php';
require_once __DIR__.'/../includes/rate-limiter.php';
require_once __DIR__.'/../includes/validator.php';
require_once __DIR__.'/../includes/auth-v2.php';

$d=db();$pdo=$d->getConnection();
$R=[];$P=0;$F=0;

function t($n,$ok,$det=''){global $R,$P,$F;if($ok){$P++;$R[]=['n'=>$n,'s'=>'PASS'];}else{$F++;$R[]=['n'=>$n,'s'=>'FAIL','d'=>$det];}}

// ============ DATABASE ============
$tc=$d->fetchOne("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE()");
t('DB: tables >= 69', intval($tc['c'])>=69, 'has '.$tc['c']);

$tables=['posts','users','comments','likes','follows','messages','conversations','notifications','groups','group_posts','group_members','group_post_likes','wallet_transactions','wallets','subscription_plans','user_subscriptions','post_reports','user_blocks','search_history','user_sessions','email_queue','error_logs','page_views','cron_logs','marketplace_listings'];
foreach($tables as $tb){
    try{$d->fetchOne("SELECT 1 FROM `$tb` LIMIT 1");t("DB: $tb exists",true);}
    catch(\Throwable $e){t("DB: $tb exists",false,$e->getMessage());}
}

// ============ INDEXES ============
$idxCheck=[
    ['posts','idx_user_status'],['posts','idx_province'],['posts','idx_sort'],
    ['likes','idx_post_user'],['follows','idx_pair'],['users','idx_company']
];
foreach($idxCheck as $ic){
    $idx=$d->fetchAll("SHOW INDEX FROM `{$ic[0]}` WHERE Key_name='{$ic[1]}'");
    t("Index: {$ic[0]}.{$ic[1]}",count($idx)>0);
}

// ============ PHP SERVICES ============
// Cache
cache_set('_test',42,10);
t('Service: cache set/get',cache_get('_test')===42);
cache_del('_test');
t('Service: cache del',cache_get('_test')===null);

// Validator
$e1=validate(['email'=>'bad'],['email'=>'required|email']);
t('Service: validator fail',isset($e1['email']));
$e2=validate(['email'=>'a@b.com'],['email'=>'required|email']);
t('Service: validator pass',empty($e2));
t('Service: sanitize',strpos(sanitize_html('<script>x</script>'),'<script>')===false);

// Auth functions
t('Service: require_auth exists',function_exists('require_auth'));
t('Service: optional_auth exists',function_exists('optional_auth'));
t('Service: require_admin exists',function_exists('require_admin'));

// JWT
$token=generateJWT(2,'a@s.vn','admin');
$decoded=verifyJWT($token);
t('Service: JWT generate+verify',$decoded&&$decoded['user_id']===2);
t('Service: JWT tampered',verifyJWT($token.'x')===false);

// Error handler
require_once __DIR__.'/../includes/error-handler.php';
t('Service: ss_log exists',function_exists('ss_log'));

// Upload handler
require_once __DIR__.'/../includes/upload-handler.php';
t('Service: handle_upload exists',function_exists('handle_upload'));

// ============ API FILES EXIST ============
$apiFiles=['posts.php','messages.php','users.php','notifications.php','search.php','admin.php','wallet.php','traffic.php','marketplace.php','analytics.php','health.php','groups.php','gamification.php','social.php'];
foreach($apiFiles as $af){
    t("API v2: $af exists",file_exists(__DIR__.'/v2/'.$af));
}

// ============ HTML PAGES ============
$pages=['index.html','messages.html','user.html','profile.html','groups.html','group.html','marketplace.html','listing.html','wallet.html','traffic.html','map.html','people.html','post-detail.html','activity-log.html','login.html','register.html','create-group.html','leaderboard.html','admin-v2.html','404.html','offline.html','landing.html'];
foreach($pages as $pg){
    $exists=file_exists(__DIR__.'/../'.$pg);
    $hasDSCSS=$exists&&strpos(file_get_contents(__DIR__.'/../'.$pg),'design-system.css')!==false;
    t("Page: $pg exists",$exists);
    t("Page: $pg has design-system",$hasDSCSS);
}

// ============ JS/CSS FILES ============
$staticFiles=['css/design-system.css','js/core/api.js','js/core/store.js','js/core/ui.js','js/core/utils.js','js/components/post-card.js','js/components/comment-sheet.js','js/components/image-viewer.js','js/components/notification-bell.js','js/components/search-overlay.js','js/components/upload.js','js/components/video-player.js','js/components/location-picker.js','js/components/gamification.js','js/components/emoji-picker.js','js/components/post-create.js','js/components/user-card.js','js/pages/feed.js'];
foreach($staticFiles as $sf){t("Static: $sf",file_exists(__DIR__.'/../'.$sf));}

// ============ SVG ASSETS ============
t('Asset: default avatar',file_exists(__DIR__.'/../assets/img/defaults/avatar.svg'));
t('Asset: no-posts',file_exists(__DIR__.'/../assets/img/defaults/no-posts.svg'));
t('Asset: badge pro',file_exists(__DIR__.'/../assets/img/badges/pro.svg'));
t('Asset: company ghtk',file_exists(__DIR__.'/../assets/img/companies/ghtk.svg'));

// ============ DATA INTEGRITY ============
// likes_count vs actual COUNT
$bad=$d->fetchAll("SELECT p.id,p.likes_count,COUNT(l.id) as real_count FROM posts p LEFT JOIN likes l ON l.post_id=p.id WHERE p.`status`='active' GROUP BY p.id HAVING ABS(p.likes_count-COUNT(l.id))>0 LIMIT 5");
t('Integrity: likes_count',count($bad)===0,count($bad).' mismatches');

// total_posts spot check
$u2=$d->fetchOne("SELECT total_posts FROM users WHERE id=2");
$real=$d->fetchOne("SELECT (SELECT COUNT(*) FROM posts WHERE user_id=2 AND `status`='active')+(SELECT COUNT(*) FROM group_posts WHERE user_id=2 AND `status`='active') as c");
t('Integrity: user 2 total_posts',abs(intval($u2['total_posts'])-intval($real['c']))<=2,'db='.$u2['total_posts'].' real='.$real['c']);

// ============ SEO ============
t('SEO: robots.txt',file_exists(__DIR__.'/../robots.txt'));
t('SEO: sitemap.xml',file_exists(__DIR__.'/../sitemap.xml'));

// ============ SECURITY ============
$htaccess=file_get_contents(__DIR__.'/../.htaccess');
t('Security: CSP in .htaccess',strpos($htaccess,'Content-Security-Policy')!==false);
t('Security: HSTS in .htaccess',strpos($htaccess,'Strict-Transport-Security')!==false);
t('Security: X-Frame',strpos($htaccess,'X-Frame-Options')!==false);
t('Security: uploads .htaccess',file_exists(__DIR__.'/../uploads/.htaccess'));

// ============ CRON ============
t('Cron: runner exists',file_exists(__DIR__.'/../includes/cron/runner.php'));
t('Cron: web trigger exists',file_exists(__DIR__.'/cron-run.php'));

// ============ DOCS ============
t('Docs: API.md',file_exists(__DIR__.'/../docs/API.md'));
t('Docs: DATABASE.md',file_exists(__DIR__.'/../docs/DATABASE.md'));
t('Docs: CHANGELOG.md',file_exists(__DIR__.'/../docs/CHANGELOG.md'));

// ============ EMAIL TEMPLATES ============
t('Template: welcome',file_exists(__DIR__.'/../templates/emails/welcome.html'));
t('Template: reset-password',file_exists(__DIR__.'/../templates/emails/reset-password.html'));
t('Template: deposit-approved',file_exists(__DIR__.'/../templates/emails/deposit-approved.html'));

// ============ RESULTS ============
$total=$P+$F;
echo json_encode(['timestamp'=>date('Y-m-d H:i:s'),'passed'=>$P,'failed'=>$F,'total'=>$total,'score'=>$total>0?round($P/$total*100,1).'%':'0%','results'=>$R],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
