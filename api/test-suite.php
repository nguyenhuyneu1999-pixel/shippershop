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
$apiFiles=['posts.php','messages.php','users.php','notifications.php','search.php','admin.php','wallet.php','traffic.php','marketplace.php','analytics.php','health.php','groups.php','gamification.php','social.php','content.php','push.php','referrals.php','stats.php','status.php','hashtags.php','friends.php','stories.php','bookmarks.php','verification.php','scheduled.php','payment.php','moderation.php','notif-prefs.php','media.php','mentions.php','export.php','reactions.php','chat-extras.php','profile-score.php','msg-poll.php','upload-media.php','post-views.php','qr.php','admin-batch.php','webhooks.php','account.php','trending.php','link-preview.php','post-analytics.php','two-factor.php','activity-feed.php','logs.php','group-settings.php','presence.php','og-tags.php','batch.php','preferences.php','recommend.php','mute.php','admin-export.php','polls.php','admin-notes.php','sitemap.php','templates.php','badges.php','report-analytics.php','reputation.php','announcements.php','pins.php','sse.php','group-admin.php','heatmap.php','profile-card.php','system-config.php','follow-suggest.php','calendar.php','index.php'];
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
$staticFiles=['css/design-system.css','css/design-system.min.css','js/core/api.js','js/core/store.js','js/core/ui.js','js/core/utils.js','js/components/post-card.js','js/components/comment-sheet.js','js/components/image-viewer.js','js/components/notification-bell.js','js/components/search-overlay.js','js/components/upload.js','js/components/video-player.js','js/components/location-picker.js','js/components/gamification.js','js/components/emoji-picker.js','js/components/post-create.js','js/components/user-card.js','js/components/dark-mode.js','js/components/pwa-install.js','js/components/scroll-heartbeat.js','js/pages/feed.js','js/pages/user-profile.js','js/pages/messages.js','js/pages/post-detail.js','js/pages/groups.js','js/pages/people.js','js/pages/wallet.js','js/pages/traffic.js','js/pages/marketplace.js','js/pages/leaderboard.js','js/pages/activity-log.js','js/pages/group-detail.js','js/pages/profile-settings.js','js/pages/listing-detail.js','js/pages/map-page.js','js/pages/admin.js','js/components/share-sheet.js','js/components/hashtags.js','js/components/online-widget.js','js/components/fab-menu.js','js/pages/admin.js','js/components/stories.js','js/components/verified-badge.js','js/components/charts.js','js/components/notif-poll.js','js/components/payment.js','js/components/report-dialog.js','js/components/notif-prefs.js','js/components/post-edit.js','js/components/block-user.js','js/pages/auth.js','js/pages/settings.js','js/components/profile-complete.js','js/components/a11y.js','js/components/msg-poll.js','js/components/error-boundary.js','js/components/lazy-img.js','js/components/post-types.js','js/components/qr-share.js','js/components/view-tracker.js','js/pages/account-settings.js','js/pages/bookmarks.js','js/pages/scheduled.js','js/components/typing-indicator.js','js/components/mention-picker.js','js/ss-critical.min.js','js/ss-lazy.min.js','js/ss-smart-loader.js','js/ss-error-tracker.js','js/pages/admin-mod.js','js/pages/admin-logs.js','js/components/link-preview.js','js/components/trending-widget.js','js/components/post-reactions.js','js/components/author-stats.js','js/components/post-analytics.js','js/components/two-factor.js','js/components/notif-sound.js','js/components/image-optimizer.js','js/components/connection-status.js','js/components/shortcuts.js','js/components/draft-save.js','js/components/lazy-loader.js','js/components/reading-time.js','js/components/profile-completion.js','js/components/celebrate.js','js/components/perf-monitor.js','js/components/infinite-scroll.js','js/components/post-utils.js','js/components/recommend.js','js/components/mute-user.js','js/components/post-templates.js','js/components/swipe.js','js/components/text-format.js','js/pages/content-stats.js','js/components/badge-display.js','js/components/quick-post.js','js/components/reputation.js','js/components/announcement.js','js/components/post-location.js','js/components/realtime.js','js/components/heatmap.js','js/components/search-filters.js','js/components/profile-card.js','js/components/scroll-progress.js','js/components/freshness.js','js/pages/system-config.js','js/components/follow-suggest.js','js/components/calendar.js','js/components/clipboard.js','js/ss-bundle.min.js','js/ss-prod.js'];
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

// ============ FUNCTIONAL API TESTS ============
// Test actual API responses (not just file existence)

// Posts API
$postsResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/posts.php?limit=1'),true);
t('Func: posts list',$postsResp&&$postsResp['success']===true);

// Trending API
$trendResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/trending.php?action=hot&limit=1'),true);
t('Func: trending hot',$trendResp&&$trendResp['success']===true);

// Hashtags API
$hashResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/hashtags.php?action=trending&limit=3'),true);
t('Func: hashtags trending',$hashResp&&$hashResp['success']===true);

// Health API
$healthResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/health.php'),true);
t('Func: health check',$healthResp&&isset($healthResp['status']));

// Status API
$statusResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/status.php'),true);
t('Func: status healthy',$statusResp&&$statusResp['status']==='healthy');

// Stats API
$statsResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/stats.php'),true);
t('Func: public stats',$statsResp&&$statsResp['success']===true);

// OG Tags API
$ogResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/og-tags.php?type=post&id=5'),true);
t('Func: OG tags',$ogResp&&$ogResp['success']===true&&!empty($ogResp['data']['title']));

// Moderation reasons
$modResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/moderation.php?action=reasons'),true);
t('Func: mod reasons',$modResp&&$modResp['success']===true&&count($modResp['data'])>=5);

// Webhook events
$whResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/webhooks.php?action=events'),true);
t('Func: webhook events',$whResp&&is_array($whResp['data']??null)&&count($whResp['data'])>=5);

// Link preview
$lpResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/link-preview.php?url=https://github.com'),true);
t('Func: link preview',$lpResp&&$lpResp['success']===true&&!empty($lpResp['data']['title']));


// Announcements (public)
$annResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/announcements.php'),true);
t('Func: announcements',$annResp&&$annResp['success']===true);

// Reputation (public)
$repResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/reputation.php?user_id=2'),true);
t('Func: reputation',$repResp&&$repResp['success']===true&&isset($repResp['data']['score']));

// Templates (public)
$tplResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/templates.php'),true);
t('Func: templates',$tplResp&&$tplResp['success']===true&&count($tplResp['data'])>=5);

// Badges (public)
$bdgResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/badges.php?action=all'),true);
t('Func: badges list',$bdgResp&&$bdgResp['success']===true&&count($bdgResp['data'])>=8);

// Polls (get for post without poll)
$pollResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/polls.php?post_id=5'),true);
t('Func: polls get',$pollResp&&$pollResp['success']===true);

// ============ RESULTS ============
$total=$P+$F;
echo json_encode(['timestamp'=>date('Y-m-d H:i:s'),'passed'=>$P,'failed'=>$F,'total'=>$total,'score'=>$total>0?round($P/$total*100,1).'%':'0%','results'=>$R],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
