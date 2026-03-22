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
$apiFiles=['ab-test.php','account.php','achievements.php','achievements-wall.php','activity-feed.php','activity-heatmap.php','admin.php','admin-audit.php','admin-backup.php','admin-batch.php','admin-content-schedule.php','admin-dashboard-v2.php','admin-export.php','admin-ip.php','admin-notes.php','admin-revenue.php','admin-stats-cache.php','admin-timeline.php','admin-user-notes.php','admin-users.php','admin-widgets.php','analytics.php','analytics-export.php','announce-schedule.php','announcement-banner.php','announcements.php','auto-tag.php','badge-showcase.php','badges.php','badges-wall.php','ban-appeals.php','batch.php','bookmarks.php','calendar.php','chat-extras.php','checkin.php','collections.php','content.php','content-calendar.php','content-filter.php','content-insights.php','content-queue.php','content-summary.php','content-warnings.php','conv-archive.php','conv-auto-label.php','conv-bookmarks.php','conv-export.php','conv-labels.php','conv-media.php','conv-notes.php','conv-pin.php','conv-polls.php','conv-quick-actions.php','conv-read-status.php','conv-reminders.php','conv-schedule.php','conv-search.php','conv-tags.php','conv-threads.php','daily-digest.php','dashboard-summary.php','delivery-map.php','delivery-stats.php','drafts-manager.php','engagement-dashboard.php','engagement-heatmap.php','engagement-predict.php','engagement-score.php','events.php','export.php','faq.php','feature-flags.php','feed-prefs.php','feedback.php','follow-requests.php','follow-suggest.php','friends.php','gamification.php','group-admin.php','group-chat.php','group-settings.php','groups.php','growth-funnel.php','hashtags.php','health.php','health-alerts.php','heatmap.php','income-tracker.php','insights.php','leaderboard-seasons.php','link-preview.php','logs.php','maintenance.php','marketplace.php','media.php','mentions.php','messages.php','milestones.php','mod-queue.php','moderation.php','msg-forward.php','msg-poll.php','msg-reactions.php','mute.php','notif-blast.php','notif-grouped.php','notif-prefs.php','notifications.php','og-tags.php','online-privacy.php','outgoing-hooks.php','payment.php','perf.php','pins.php','platform-stats.php','polls.php','post-analytics.php','post-boost.php','post-collab.php','post-collections.php','post-digest.php','post-expiry.php','post-highlights.php','post-polls.php','post-reach.php','post-schedule-v2.php','post-sentiment.php','post-share-stats.php','post-similar.php','post-stats-detail.php','post-views.php','posts.php','preferences.php','presence.php','privacy.php','profile-card.php','profile-score.php','profile-theme.php','profile-themes.php','push.php','qr.php','queue-dashboard.php','rate-monitor.php','reactions.php','reactions-analytics.php','read-later.php','recommend.php','referral-dashboard.php','referrals.php','report-analytics.php','reputation.php','reputation-tiers.php','saved-collections.php','saved-replies.php','schedule-calendar.php','schedule-templates.php','scheduled.php','search.php','search-history.php','share-to-group.php','shipper-map.php','short-link.php','site-config.php','sitemap.php','sitemap-gen.php','skill-tags.php','smart-schedule.php','smart-suggest.php','social.php','sse.php','starred-msgs.php','stats.php','status.php','stories.php','suggest.php','system-config.php','system-health.php','template-market.php','templates.php','timeline.php','traffic.php','trending.php','trending-topics.php','two-factor.php','typing-stats.php','upload-media.php','user-activity.php','user-availability.php','user-compare.php','user-connections.php','user-goals.php','user-lifecycle.php','user-notes.php','user-portfolio.php','user-prefs.php','user-ratings.php','user-segments.php','user-summary-card.php','user-theme.php','users.php','verification.php','voice-notes.php','wallet.php','wallet-chart.php','webhooks.php','weekly-report.php','work-history.php','index.php'];
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
$staticFiles=['css/design-system.css','css/design-system.min.css','js/core/api.js','js/core/store.js','js/core/ui.js','js/core/utils.js','js/components/a11y.js','js/components/ab-test.js','js/components/achievements-ui.js','js/components/achievements-wall.js','js/components/activity-heatmap.js','js/components/admin-audit.js','js/components/admin-dash-widget.js','js/components/admin-export.js','js/components/admin-revenue.js','js/components/analytics-export.js','js/components/announce-banner.js','js/components/announcement.js','js/components/author-stats.js','js/components/auto-draft.js','js/components/auto-tag.js','js/components/badge-display.js','js/components/badge-showcase.js','js/components/badges-wall.js','js/components/block-user.js','js/components/calendar.js','js/components/celebrate.js','js/components/charts.js','js/components/clipboard.js','js/components/collections-ui.js','js/components/comment-sheet.js','js/components/connection-status.js','js/components/content-calendar.js','js/components/content-insights.js','js/components/content-summary.js','js/components/content-warnings.js','js/components/conv-archive.js','js/components/conv-auto-label.js','js/components/conv-bookmarks.js','js/components/conv-labels.js','js/components/conv-media.js','js/components/conv-polls.js','js/components/conv-quick-actions.js','js/components/conv-read-status.js','js/components/conv-reminders.js','js/components/conv-schedule.js','js/components/conv-tags.js','js/components/conv-threads.js','js/components/dark-mode.js','js/components/delivery-map.js','js/components/draft-save.js','js/components/drafts-manager.js','js/components/emoji-picker.js','js/components/engagement-dashboard.js','js/components/engagement-heatmap.js','js/components/engagement-predict.js','js/components/engagement-score.js','js/components/error-boundary.js','js/components/fab-menu.js','js/components/faq.js','js/components/feature-flags.js','js/components/feed-filter.js','js/components/feed-mute.js','js/components/feedback.js','js/components/follow-requests.js','js/components/follow-suggest.js','js/components/freshness.js','js/components/gamification.js','js/components/growth-funnel.js','js/components/hashtags.js','js/components/health-alerts.js','js/components/heatmap.js','js/components/image-optimizer.js','js/components/image-viewer.js','js/components/img-compress.js','js/components/income-tracker.js','js/components/infinite-scroll.js','js/components/insights.js','js/components/lazy-img.js','js/components/lazy-loader.js','js/components/leaderboard-seasons.js','js/components/link-preview.js','js/components/location-picker.js','js/components/maintenance.js','js/components/mention-picker.js','js/components/milestones-ui.js','js/components/milestones.js','js/components/mod-queue.js','js/components/msg-forward.js','js/components/msg-poll.js','js/components/msg-reactions.js','js/components/mute-user.js','js/components/notif-grouped.js','js/components/notif-poll.js','js/components/notif-prefs.js','js/components/notif-sound.js','js/components/notification-bell.js','js/components/online-privacy.js','js/components/online-widget.js','js/components/payment.js','js/components/perf-monitor.js','js/components/perf-reporter.js','js/components/platform-stats.js','js/components/poll-ui.js','js/components/poll.js','js/components/post-analytics.js','js/components/post-boost.js','js/components/post-card.js','js/components/post-collab.js','js/components/post-create.js','js/components/post-digest.js','js/components/post-edit.js','js/components/post-highlights.js','js/components/post-location.js','js/components/post-reach.js','js/components/post-reactions.js','js/components/post-sentiment.js','js/components/post-similar.js','js/components/post-stats-detail.js','js/components/post-templates-ui.js','js/components/post-templates.js','js/components/post-types.js','js/components/post-utils.js','js/components/prefs-sync.js','js/components/privacy-settings.js','js/components/profile-card.js','js/components/profile-complete.js','js/components/profile-completion.js','js/components/profile-themes.js','js/components/pwa-install.js','js/components/qr-share.js','js/components/queue-dash.js','js/components/quick-post.js','js/components/reactions-analytics.js','js/components/read-later.js','js/components/reading-time.js','js/components/realtime.js','js/components/recommend.js','js/components/referral-dash.js','js/components/report-dialog.js','js/components/reputation-display.js','js/components/reputation-tiers.js','js/components/reputation.js','js/components/saved-collections.js','js/components/saved-replies.js','js/components/schedule-calendar.js','js/components/schedule-templates.js','js/components/scroll-heartbeat.js','js/components/scroll-progress.js','js/components/search-filters.js','js/components/search-history.js','js/components/search-overlay.js','js/components/share-sheet.js','js/components/share-to-group.js','js/components/shipper-map-data.js','js/components/short-link.js','js/components/shortcuts.js','js/components/skill-tags.js','js/components/smart-schedule.js','js/components/smart-suggest.js','js/components/starred-msgs.js','js/components/stories.js','js/components/sub-badge.js','js/components/swipe.js','js/components/template-market.js','js/components/text-format.js','js/components/theme-picker.js','js/components/timeline.js','js/components/tooltip.js','js/components/trending-topics.js','js/components/trending-widget.js','js/components/two-factor.js','js/components/typing-indicator.js','js/components/typing-stats.js','js/components/upload.js','js/components/user-availability.js','js/components/user-card.js','js/components/user-compare.js','js/components/user-connections.js','js/components/user-goals.js','js/components/user-lifecycle.js','js/components/user-notes.js','js/components/user-portfolio.js','js/components/user-ratings.js','js/components/user-segments.js','js/components/user-summary-card.js','js/components/user-theme.js','js/components/verified-badge.js','js/components/video-player.js','js/components/view-tracker.js','js/components/voice-notes.js','js/components/wallet-chart.js','js/components/weekly-report.js','js/components/work-history.js','js/pages/account-settings.js','js/pages/activity-log.js','js/pages/admin-logs.js','js/pages/admin-mod.js','js/pages/admin-users.js','js/pages/admin.js','js/pages/auth.js','js/pages/bookmarks.js','js/pages/content-queue.js','js/pages/content-stats.js','js/pages/feed.js','js/pages/group-detail.js','js/pages/groups.js','js/pages/leaderboard.js','js/pages/listing-detail.js','js/pages/map-page.js','js/pages/marketplace.js','js/pages/messages.js','js/pages/people.js','js/pages/post-detail.js','js/pages/preferences.js','js/pages/profile-settings.js','js/pages/scheduled.js','js/pages/settings.js','js/pages/system-config.js','js/pages/traffic.js','js/pages/user-profile.js','js/pages/wallet.js','js/ss-bundle.min.js','js/ss-prod.js'];
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

