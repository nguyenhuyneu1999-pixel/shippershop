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
$apiFiles=['account.php','activity-feed.php','admin.php','admin-batch.php','admin-export.php','admin-notes.php','admin-users.php','analytics.php','announcements.php','badges.php','batch.php','bookmarks.php','calendar.php','chat-extras.php','content.php','content-filter.php','content-queue.php','conv-labels.php','conv-pin.php','conv-search.php','dashboard-summary.php','events.php','export.php','feed-prefs.php','follow-suggest.php','friends.php','gamification.php','group-admin.php','group-chat.php','group-settings.php','groups.php','hashtags.php','health.php','heatmap.php','insights.php','link-preview.php','logs.php','marketplace.php','media.php','mentions.php','messages.php','moderation.php','msg-poll.php','mute.php','notif-grouped.php','notif-prefs.php','notifications.php','og-tags.php','outgoing-hooks.php','payment.php','perf.php','pins.php','polls.php','post-analytics.php','post-polls.php','post-views.php','posts.php','preferences.php','presence.php','profile-card.php','profile-score.php','profile-theme.php','push.php','qr.php','rate-monitor.php','reactions.php','recommend.php','referrals.php','report-analytics.php','reputation.php','scheduled.php','search.php','share-to-group.php','site-config.php','sitemap.php','sitemap-gen.php','social.php','sse.php','stats.php','status.php','stories.php','suggest.php','system-config.php','templates.php','traffic.php','trending.php','two-factor.php','upload-media.php','user-activity.php','users.php','verification.php','wallet.php','webhooks.php','index.php'];
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
$staticFiles=['css/design-system.css','css/design-system.min.css','js/core/api.js','js/core/store.js','js/core/ui.js','js/core/utils.js','js/components/a11y.js','js/components/admin-export.js','js/components/announcement.js','js/components/author-stats.js','js/components/auto-draft.js','js/components/badge-display.js','js/components/block-user.js','js/components/calendar.js','js/components/celebrate.js','js/components/charts.js','js/components/clipboard.js','js/components/comment-sheet.js','js/components/connection-status.js','js/components/conv-labels.js','js/components/dark-mode.js','js/components/draft-save.js','js/components/emoji-picker.js','js/components/error-boundary.js','js/components/fab-menu.js','js/components/feed-filter.js','js/components/feed-mute.js','js/components/follow-suggest.js','js/components/freshness.js','js/components/gamification.js','js/components/hashtags.js','js/components/heatmap.js','js/components/image-optimizer.js','js/components/image-viewer.js','js/components/img-compress.js','js/components/infinite-scroll.js','js/components/insights.js','js/components/lazy-img.js','js/components/lazy-loader.js','js/components/link-preview.js','js/components/location-picker.js','js/components/mention-picker.js','js/components/msg-poll.js','js/components/mute-user.js','js/components/notif-grouped.js','js/components/notif-poll.js','js/components/notif-prefs.js','js/components/notif-sound.js','js/components/notification-bell.js','js/components/online-widget.js','js/components/payment.js','js/components/perf-monitor.js','js/components/perf-reporter.js','js/components/poll-ui.js','js/components/poll.js','js/components/post-analytics.js','js/components/post-card.js','js/components/post-create.js','js/components/post-edit.js','js/components/post-location.js','js/components/post-reactions.js','js/components/post-templates-ui.js','js/components/post-templates.js','js/components/post-types.js','js/components/post-utils.js','js/components/profile-card.js','js/components/profile-complete.js','js/components/profile-completion.js','js/components/pwa-install.js','js/components/qr-share.js','js/components/quick-post.js','js/components/reading-time.js','js/components/realtime.js','js/components/recommend.js','js/components/report-dialog.js','js/components/reputation-display.js','js/components/reputation.js','js/components/scroll-heartbeat.js','js/components/scroll-progress.js','js/components/search-filters.js','js/components/search-overlay.js','js/components/share-sheet.js','js/components/share-to-group.js','js/components/shortcuts.js','js/components/stories.js','js/components/sub-badge.js','js/components/swipe.js','js/components/text-format.js','js/components/theme-picker.js','js/components/tooltip.js','js/components/trending-widget.js','js/components/two-factor.js','js/components/typing-indicator.js','js/components/upload.js','js/components/user-card.js','js/components/verified-badge.js','js/components/video-player.js','js/components/view-tracker.js','js/pages/account-settings.js','js/pages/activity-log.js','js/pages/admin-logs.js','js/pages/admin-mod.js','js/pages/admin-users.js','js/pages/admin.js','js/pages/auth.js','js/pages/bookmarks.js','js/pages/content-queue.js','js/pages/content-stats.js','js/pages/feed.js','js/pages/group-detail.js','js/pages/groups.js','js/pages/leaderboard.js','js/pages/listing-detail.js','js/pages/map-page.js','js/pages/marketplace.js','js/pages/messages.js','js/pages/people.js','js/pages/post-detail.js','js/pages/preferences.js','js/pages/profile-settings.js','js/pages/scheduled.js','js/pages/settings.js','js/pages/system-config.js','js/pages/traffic.js','js/pages/user-profile.js','js/pages/wallet.js','js/ss-bundle.min.js','js/ss-prod.js'];
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