// Daily Digest
$digestResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/daily-digest.php'),true);
t('Func: daily digest',$digestResp&&$digestResp['success']===true&&isset($digestResp['data']['greeting']));
t('Func: digest trending',isset($digestResp['data']['trending'])&&is_array($digestResp['data']['trending']));
t('Func: digest today stats',isset($digestResp['data']['today'])&&isset($digestResp['data']['today']['new_posts']));

// Delivery Stats (platform)
$delResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/delivery-stats.php?action=platform'),true);
t('Func: delivery platform',$delResp&&$delResp['success']===true&&isset($delResp['data']['total_deliveries']));
t('Func: delivery companies',isset($delResp['data']['top_companies'])&&is_array($delResp['data']['top_companies']));

// Checkin (leaderboard - no auth needed)
$ciResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/checkin.php?action=leaderboard'),true);
t('Func: checkin leaderboard',$ciResp&&$ciResp['success']===true);

// Profile Themes (presets)
$ptResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/profile-themes.php?action=presets'),true);
t('Func: profile themes',$ptResp&&$ptResp['success']===true&&count($ptResp['data']['presets'])>=8);

// Health Alerts
$haResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/health-alerts.php'),true);
t('Func: health alerts',$haResp&&$haResp['success']===true&&isset($haResp['data']['status']));
t('Func: health status ok',$haResp['data']['status']==='healthy'||$haResp['data']['status']==='warning');

// Site Config
$scResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/site-config.php'),true);
t('Func: site config',$scResp&&$scResp['success']===true&&isset($scResp['data']['site']));
t('Func: site features',count($scResp['data']['features']??[])>=10);
t('Func: shipping companies',count($scResp['data']['shipping_companies']??[])>=8);

// Suggest (search autocomplete)
$sugResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/suggest.php?q=admin'),true);
t('Func: suggest search',$sugResp&&$sugResp['success']===true);

// Sitemap
$smResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/sitemap-gen.php?format=json'),true);
t('Func: sitemap gen',$smResp&&$smResp['success']===true&&$smResp['data']['count']>=100);

// Timeline
$tlResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/timeline.php?user_id=2&limit=5'),true);
t('Func: timeline',$tlResp&&$tlResp['success']===true&&isset($tlResp['data']['events']));

// Referral leaderboard
$refLbResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/referral-dashboard.php?action=leaderboard'),true);
t('Func: referral leaderboard',$refLbResp&&$refLbResp['success']===true);

// Engagement score
$engResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/engagement-score.php?user_id=2&days=30'),true);
t('Func: engagement score',$engResp&&$engResp['success']===true&&isset($engResp['data']['score']));
t('Func: engagement rank',isset($engResp['data']['rank'])&&strlen($engResp['data']['rank'])>0);
t('Func: engagement metrics',isset($engResp['data']['metrics'])&&isset($engResp['data']['platform_avg']));

// Delivery stats user
$delUserResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/delivery-stats.php?user_id=2'),true);
t('Func: delivery user stats',$delUserResp&&$delUserResp['success']===true&&isset($delUserResp['data']['total_deliveries']));

// Achievements
$achResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/achievements.php?user_id=2'),true);
t('Func: achievements data',$achResp&&$achResp['success']===true&&isset($achResp['data']['total_earned']));
t('Func: achievements xp',isset($achResp['data']['total_xp'])&&$achResp['data']['total_xp']>=0);

// Badge showcase
$badgeResp2=json_decode(@file_get_contents('https://shippershop.vn/api/v2/badge-showcase.php?user_id=2'),true);
t('Func: badge earned count',$badgeResp2&&$badgeResp2['success']===true&&$badgeResp2['data']['total_earned']>=1);

// Schedule calendar (requires auth, just check endpoint exists)
// Schedule calendar requires auth - check endpoint responds
$calCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$calRaw=@file_get_contents('https://shippershop.vn/api/v2/schedule-calendar.php',false,$calCtx);
$calResp=json_decode($calRaw,true);
t('Func: schedule calendar',$calResp!==null);

// Checkin nearby
$ciNearResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/checkin.php?action=nearby'),true);
t('Func: checkin nearby',$ciNearResp&&$ciNearResp['success']===true);


// Trending topics
$ttResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/trending-topics.php?hours=24'),true);
t('Func: trending topics',$ttResp&&$ttResp['success']===true&&isset($ttResp['data']['hot_posts']));
t('Func: trending provinces',isset($ttResp['data']['active_provinces']));
t('Func: trending companies',isset($ttResp['data']['active_companies']));

// Ban appeals (list — requires admin, check endpoint exists)
$baCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$baResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/ban-appeals.php',false,$baCtx),true);
t('Func: ban appeals endpoint',$baResp!==null);

// Msg reactions (GET requires auth, check endpoint)
$mrCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$mrResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/msg-reactions.php?message_id=1',false,$mrCtx),true);
t('Func: msg reactions endpoint',$mrResp!==null);


// Follow requests (requires auth, check endpoint)
$frCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$frResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/follow-requests.php',false,$frCtx),true);
t('Func: follow requests endpoint',$frResp!==null);

// Drafts manager (requires auth)
$dmCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$dmResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/drafts-manager.php',false,$dmCtx),true);
t('Func: drafts manager endpoint',$dmResp!==null);

// Admin timeline (requires admin)
$atCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$atResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/admin-timeline.php',false,$atCtx),true);
t('Func: admin timeline endpoint',$atResp!==null);

// Conv notes (requires auth)
$cnCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$cnResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/conv-notes.php?conversation_id=1',false,$cnCtx),true);
t('Func: conv notes endpoint',$cnResp!==null);

// Weekly report (requires auth)
$wrCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$wrResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/weekly-report.php',false,$wrCtx),true);
t('Func: weekly report endpoint',$wrResp!==null);


// FAQ
$faqResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/faq.php'),true);
t('Func: faq list',$faqResp&&$faqResp['success']===true&&count($faqResp['data']['faqs'])>=10);
t('Func: faq categories',count($faqResp['data']['categories'])>=5);

// FAQ search
$faqSResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/faq.php?q=dang+ky'),true);
t('Func: faq search',$faqSResp&&$faqSResp['success']===true);

// Post expiry (GET without post)
$peResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/post-expiry.php?post_id=5'),true);
t('Func: post expiry check',$peResp&&$peResp['success']===true);

// Feedback (POST test via stream)
$fbCtx=stream_context_create(['http'=>['method'=>'POST','header'=>'Content-Type: application/json','content'=>json_encode(['type'=>'test','message'=>'Test feedback from test suite']),'ignore_errors'=>true]]);
$fbResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/feedback.php',false,$fbCtx),true);
t('Func: feedback submit',$fbResp&&$fbResp['success']===true);


// Maintenance mode (public check)
$mmResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/maintenance.php'),true);
t('Func: maintenance check',$mmResp&&$mmResp['success']===true&&isset($mmResp['data']['active']));

// Read later (requires auth)
$rlCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$rlResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/read-later.php',false,$rlCtx),true);
t('Func: read later endpoint',$rlResp!==null);

// Starred msgs (requires auth)
$smCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$smResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/starred-msgs.php',false,$smCtx),true);
t('Func: starred msgs endpoint',$smResp!==null);

// Admin IP (requires admin)
$ipCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$ipResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/admin-ip.php',false,$ipCtx),true);
t('Func: admin ip endpoint',$ipResp!==null);


// Short link (resolve)
$slResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/short-link.php?code=test'),true);
t('Func: short link resolve',$slResp&&$slResp['success']===true);

// Online privacy (requires auth)
$opCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$opResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/online-privacy.php',false,$opCtx),true);
t('Func: online privacy endpoint',$opResp!==null);

// Search history (requires auth)
$shCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$shResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/search-history.php',false,$shCtx),true);
t('Func: search history endpoint',$shResp!==null);

// Admin backup (requires admin)
$abCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$abResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/admin-backup.php',false,$abCtx),true);
t('Func: admin backup endpoint',$abResp!==null);


// Activity heatmap (public)
$hmResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/activity-heatmap.php?user_id=2&days=30'),true);
t('Func: activity heatmap',$hmResp&&$hmResp['success']===true&&isset($hmResp['data']['days']));
t('Func: heatmap stats',isset($hmResp['data']['total_contributions'])&&isset($hmResp['data']['active_days']));

// Post share stats (public GET)
$pssResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/post-share-stats.php?post_id=5'),true);
t('Func: post share stats',$pssResp&&$pssResp['success']===true&&isset($pssResp['data']['total']));

// User notes (requires auth)
$unCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$unResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/user-notes.php',false,$unCtx),true);
t('Func: user notes endpoint',$unResp!==null);

// User theme (requires auth)
$utCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$utResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/user-theme.php',false,$utCtx),true);
t('Func: user theme endpoint',$utResp!==null);

// Content calendar (requires auth)
$ccCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$ccResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/content-calendar.php',false,$ccCtx),true);
t('Func: content calendar endpoint',$ccResp!==null);

// Delivery stats user (public)
$ds2Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/delivery-stats.php?user_id=3&action=summary'),true);
t('Func: delivery stats user 3',$ds2Resp&&$ds2Resp['success']===true);

// Trending 72h
$tt72Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/trending-topics.php?hours=72'),true);
t('Func: trending 72h',$tt72Resp&&$tt72Resp['success']===true&&isset($tt72Resp['data']['rising_users']));

// FAQ category filter
$faqWResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/faq.php?category=wallet'),true);
t('Func: faq wallet category',$faqWResp&&$faqWResp['success']===true&&count($faqWResp['data']['faqs'])>=2);

// Heatmap with 365 days
$hm365Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/activity-heatmap.php?user_id=2&days=365'),true);
t('Func: heatmap 365d',$hm365Resp&&$hm365Resp['success']===true&&count($hm365Resp['data']['days'])>=300);

// Engagement score user 3
$eng3Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/engagement-score.php?user_id=3'),true);
t('Func: engagement user 3',$eng3Resp&&$eng3Resp['success']===true);

// Timeline user 3
$tl3Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/timeline.php?user_id=3&limit=3'),true);
t('Func: timeline user 3',$tl3Resp&&$tl3Resp['success']===true);

// Suggest empty
$sugEResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/suggest.php?q='),true);
t('Func: suggest empty',$sugEResp&&$sugEResp['success']===true&&count($sugEResp['data'])===0);

// Short link create via POST
$slCtx=stream_context_create(['http'=>['method'=>'POST','header'=>'Content-Type: application/json','content'=>json_encode(['type'=>'post','id'=>5])]]);
$slCreateResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/short-link.php',false,$slCtx),true);
t('Func: short link create',$slCreateResp&&$slCreateResp['success']===true&&!empty($slCreateResp['data']['short_url']));

// Feedback categories count
$faqCatResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/faq.php?action=categories'),true);
t('Func: faq categories only',$faqCatResp&&$faqCatResp['success']===true&&count($faqCatResp['data'])>=5);

// Platform delivery
$pdResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/delivery-stats.php?action=platform'),true);
t('Func: platform top provinces',isset($pdResp['data']['top_provinces'])&&count($pdResp['data']['top_provinces'])>=5);


// User compare (public)
$ucResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/user-compare.php?user1=2&user2=3'),true);
t('Func: user compare',$ucResp&&$ucResp['success']===true&&isset($ucResp['data']['comparison']));
t('Func: compare wins',isset($ucResp['data']['wins'])&&isset($ucResp['data']['wins']['user1']));
t('Func: compare user1 stats',isset($ucResp['data']['user1']['stats']['posts']));

// Platform stats (public)
$psResp2=json_decode(@file_get_contents('https://shippershop.vn/api/v2/platform-stats.php'),true);
t('Func: platform stats',$psResp2&&$psResp2['success']===true&&isset($psResp2['data']['users']));
t('Func: platform users total',$psResp2['data']['users']['total']>=100);
t('Func: platform deliveries',isset($psResp2['data']['deliveries']['total']));
t('Func: platform provinces',$psResp2['data']['provinces_covered']>=5);

// Content summary (public)
$csResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/content-summary.php?hours=168'),true);
t('Func: content summary',$csResp&&$csResp['success']===true);
t('Func: content engagement',isset($csResp['data']['engagement_rate']));
t('Func: content peak hours',isset($csResp['data']['peak_hours']));

// Shipper map (public)
$smResp2=json_decode(@file_get_contents('https://shippershop.vn/api/v2/shipper-map.php?action=pins'),true);
t('Func: shipper map pins',$smResp2&&$smResp2['success']===true);

// Shipper map province heatmap
$smHeatResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/shipper-map.php?action=province_heat'),true);
t('Func: shipper province heat',$smHeatResp&&$smHeatResp['success']===true&&is_array($smHeatResp['data']));

// Post stats detail (public partial)
$psdResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/post-stats-detail.php?post_id=125'),true);
t('Func: post stats detail',$psdResp&&$psdResp['success']===true&&isset($psdResp['data']['likes']));
t('Func: post stats views',isset($psdResp['data']['views']));

// User notes (auth required)
$notesCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$notesResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/user-notes.php',false,$notesCtx),true);
t('Func: user notes endpoint 2',$notesResp!==null);

// Content calendar (auth required)
$cc2Ctx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$cc2Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/content-calendar.php?month=2026-03',false,$cc2Ctx),true);
t('Func: content calendar month',$cc2Resp!==null);

// Checkin nearby with province filter
$ciProvResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/checkin.php?action=nearby'),true);
t('Func: checkin province filter',$ciProvResp&&$ciProvResp['success']===true);

// Post share stats default
$pss0Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/post-share-stats.php?post_id=999'),true);
t('Func: share stats default',$pss0Resp&&$pss0Resp['success']===true&&$pss0Resp['data']['total']===0);


// Badges wall (public)
$bwResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/badges-wall.php?action=user&user_id=2'),true);
t('Func: badges wall',$bwResp&&$bwResp['success']===true&&isset($bwResp['data']['badges']));
t('Func: badges count',$bwResp['data']['total']>=10);
t('Func: badges progress',isset($bwResp['data']['earned_count']));

// Optimal posting times (public)
$optResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/post-schedule-v2.php?action=optimal_times'),true);
t('Func: optimal times',$optResp&&$optResp['success']===true&&isset($optResp['data']['hourly']));
t('Func: optimal best times',isset($optResp['data']['best_times']));

// Conv tags (requires auth)
$ctCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$ctResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/conv-tags.php',false,$ctCtx),true);
t('Func: conv tags endpoint',$ctResp!==null);

// Admin revenue (requires admin)
$arCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$arResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/admin-revenue.php',false,$arCtx),true);
t('Func: admin revenue endpoint',$arResp!==null);


// Milestones (public)
$msResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/milestones.php?user_id=2'),true);
t('Func: milestones',$msResp&&$msResp['success']===true&&isset($msResp['data']['earned']));
t('Func: milestones progress',count($msResp['data']['progress'])>=10);
t('Func: milestones earned count',$msResp['data']['total_earned']>=1);

// Schedule templates (public GET)
$stResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/schedule-templates.php'),true);
t('Func: schedule templates',$stResp&&$stResp['success']===true&&count($stResp['data']['templates'])>=8);

// Conv reminders (requires auth)
$crCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$crResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/conv-reminders.php',false,$crCtx),true);
t('Func: conv reminders endpoint',$crResp!==null);

// Admin revenue (requires admin)
$arCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$arResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/admin-revenue.php',false,$arCtx),true);
t('Func: admin revenue endpoint',$arResp!==null);


// Badges wall catalog
$bwResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/badges-wall.php?action=catalog'),true);
t('Func: badges catalog',$bwResp&&$bwResp['success']===true&&count($bwResp['data']['badges'])>=10);

// Badges wall user
$bwuResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/badges-wall.php?action=user&user_id=2'),true);
t('Func: badges user',$bwuResp&&$bwuResp['success']===true&&isset($bwuResp['data']['earned_count']));

// Badges leaderboard
$bwlResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/badges-wall.php?action=leaderboard'),true);
t('Func: badges leaderboard',$bwlResp&&$bwlResp['success']===true);

// Post sentiment
$psaResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/post-sentiment.php?post_id=125'),true);
t('Func: post sentiment',$psaResp&&$psaResp['success']===true&&isset($psaResp['data']['label']));

// Sentiment overview
$psoResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/post-sentiment.php?action=overview'),true);
t('Func: sentiment overview',$psoResp&&$psoResp['success']===true&&isset($psoResp['data']['positive_pct']));

// User segments (admin)
$usCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$usResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/user-segments.php',false,$usCtx),true);
t('Func: user segments endpoint',$usResp!==null);

// Conv export (auth)
$ceCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$ceResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/conv-export.php?conversation_id=1',false,$ceCtx),true);
t('Func: conv export endpoint',$ceResp!==null);


// Auto-tag
$atResp2=json_decode(@file_get_contents('https://shippershop.vn/api/v2/auto-tag.php?text=ghtk+giao+hang+nhanh+shipper'),true);
t('Func: auto tag',$atResp2&&$atResp2['success']===true&&count($atResp2['data']['suggested_tags'])>=1);

// Achievements wall leaderboard
$awlResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/achievements-wall.php?action=leaderboard'),true);
t('Func: achievements leaderboard',$awlResp&&$awlResp['success']===true);

// Achievements wall user
$awuResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/achievements-wall.php?action=user&user_id=2'),true);
t('Func: achievements user',$awuResp&&$awuResp['success']===true&&isset($awuResp['data']['level']));
t('Func: achievements xp detail',isset($awuResp['data']['total_xp'])&&$awuResp['data']['level']>=1);

// Achievements streaks
$awsResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/achievements-wall.php?action=streaks'),true);
t('Func: achievements streaks',$awsResp&&$awsResp['success']===true);

// Admin content schedule (requires admin)
$acsCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$acsResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/admin-content-schedule.php',false,$acsCtx),true);
t('Func: admin content schedule',$acsResp!==null);

// Typing stats (requires auth)
$tsCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$tsResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/typing-stats.php',false,$tsCtx),true);
t('Func: typing stats endpoint',$tsResp!==null);


// User portfolio (public)
$upResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/user-portfolio.php?user_id=2'),true);
t('Func: user portfolio',$upResp&&$upResp['success']===true&&isset($upResp['data']['user']));
t('Func: portfolio stats',isset($upResp['data']['stats']['posts'])&&isset($upResp['data']['stats']['level']));
t('Func: portfolio best posts',is_array($upResp['data']['best_posts']));

// Engagement predictor
$epResp=json_decode(@file_get_contents(str_replace(' ','+','https://shippershop.vn/api/v2/engagement-predict.php?text=ghtk+giao+hang+nhanh+tphcm+shipper+chia+se+kinh+nghiem')),true);
t('Func: engagement predict',$epResp&&$epResp['success']===true&&isset($epResp['data']['score']));
t('Func: predict factors',is_array($epResp['data']['factors'])&&count($epResp['data']['factors'])>=1);

// Announce schedule (public active)
$annSchResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/announce-schedule.php'),true);
t('Func: announce schedule active',$annSchResp&&$annSchResp['success']===true&&isset($annSchResp['data']['announcements']));

// Conv quick actions (requires auth)
$cqaCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$cqaResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/conv-quick-actions.php?conversation_id=1',false,$cqaCtx),true);
t('Func: conv quick actions',$cqaResp!==null);

// Portfolio user 3
$up3Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/user-portfolio.php?user_id=3'),true);
t('Func: portfolio user 3',$up3Resp&&$up3Resp['success']===true);

// Predict empty text
$epEmptyResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/engagement-predict.php?text=hi'),true);
t('Func: predict short text',$epEmptyResp&&$epEmptyResp['success']===true&&$epEmptyResp['data']['score']===50);


// Post digest daily
$pdgResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/post-digest.php?period=daily'),true);
t('Func: post digest daily',$pdgResp&&$pdgResp['success']===true&&isset($pdgResp['data']['top_posts']));
t('Func: digest stats',isset($pdgResp['data']['stats']['posts']));

// Post digest weekly
$pdwResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/post-digest.php?period=weekly'),true);
t('Func: post digest weekly',$pdwResp&&$pdwResp['success']===true&&$pdwResp['data']['period']==='weekly');

// User connections
$ucnResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/user-connections.php?user_id=2'),true);
t('Func: user connections',$ucnResp&&$ucnResp['success']===true&&isset($ucnResp['data']['followers']));
t('Func: connections mutuals',isset($ucnResp['data']['mutuals']));

// User connections mutual
$ucmResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/user-connections.php?user_id=2&action=mutual&other_id=3'),true);
t('Func: mutual connections',$ucmResp&&$ucmResp['success']===true&&isset($ucmResp['data']['count']));

// Admin stats cache (requires admin)
$ascCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$ascResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/admin-stats-cache.php?action=info',false,$ascCtx),true);
t('Func: admin cache endpoint',$ascResp!==null);

// Cross-check: platform stats users > posts
$ps3Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/platform-stats.php'),true);
t('Func: platform users > 500',$ps3Resp&&$ps3Resp['data']['users']['total']>=500);

// Cross-check: delivery stats > 50000
$ds3Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/delivery-stats.php?action=platform'),true);
t('Func: deliveries > 50000',$ds3Resp&&$ds3Resp['data']['total_deliveries']>=50000);


// Skill tags presets
$skResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/skill-tags.php'),true);
t('Func: skill tags presets',$skResp&&$skResp['success']===true&&count($skResp['data']['presets'])>=5);

// Skill tags user
$skuResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/skill-tags.php?user_id=2'),true);
t('Func: skill tags user',$skuResp&&$skuResp['success']===true);

// Template marketplace
$tmResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/template-market.php'),true);
t('Func: template market',$tmResp&&$tmResp['success']===true&&count($tmResp['data']['templates'])>=8);
t('Func: template categories',count($tmResp['data']['categories'])>=5);

// Template popular
$tmpResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/template-market.php?action=popular'),true);
t('Func: template popular',$tmpResp&&$tmpResp['success']===true&&count($tmpResp['data'])>=5);

// Notif blast history (requires admin)
$nbCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$nbResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/notif-blast.php',false,$nbCtx),true);
t('Func: notif blast endpoint',$nbResp!==null);

// Conv threads (requires auth)
$ctCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$ctResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/conv-threads.php?parent_id=1',false,$ctCtx),true);
t('Func: conv threads endpoint',$ctResp!==null);


// Reputation tiers list
$rtResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/reputation-tiers.php?action=tiers'),true);
t('Func: reputation tiers',$rtResp&&$rtResp['success']===true&&count($rtResp['data']['tiers'])>=5);

// Reputation user
$rtuResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/reputation-tiers.php?action=user&user_id=2'),true);
t('Func: reputation user',$rtuResp&&$rtuResp['success']===true&&isset($rtuResp['data']['score']));
t('Func: reputation tier name',!empty($rtuResp['data']['tier']['name']));
t('Func: reputation breakdown',isset($rtuResp['data']['breakdown']['posts']));

// Reputation leaderboard
$rtlResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/reputation-tiers.php?action=leaderboard'),true);
t('Func: reputation leaderboard',$rtlResp&&$rtlResp['success']===true&&is_array($rtlResp['data']));

// Analytics export (requires auth)
$aeCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$aeResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/analytics-export.php',false,$aeCtx),true);
t('Func: analytics export endpoint',$aeResp!==null);

// Admin dashboard v2 (requires admin)
$ad2Ctx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$ad2Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/admin-dashboard-v2.php',false,$ad2Ctx),true);
t('Func: admin dashboard v2',$ad2Resp!==null);

// Conv read status (requires auth)
$crsCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$crsResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/conv-read-status.php?conversation_id=1',false,$crsCtx),true);
t('Func: conv read status',$crsResp!==null);


// Smart schedule optimal
$ssResp2=json_decode(@file_get_contents('https://shippershop.vn/api/v2/smart-schedule.php'),true);
t('Func: smart schedule',$ssResp2&&$ssResp2['success']===true&&isset($ssResp2['data']['recommended_slots']));
t('Func: schedule hour stats',isset($ssResp2['data']['hour_stats']));

// Smart schedule next
$ssnResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/smart-schedule.php?action=next'),true);
t('Func: schedule next time',$ssnResp&&$ssnResp['success']===true&&isset($ssnResp['data']['next_time']));

// Wallet chart (requires auth)
$wcCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$wcResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/wallet-chart.php',false,$wcCtx),true);
t('Func: wallet chart endpoint',$wcResp!==null);

// Growth funnel (requires admin)
$gfCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$gfResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/growth-funnel.php',false,$gfCtx),true);
t('Func: growth funnel endpoint',$gfResp!==null);

// Conv media (requires auth)
$cmCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$cmResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/conv-media.php?conversation_id=1',false,$cmCtx),true);
t('Func: conv media endpoint',$cmResp!==null);


// Leaderboard seasons (public)
$lsResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/leaderboard-seasons.php?period=monthly&metric=posts'),true);
t('Func: leaderboard seasons',$lsResp&&$lsResp['success']===true&&isset($lsResp['data']['leaderboard']));
t('Func: leaderboard season name',!empty($lsResp['data']['season']));
t('Func: leaderboard rewards',count($lsResp['data']['rewards']??[])>=3);

// Leaderboard metrics
$lmResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/leaderboard-seasons.php?action=metrics'),true);
t('Func: leaderboard metrics',$lmResp&&$lmResp['success']===true&&count($lmResp['data']['metrics'])>=5);

// Leaderboard weekly XP
$lwxResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/leaderboard-seasons.php?period=weekly&metric=xp'),true);
t('Func: leaderboard weekly xp',$lwxResp&&$lwxResp['success']===true);

// AB test variant (public)
$abvResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/ab-test.php?action=variant&test_id=test1&user_id=2'),true);
t('Func: ab test variant',$abvResp&&$abvResp['success']===true&&isset($abvResp['data']['variant']));

// Post collab (requires auth)
$pcCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$pcResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/post-collab.php',false,$pcCtx),true);
t('Func: post collab endpoint',$pcResp!==null);

// Conv polls (requires auth)
$cpCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$cpResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/conv-polls.php?conversation_id=1',false,$cpCtx),true);
t('Func: conv polls endpoint',$cpResp!==null);


// Work history (public)
$whResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/work-history.php?user_id=2'),true);
t('Func: work history',$whResp&&$whResp['success']===true&&isset($whResp['data']['history']));

// Reactions analytics (public)
$raResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/reactions-analytics.php?days=30'),true);
t('Func: reactions analytics',$raResp&&$raResp['success']===true&&isset($raResp['data']['total_likes']));
t('Func: reactions avg',isset($raResp['data']['avg_per_post']));
t('Func: reactions top posts',is_array($raResp['data']['top_posts']));
t('Func: reactions top likers',is_array($raResp['data']['top_likers']));

// Reactions user
$rauResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/reactions-analytics.php?action=user&user_id=2'),true);
t('Func: reactions user',$rauResp&&$rauResp['success']===true&&isset($rauResp['data']['given']));

// Feature flags (public)
$ffResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/feature-flags.php'),true);
t('Func: feature flags',$ffResp&&$ffResp['success']===true&&isset($ffResp['data']['flags']));
t('Func: flags count',count($ffResp['data']['flags'])>=10);
t('Func: dark mode flag',$ffResp['data']['flags']['dark_mode']===true);

// Feature flag check specific
$ffcResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/feature-flags.php?action=check&flag=stories'),true);
t('Func: flag check stories',$ffcResp&&$ffcResp['success']===true&&$ffcResp['data']['enabled']===true);

// Conv schedule (requires auth)
$csCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$csResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/conv-schedule.php',false,$csCtx),true);
t('Func: conv schedule endpoint',$csResp!==null);

// Reactions analytics 7d
$ra7Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/reactions-analytics.php?days=7'),true);
t('Func: reactions 7d',$ra7Resp&&$ra7Resp['success']===true);

// Work history non-existent user
$wh0Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/work-history.php?user_id=999'),true);
t('Func: work history empty',$wh0Resp&&$wh0Resp['success']===true&&count($wh0Resp['data']['history'])===0);

// Feature flags disabled check
$ff2Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/feature-flags.php?action=check&flag=ai_content'),true);
t('Func: flag check disabled',$ff2Resp&&$ff2Resp['data']['enabled']===false);

// Leaderboard deliveries
$ldResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/leaderboard-seasons.php?period=monthly&metric=deliveries'),true);
t('Func: leaderboard deliveries',$ldResp&&$ldResp['success']===true);

// Platform stats online
$ps4Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/platform-stats.php'),true);
t('Func: platform shipping companies',$ps4Resp['data']['shipping_companies']>=5);

// Smart schedule day stats
$ss3Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/smart-schedule.php'),true);
t('Func: schedule day stats',isset($ss3Resp['data']['day_stats'])&&count($ss3Resp['data']['day_stats'])>=1);

// Milestones user 3
$ms3Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/milestones.php?user_id=3'),true);
t('Func: milestones user 3',$ms3Resp&&$ms3Resp['success']===true&&isset($ms3Resp['data']['total_earned']));


// Post reach (public)
$prResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/post-reach.php?user_id=2'),true);
t('Func: post reach',$prResp&&$prResp['success']===true&&isset($prResp['data']['total_estimated_reach']));
t('Func: reach followers',isset($prResp['data']['direct_followers']));

// Post reach by post
$pr2Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/post-reach.php?post_id=125'),true);
t('Func: post reach by post',$pr2Resp&&$pr2Resp['success']===true);

// User availability statuses
$uaResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/user-availability.php'),true);
t('Func: availability statuses',$uaResp&&$uaResp['success']===true&&count($uaResp['data']['statuses'])>=4);

// User availability check
$ua2Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/user-availability.php?user_id=2'),true);
t('Func: availability user',$ua2Resp&&$ua2Resp['success']===true&&isset($ua2Resp['data']['current']));

// Post similar
$psimResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/post-similar.php?post_id=125'),true);
t('Func: post similar',$psimResp&&$psimResp['success']===true&&isset($psimResp['data']['similar']));

// User goals (requires auth)
$ugCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$ugResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/user-goals.php',false,$ugCtx),true);
t('Func: user goals endpoint',$ugResp!==null);

// Admin audit (requires admin)
$aaCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$aaResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/admin-audit.php',false,$aaCtx),true);
t('Func: admin audit endpoint',$aaResp!==null);

// Admin audit actions
$aa2Ctx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$aa2Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/admin-audit.php?action=actions',false,$aa2Ctx),true);
t('Func: audit actions list',$aa2Resp!==null);


// Income tracker (requires auth)
$itCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$itResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/income-tracker.php',false,$itCtx),true);
t('Func: income tracker endpoint',$itResp!==null);

// Post highlights (public)
$phResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/post-highlights.php?user_id=2'),true);
t('Func: post highlights',$phResp&&$phResp['success']===true&&isset($phResp['data']['highlights']));

// Mod queue (requires admin)
$mqCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$mqResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/mod-queue.php',false,$mqCtx),true);
t('Func: mod queue endpoint',$mqResp!==null);

// Conv bookmarks (requires auth)
$cbCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$cbResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/conv-bookmarks.php',false,$cbCtx),true);
t('Func: conv bookmarks endpoint',$cbResp!==null);

// User summary card (public)
$uscResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/user-summary-card.php?user_id=2'),true);
t('Func: user summary card',$uscResp&&$uscResp['success']===true&&isset($uscResp['data']['fullname']));
t('Func: summary card level',$uscResp['data']['level']>=1);
t('Func: summary card stats',$uscResp['data']['posts']>=1&&$uscResp['data']['days']>=1);

// User summary card user 3
$usc3Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/user-summary-card.php?user_id=3'),true);
t('Func: summary card user 3',$usc3Resp&&$usc3Resp['success']===true);

// Post highlights empty user
$ph0Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/post-highlights.php?user_id=999'),true);
t('Func: highlights empty',$ph0Resp&&$ph0Resp['success']===true&&count($ph0Resp['data']['highlights'])===0);


// User ratings categories
$urResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/user-ratings.php'),true);
t('Func: rating categories',$urResp&&$urResp['success']===true&&count($urResp['data']['categories'])>=5);

// User ratings for user
$ur2Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/user-ratings.php?user_id=2'),true);
t('Func: user rating',$ur2Resp&&$ur2Resp['success']===true&&isset($ur2Resp['data']['overall']));
t('Func: rating by category',isset($ur2Resp['data']['by_category']));

// Content warnings types
$cwResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/content-warnings.php'),true);
t('Func: warning types',$cwResp&&$cwResp['success']===true&&count($cwResp['data']['types'])>=5);

// Content warnings for post
$cw2Resp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/content-warnings.php?post_id=125'),true);
t('Func: post warnings',$cw2Resp&&$cw2Resp['success']===true&&isset($cw2Resp['data']['warnings']));

// Engagement dashboard (admin)
$edCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$edResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/engagement-dashboard.php',false,$edCtx),true);
t('Func: engagement dashboard',$edResp!==null);

// Voice notes (auth)
$vnCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$vnResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/voice-notes.php?conversation_id=1',false,$vnCtx),true);
t('Func: voice notes endpoint',$vnResp!==null);


// Delivery map hotspots
$dmResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/delivery-map.php'),true);
t('Func: delivery hotspots',$dmResp&&$dmResp['success']===true&&isset($dmResp['data']['by_province']));
t('Func: delivery total location',$dmResp['data']['total_with_location']>=0);

// Delivery map user
$dmuResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/delivery-map.php?action=user&user_id=2'),true);
t('Func: delivery map user',$dmuResp&&$dmuResp['success']===true&&isset($dmuResp['data']['areas']));

// Engagement heatmap
$ehResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/engagement-heatmap.php'),true);
t('Func: engagement heatmap',$ehResp&&$ehResp['success']===true&&isset($ehResp['data']['grid']));
t('Func: heatmap best slots',isset($ehResp['data']['best_slots']));

// User lifecycle (admin)
$ulCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$ulResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/user-lifecycle.php',false,$ulCtx),true);
t('Func: user lifecycle endpoint',$ulResp!==null);

// Conv auto-label (auth)
$calCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$calResp=json_decode(@file_get_contents('https://shippershop.vn/api/v2/conv-auto-label.php?conversation_id=1',false,$calCtx),true);
t('Func: conv auto label',$calResp!==null);

// ============ RESULTS ============
$total=$P+$F;
echo json_encode(['timestamp'=>date('Y-m-d H:i:s'),'passed'=>$P,'failed'=>$F,'total'=>$total,'score'=>$total>0?round($P/$total*100,1).'%':'0%','results'=>$R],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
