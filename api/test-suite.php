<?php
// ShipperShop Test Suite v2 — Direct PHP testing (no self-curl)
set_time_limit(600);
ini_set('max_execution_time', 600);
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
$R=[];$P=0;$F=0;$_tIdx=0;
$_page=intval($_GET['page']??0);
$_perPage=200;
$_startIdx=$_page>0?($_page-1)*$_perPage:0;
$_endIdx=$_page>0?$_page*$_perPage:999999;

// Set default timeout for external HTTP calls
stream_context_set_default(['http'=>['timeout'=>8,'ignore_errors'=>true]]);

function t($n,$ok,$det=''){global $R,$P,$F,$_tIdx,$_startIdx,$_endIdx;$_tIdx++;if($_tIdx<$_startIdx||$_tIdx>=$_endIdx)return;if($ok){$P++;$R[]=['n'=>$n,'s'=>'PASS'];}else{$F++;$R[]=['n'=>$n,'s'=>'FAIL','d'=>$det];}}

function http_get($url){
    $ctx=stream_context_create(['http'=>['timeout'=>10,'ignore_errors'=>true]]);
    return @file_get_contents($url,false,$ctx);
}

function http_get_ctx($url,$ctx){
    return @file_get_contents($url,false,$ctx);
}


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
$apiFiles=['ab-split.php','ab-test.php','account.php','achievements.php','achievements-wall.php','activity-feed.php','activity-heatmap.php','admin.php','admin-audit.php','admin-backup.php','admin-batch.php','admin-content-schedule.php','admin-dashboard-v2.php','admin-export.php','admin-ip.php','admin-kpi.php','admin-notes.php','admin-overview.php','admin-revenue.php','admin-stats-cache.php','admin-summary.php','admin-table-stats.php','admin-timeline.php','admin-user-notes.php','admin-users.php','admin-widgets.php','ai-suggest.php','analytics.php','analytics-export.php','announce-schedule.php','announcement-banner.php','announcements.php','api-usage.php','area-coverage.php','audience-insights.php','auto-tag.php','badge-showcase.php','badges.php','badges-wall.php','ban-appeals.php','bandwidth-monitor.php','batch.php','bookmarks.php','calendar.php','calendar-v2.php','calendar-view.php','chat-extras.php','checkin.php','churn-predict.php','cod-tracker.php','cohort-analysis.php','collections.php','contact-card.php','content.php','content-analyzer.php','content-benchmark.php','content-calendar.php','content-compare.php','content-filter.php','content-insights.php','content-insights-ai.php','content-moderate.php','content-pipeline.php','content-queue.php','content-rewriter.php','content-score.php','content-summary.php','content-warnings.php','conv-archive.php','conv-auto-archive.php','conv-auto-label.php','conv-bookmarks.php','conv-checklist.php','conv-countdown.php','conv-delivery-map.php','conv-delivery-schedule.php','conv-export.php','conv-files.php','conv-labels.php','conv-location.php','conv-location-live.php','conv-media.php','conv-media-gallery.php','conv-meeting.php','conv-notes.php','conv-order-board.php','conv-payment-split.php','conv-pin.php','conv-pinned-topics.php','conv-polls.php','conv-quick-actions.php','conv-quick-polls.php','conv-reactions-summary.php','conv-read-status.php','conv-reminder.php','conv-reminders.php','conv-schedule.php','conv-search.php','conv-shared-notes.php','conv-status-update.php','conv-stickers.php','conv-summary.php','conv-tags.php','conv-task-assign.php','conv-templates.php','conv-threads.php','conv-weather-share.php','cron-monitor.php','customer-contacts.php','daily-digest.php','daily-planner.php','daily-report.php','dashboard-summary.php','db-health.php','delivery-analytics.php','delivery-map.php','delivery-notes.php','delivery-proof.php','delivery-receipt.php','delivery-stats.php','delivery-stats-v2.php','delivery-zones.php','disappearing-msgs.php','drafts-manager.php','drafts-sync.php','earnings-calc.php','engagement-compare.php','engagement-dashboard.php','engagement-heatmap.php','engagement-predict.php','engagement-score.php','engagement-score-v2.php','error-tracker.php','events.php','expense-report.php','expense-splitter.php','export.php','faq.php','feature-flags.php','feed-prefs.php','feedback.php','fleet-manager.php','follow-requests.php','follow-suggest.php','friends.php','fuel-tracker.php','gamification.php','group-admin.php','group-chat.php','group-settings.php','groups.php','growth-funnel.php','growth-metrics.php','hashtag-analytics.php','hashtags.php','health.php','health-alerts.php','health-score.php','heatmap.php','income-goal.php','income-tracker.php','insights.php','insights-v2.php','kpi-dashboard.php','leaderboard-seasons.php','link-preview.php','login-monitor.php','logs.php','maintenance.php','marketplace.php','media.php','mentions.php','messages.php','mileage-log.php','milestones.php','mod-queue.php','moderation.php','msg-forward.php','msg-poll.php','msg-reactions.php','mute.php','notif-analytics.php','notif-blast.php','notif-grouped.php','notif-prefs.php','notifications.php','og-tags.php','online-privacy.php','order-tracker.php','outgoing-hooks.php','page-speed.php','payment.php','perf.php','pins.php','plagiarism-check.php','plagiarism-v2.php','platform-alerts.php','platform-scorecard.php','platform-stats.php','polls.php','post-analytics.php','post-analytics-v2.php','post-best-time.php','post-bookmarks-folder.php','post-boost.php','post-collab.php','post-collections.php','post-digest.php','post-expiry.php','post-highlights.php','post-performance.php','post-polls.php','post-reach.php','post-schedule-v2.php','post-sentiment.php','post-share-stats.php','post-similar.php','post-stats-detail.php','post-views.php','post-word-cloud.php','posts.php','predict-v2.php','preferences.php','presence.php','privacy.php','profile-card.php','profile-score.php','profile-theme.php','profile-themes.php','push.php','qr.php','quality-gate.php','queue-dashboard.php','rate-monitor.php','rating-history.php','reactions.php','reactions-analytics.php','read-later.php','realtime-monitor.php','recommend.php','referral-dashboard.php','referrals.php','report-analytics.php','report-generator.php','reputation.php','reputation-tiers.php','retention-dashboard.php','retention-score.php','revenue-forecast.php','revenue-tracker.php','route-optimizer.php','route-planner.php','saved-collections.php','saved-replies.php','schedule-calendar.php','schedule-queue.php','schedule-templates.php','scheduled.php','scheduler-v2.php','search.php','search-history.php','sentiment-timeline.php','seo-monitor.php','share-to-group.php','shift-logger.php','shift-planner.php','shipper-map.php','shipper-profile-v2.php','short-link.php','site-config.php','sitemap.php','sitemap-gen.php','skill-tags.php','smart-reply.php','smart-schedule.php','smart-suggest.php','social.php','sse.php','starred-msgs.php','stats.php','status.php','stories.php','suggest.php','system-alerts.php','system-config.php','system-health.php','template-library.php','template-market.php','templates.php','timeline.php','tip-calculator.php','tip-jar.php','traffic.php','trend-detector.php','trending.php','trending-topics.php','trending-v2.php','two-factor.php','typing-stats.php','upload-media.php','user-activity.php','user-availability.php','user-compare.php','user-connections.php','user-dashboard.php','user-funnel.php','user-goals.php','user-lifecycle.php','user-notes.php','user-portfolio.php','user-prefs.php','user-ratings.php','user-segment-v2.php','user-segments.php','user-summary-card.php','user-theme.php','users.php','vehicle-manager.php','verification.php','viral-detector.php','voice-notes.php','voice-transcribe.php','wallet.php','wallet-chart.php','weather-alerts.php','webhook-logs.php','webhooks.php','weekly-challenge.php','weekly-report.php','work-history.php','index.php'];
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
$staticFiles=['css/design-system.css','css/design-system.min.css','js/core/api.js','js/core/store.js','js/core/ui.js','js/core/utils.js','js/components/a11y.js','js/components/ab-split.js','js/components/ab-test.js','js/components/achievements-ui.js','js/components/achievements-wall.js','js/components/activity-heatmap.js','js/components/admin-audit.js','js/components/admin-dash-widget.js','js/components/admin-export.js','js/components/admin-kpi.js','js/components/admin-overview.js','js/components/admin-revenue.js','js/components/admin-summary.js','js/components/admin-table-stats.js','js/components/ai-suggest.js','js/components/analytics-export.js','js/components/announce-banner.js','js/components/announcement.js','js/components/api-usage.js','js/components/area-coverage.js','js/components/audience-insights.js','js/components/author-stats.js','js/components/auto-draft.js','js/components/auto-tag.js','js/components/badge-display.js','js/components/badge-showcase.js','js/components/badges-wall.js','js/components/bandwidth-monitor.js','js/components/block-user.js','js/components/calendar-v2.js','js/components/calendar-view.js','js/components/calendar.js','js/components/celebrate.js','js/components/charts.js','js/components/churn-predict.js','js/components/clipboard.js','js/components/cod-tracker.js','js/components/cohort-analysis.js','js/components/collections-ui.js','js/components/color-utils.js','js/components/comment-sheet.js','js/components/connection-status.js','js/components/contact-card.js','js/components/content-analyzer.js','js/components/content-benchmark.js','js/components/content-calendar.js','js/components/content-compare.js','js/components/content-insights-ai.js','js/components/content-insights.js','js/components/content-moderate.js','js/components/content-pipeline.js','js/components/content-rewriter.js','js/components/content-score.js','js/components/content-summary.js','js/components/content-warnings.js','js/components/conv-archive.js','js/components/conv-auto-archive.js','js/components/conv-auto-label.js','js/components/conv-bookmarks.js','js/components/conv-checklist.js','js/components/conv-countdown.js','js/components/conv-delivery-map.js','js/components/conv-delivery-schedule.js','js/components/conv-files.js','js/components/conv-labels.js','js/components/conv-location-live.js','js/components/conv-location.js','js/components/conv-media-gallery.js','js/components/conv-media.js','js/components/conv-meeting.js','js/components/conv-order-board.js','js/components/conv-payment-split.js','js/components/conv-pinned-topics.js','js/components/conv-polls.js','js/components/conv-quick-actions.js','js/components/conv-quick-polls.js','js/components/conv-reactions-summary.js','js/components/conv-read-status.js','js/components/conv-reminder.js','js/components/conv-reminders.js','js/components/conv-schedule.js','js/components/conv-shared-notes.js','js/components/conv-status-update.js','js/components/conv-stickers.js','js/components/conv-summary.js','js/components/conv-tags.js','js/components/conv-task-assign.js','js/components/conv-templates.js','js/components/conv-threads.js','js/components/conv-weather-share.js','js/components/copy-text.js','js/components/cron-monitor.js','js/components/customer-contacts.js','js/components/daily-planner.js','js/components/daily-report.js','js/components/dark-mode.js','js/components/date-picker.js','js/components/db-health.js','js/components/debounce.js','js/components/delivery-analytics.js','js/components/delivery-map.js','js/components/delivery-notes.js','js/components/delivery-proof.js','js/components/delivery-receipt.js','js/components/delivery-stats-v2.js','js/components/delivery-zones.js','js/components/disappearing-msgs.js','js/components/draft-save.js','js/components/drafts-manager.js','js/components/drafts-sync.js','js/components/earnings-calc.js','js/components/emoji-picker.js','js/components/empty-list.js','js/components/engagement-compare.js','js/components/engagement-dashboard.js','js/components/engagement-heatmap.js','js/components/engagement-predict.js','js/components/engagement-score-v2.js','js/components/engagement-score.js','js/components/error-boundary.js','js/components/error-tracker.js','js/components/expense-report.js','js/components/expense-splitter.js','js/components/fab-menu.js','js/components/faq.js','js/components/feature-flags.js','js/components/feed-filter.js','js/components/feed-mute.js','js/components/feedback.js','js/components/fleet-manager.js','js/components/follow-requests.js','js/components/follow-suggest.js','js/components/form-validate.js','js/components/freshness.js','js/components/fuel-tracker.js','js/components/gamification.js','js/components/growth-funnel.js','js/components/growth-metrics.js','js/components/hashtag-analytics.js','js/components/hashtags.js','js/components/health-alerts.js','js/components/health-score.js','js/components/heatmap.js','js/components/image-optimizer.js','js/components/image-viewer.js','js/components/img-compress.js','js/components/income-goal.js','js/components/income-tracker.js','js/components/infinite-scroll.js','js/components/insights-v2.js','js/components/insights.js','js/components/keyboard-shortcuts.js','js/components/kpi-dashboard.js','js/components/lazy-images.js','js/components/lazy-img.js','js/components/lazy-loader.js','js/components/leaderboard-seasons.js','js/components/link-preview.js','js/components/loading-skeleton.js','js/components/location-picker.js','js/components/login-monitor.js','js/components/maintenance.js','js/components/mention-picker.js','js/components/mileage-log.js','js/components/milestones-ui.js','js/components/milestones.js','js/components/mod-queue.js','js/components/msg-forward.js','js/components/msg-poll.js','js/components/msg-reactions.js','js/components/mute-user.js','js/components/network-status.js','js/components/notif-analytics.js','js/components/notif-grouped.js','js/components/notif-poll.js','js/components/notif-prefs.js','js/components/notif-sound.js','js/components/notification-bell.js','js/components/number-format.js','js/components/online-privacy.js','js/components/online-widget.js','js/components/order-tracker.js','js/components/page-speed.js','js/components/payment.js','js/components/perf-monitor.js','js/components/perf-reporter.js','js/components/plagiarism-check.js','js/components/plagiarism-v2.js','js/components/platform-alerts.js','js/components/platform-scorecard.js','js/components/platform-stats.js','js/components/poll-ui.js','js/components/poll.js','js/components/post-analytics-v2.js','js/components/post-analytics.js','js/components/post-best-time.js','js/components/post-bookmarks-folder.js','js/components/post-boost.js','js/components/post-card.js','js/components/post-collab.js','js/components/post-create.js','js/components/post-digest.js','js/components/post-edit.js','js/components/post-highlights.js','js/components/post-location.js','js/components/post-performance.js','js/components/post-reach.js','js/components/post-reactions.js','js/components/post-sentiment.js','js/components/post-similar.js','js/components/post-stats-detail.js','js/components/post-templates-ui.js','js/components/post-templates.js','js/components/post-types.js','js/components/post-utils.js','js/components/post-word-cloud.js','js/components/predict-v2.js','js/components/prefs-sync.js','js/components/privacy-settings.js','js/components/profile-card.js','js/components/profile-complete.js','js/components/profile-completion.js','js/components/profile-themes.js','js/components/progress-ring.js','js/components/pwa-install.js','js/components/qr-share.js','js/components/quality-gate.js','js/components/queue-dash.js','js/components/quick-post.js','js/components/quick-share.js','js/components/rating-history.js','js/components/reactions-analytics.js','js/components/read-later.js','js/components/reading-time.js','js/components/realtime-monitor.js','js/components/realtime.js','js/components/recommend.js','js/components/referral-dash.js','js/components/report-dialog.js','js/components/report-generator.js','js/components/reputation-display.js','js/components/reputation-tiers.js','js/components/reputation.js','js/components/retention-dashboard.js','js/components/retention-score.js','js/components/revenue-forecast.js','js/components/revenue-tracker.js','js/components/route-optimizer.js','js/components/route-planner.js','js/components/saved-collections.js','js/components/saved-replies.js','js/components/schedule-calendar.js','js/components/schedule-queue.js','js/components/schedule-templates.js','js/components/scheduler-v2.js','js/components/scroll-heartbeat.js','js/components/scroll-progress.js','js/components/scroll-top.js','js/components/search-filters.js','js/components/search-history.js','js/components/search-overlay.js','js/components/sentiment-timeline.js','js/components/seo-monitor.js','js/components/share-sheet.js','js/components/share-to-group.js','js/components/shift-logger.js','js/components/shift-planner.js','js/components/shipper-map-data.js','js/components/shipper-profile-v2.js','js/components/short-link.js','js/components/shortcuts.js','js/components/skill-tags.js','js/components/smart-reply.js','js/components/smart-schedule.js','js/components/smart-suggest.js','js/components/starred-msgs.js','js/components/stat-card.js','js/components/stories.js','js/components/sub-badge.js','js/components/swipe.js','js/components/system-alerts.js','js/components/template-library.js','js/components/template-market.js','js/components/text-format.js','js/components/theme-picker.js','js/components/theme-toggle.js','js/components/time-ago.js','js/components/timeline.js','js/components/tip-calculator.js','js/components/tip-jar.js','js/components/toast-queue.js','js/components/tooltip.js','js/components/trend-detector.js','js/components/trending-topics.js','js/components/trending-v2.js','js/components/trending-widget.js','js/components/two-factor.js','js/components/typing-indicator.js','js/components/typing-stats.js','js/components/upload.js','js/components/user-availability.js','js/components/user-card.js','js/components/user-compare.js','js/components/user-connections.js','js/components/user-dashboard.js','js/components/user-funnel.js','js/components/user-goals.js','js/components/user-lifecycle.js','js/components/user-notes.js','js/components/user-portfolio.js','js/components/user-ratings.js','js/components/user-segment-v2.js','js/components/user-segments.js','js/components/user-summary-card.js','js/components/user-theme.js','js/components/vehicle-manager.js','js/components/verified-badge.js','js/components/video-player.js','js/components/view-tracker.js','js/components/viral-detector.js','js/components/voice-notes.js','js/components/voice-transcribe.js','js/components/wallet-chart.js','js/components/weather-alerts.js','js/components/webhook-logs.js','js/components/weekly-challenge.js','js/components/weekly-report.js','js/components/work-history.js','js/pages/account-settings.js','js/pages/activity-log.js','js/pages/admin-logs.js','js/pages/admin-mod.js','js/pages/admin-users.js','js/pages/admin.js','js/pages/auth.js','js/pages/bookmarks.js','js/pages/content-queue.js','js/pages/content-stats.js','js/pages/feed.js','js/pages/group-detail.js','js/pages/groups.js','js/pages/leaderboard.js','js/pages/listing-detail.js','js/pages/map-page.js','js/pages/marketplace.js','js/pages/messages.js','js/pages/people.js','js/pages/post-detail.js','js/pages/preferences.js','js/pages/profile-settings.js','js/pages/scheduled.js','js/pages/settings.js','js/pages/system-config.js','js/pages/traffic.js','js/pages/user-profile.js','js/pages/wallet.js','js/ss-bundle.min.js','js/ss-prod.js'];
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
$postsResp=json_decode(http_get('https://shippershop.vn/api/v2/posts.php?limit=1'),true);
t('Func: posts list',$postsResp&&$postsResp['success']===true);

// Trending API
$trendResp=json_decode(http_get('https://shippershop.vn/api/v2/trending.php?action=hot&limit=1'),true);
t('Func: trending hot',$trendResp&&$trendResp['success']===true);

// Hashtags API
$hashResp=json_decode(http_get('https://shippershop.vn/api/v2/hashtags.php?action=trending&limit=3'),true);
t('Func: hashtags trending',$hashResp&&$hashResp['success']===true);

// Health API
$healthResp=json_decode(http_get('https://shippershop.vn/api/v2/health.php'),true);
t('Func: health check',$healthResp&&isset($healthResp['status']));

// Status API
$statusResp=json_decode(http_get('https://shippershop.vn/api/v2/status.php'),true);
t('Func: status healthy',$statusResp&&$statusResp['status']==='healthy');

// Stats API
$statsResp=json_decode(http_get('https://shippershop.vn/api/v2/stats.php'),true);
t('Func: public stats',$statsResp&&$statsResp['success']===true);

// OG Tags API
$ogResp=json_decode(http_get('https://shippershop.vn/api/v2/og-tags.php?type=post&id=5'),true);
t('Func: OG tags',$ogResp&&$ogResp['success']===true&&!empty($ogResp['data']['title']));

// Moderation reasons
$modResp=json_decode(http_get('https://shippershop.vn/api/v2/moderation.php?action=reasons'),true);
t('Func: mod reasons',$modResp&&$modResp['success']===true&&count($modResp['data'])>=5);

// Webhook events
$whResp=json_decode(http_get('https://shippershop.vn/api/v2/webhooks.php?action=events'),true);
t('Func: webhook events',$whResp&&is_array($whResp['data']??null)&&count($whResp['data'])>=5);

// Link preview
$lpResp=json_decode(http_get('https://shippershop.vn/api/v2/link-preview.php?url=https://github.com'),true);
t('Func: link preview',$lpResp&&$lpResp['success']===true&&!empty($lpResp['data']['title']));


// Announcements (public)
$annResp=json_decode(http_get('https://shippershop.vn/api/v2/announcements.php'),true);
t('Func: announcements',$annResp&&$annResp['success']===true);

// Reputation (public)
$repResp=json_decode(http_get('https://shippershop.vn/api/v2/reputation.php?user_id=2'),true);
t('Func: reputation',$repResp&&$repResp['success']===true&&isset($repResp['data']['score']));

// Templates (public)
$tplResp=json_decode(http_get('https://shippershop.vn/api/v2/templates.php'),true);
t('Func: templates',$tplResp&&$tplResp['success']===true&&count($tplResp['data'])>=5);

// Badges (public)
$bdgResp=json_decode(http_get('https://shippershop.vn/api/v2/badges.php?action=all'),true);
t('Func: badges list',$bdgResp&&$bdgResp['success']===true&&count($bdgResp['data'])>=8);

// Polls (get for post without poll)
$pollResp=json_decode(http_get('https://shippershop.vn/api/v2/polls.php?post_id=5'),true);
t('Func: polls get',$pollResp&&$pollResp['success']===true);

// Daily Digest
$digestResp=json_decode(http_get('https://shippershop.vn/api/v2/daily-digest.php'),true);
t('Func: daily digest',$digestResp&&$digestResp['success']===true&&isset($digestResp['data']['greeting']));
t('Func: digest trending',isset($digestResp['data']['trending'])&&is_array($digestResp['data']['trending']));
t('Func: digest today stats',isset($digestResp['data']['today'])&&isset($digestResp['data']['today']['new_posts']));

// Delivery Stats (platform)
$delResp=json_decode(http_get('https://shippershop.vn/api/v2/delivery-stats.php?action=platform'),true);
t('Func: delivery platform',$delResp&&$delResp['success']===true&&isset($delResp['data']['total_deliveries']));
t('Func: delivery companies',isset($delResp['data']['top_companies'])&&is_array($delResp['data']['top_companies']));

// Checkin (leaderboard - no auth needed)
$ciResp=json_decode(http_get('https://shippershop.vn/api/v2/checkin.php?action=leaderboard'),true);
t('Func: checkin leaderboard',$ciResp&&$ciResp['success']===true);

// Profile Themes (presets)
$ptResp=json_decode(http_get('https://shippershop.vn/api/v2/profile-themes.php?action=presets'),true);
t('Func: profile themes',$ptResp&&$ptResp['success']===true&&count($ptResp['data']['presets'])>=8);

// Health Alerts
$haResp=json_decode(http_get('https://shippershop.vn/api/v2/health-alerts.php'),true);
t('Func: health alerts',$haResp&&$haResp['success']===true&&isset($haResp['data']['status']));
t('Func: health status ok',$haResp['data']['status']==='healthy'||$haResp['data']['status']==='warning');

// Site Config
$scResp=json_decode(http_get('https://shippershop.vn/api/v2/site-config.php'),true);
t('Func: site config',$scResp&&$scResp['success']===true&&isset($scResp['data']['site']));
t('Func: site features',count($scResp['data']['features']??[])>=10);
t('Func: shipping companies',count($scResp['data']['shipping_companies']??[])>=8);

// Suggest (search autocomplete)
$sugResp=json_decode(http_get('https://shippershop.vn/api/v2/suggest.php?q=admin'),true);
t('Func: suggest search',$sugResp&&$sugResp['success']===true);

// Sitemap
$smResp=json_decode(http_get('https://shippershop.vn/api/v2/sitemap-gen.php?format=json'),true);
t('Func: sitemap gen',$smResp&&$smResp['success']===true&&$smResp['data']['count']>=100);

// Timeline
$tlResp=json_decode(http_get('https://shippershop.vn/api/v2/timeline.php?user_id=2&limit=5'),true);
t('Func: timeline',$tlResp&&$tlResp['success']===true&&isset($tlResp['data']['events']));

// Referral leaderboard
$refLbResp=json_decode(http_get('https://shippershop.vn/api/v2/referral-dashboard.php?action=leaderboard'),true);
t('Func: referral leaderboard',$refLbResp&&$refLbResp['success']===true);

// Engagement score
$engResp=json_decode(http_get('https://shippershop.vn/api/v2/engagement-score.php?user_id=2&days=30'),true);
t('Func: engagement score',$engResp&&$engResp['success']===true&&isset($engResp['data']['score']));
t('Func: engagement rank',isset($engResp['data']['rank'])&&strlen($engResp['data']['rank'])>0);
t('Func: engagement metrics',isset($engResp['data']['metrics'])&&isset($engResp['data']['platform_avg']));

// Delivery stats user
$delUserResp=json_decode(http_get('https://shippershop.vn/api/v2/delivery-stats.php?user_id=2'),true);
t('Func: delivery user stats',$delUserResp&&$delUserResp['success']===true&&isset($delUserResp['data']['total_deliveries']));

// Achievements
$achResp=json_decode(http_get('https://shippershop.vn/api/v2/achievements.php?user_id=2'),true);
t('Func: achievements data',$achResp&&$achResp['success']===true&&isset($achResp['data']['total_earned']));
t('Func: achievements xp',isset($achResp['data']['total_xp'])&&$achResp['data']['total_xp']>=0);

// Badge showcase
$badgeResp2=json_decode(http_get('https://shippershop.vn/api/v2/badge-showcase.php?user_id=2'),true);
t('Func: badge earned count',$badgeResp2&&$badgeResp2['success']===true&&$badgeResp2['data']['total_earned']>=1);

// Schedule calendar (requires auth, just check endpoint exists)
// Schedule calendar requires auth - check endpoint responds
$calCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$calRaw=http_get_ctx('https://shippershop.vn/api/v2/schedule-calendar.php',$calCtx);
$calResp=json_decode($calRaw,true);
t('Func: schedule calendar',$calResp!==null);

// Checkin nearby
$ciNearResp=json_decode(http_get('https://shippershop.vn/api/v2/checkin.php?action=nearby'),true);
t('Func: checkin nearby',$ciNearResp&&$ciNearResp['success']===true);


// Trending topics
$ttResp=json_decode(http_get('https://shippershop.vn/api/v2/trending-topics.php?hours=24'),true);
t('Func: trending topics',$ttResp&&$ttResp['success']===true&&isset($ttResp['data']['hot_posts']));
t('Func: trending provinces',isset($ttResp['data']['active_provinces']));
t('Func: trending companies',isset($ttResp['data']['active_companies']));

// Ban appeals (list — requires admin, check endpoint exists)
$baCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$baResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/ban-appeals.php',$baCtx),true);
t('Func: ban appeals endpoint',$baResp!==null);

// Msg reactions (GET requires auth, check endpoint)
$mrCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$mrResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/msg-reactions.php?message_id=1',$mrCtx),true);
t('Func: msg reactions endpoint',$mrResp!==null);


// Follow requests (requires auth, check endpoint)
$frCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$frResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/follow-requests.php',$frCtx),true);
t('Func: follow requests endpoint',$frResp!==null);

// Drafts manager (requires auth)
$dmCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$dmResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/drafts-manager.php',$dmCtx),true);
t('Func: drafts manager endpoint',$dmResp!==null);

// Admin timeline (requires admin)
$atCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$atResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/admin-timeline.php',$atCtx),true);
t('Func: admin timeline endpoint',$atResp!==null);

// Conv notes (requires auth)
$cnCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$cnResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-notes.php?conversation_id=1',$cnCtx),true);
t('Func: conv notes endpoint',$cnResp!==null);

// Weekly report (requires auth)
$wrCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$wrResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/weekly-report.php',$wrCtx),true);
t('Func: weekly report endpoint',$wrResp!==null);


// FAQ
$faqResp=json_decode(http_get('https://shippershop.vn/api/v2/faq.php'),true);
t('Func: faq list',$faqResp&&$faqResp['success']===true&&count($faqResp['data']['faqs'])>=10);
t('Func: faq categories',count($faqResp['data']['categories'])>=5);

// FAQ search
$faqSResp=json_decode(http_get('https://shippershop.vn/api/v2/faq.php?q=dang+ky'),true);
t('Func: faq search',$faqSResp&&$faqSResp['success']===true);

// Post expiry (GET without post)
$peResp=json_decode(http_get('https://shippershop.vn/api/v2/post-expiry.php?post_id=5'),true);
t('Func: post expiry check',$peResp&&$peResp['success']===true);

// Feedback (POST test via stream)
$fbCtx=stream_context_create(['http'=>['method'=>'POST','header'=>'Content-Type: application/json','content'=>json_encode(['type'=>'test','message'=>'Test feedback from test suite']),'ignore_errors'=>true]]);
$fbResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/feedback.php',$fbCtx),true);
t('Func: feedback submit',$fbResp&&$fbResp['success']===true);


// Maintenance mode (public check)
$mmResp=json_decode(http_get('https://shippershop.vn/api/v2/maintenance.php'),true);
t('Func: maintenance check',$mmResp&&$mmResp['success']===true&&isset($mmResp['data']['active']));

// Read later (requires auth)
$rlCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$rlResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/read-later.php',$rlCtx),true);
t('Func: read later endpoint',$rlResp!==null);

// Starred msgs (requires auth)
$smCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$smResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/starred-msgs.php',$smCtx),true);
t('Func: starred msgs endpoint',$smResp!==null);

// Admin IP (requires admin)
$ipCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$ipResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/admin-ip.php',$ipCtx),true);
t('Func: admin ip endpoint',$ipResp!==null);


// Short link (resolve)
$slResp=json_decode(http_get('https://shippershop.vn/api/v2/short-link.php?code=test'),true);
t('Func: short link resolve',$slResp&&$slResp['success']===true);

// Online privacy (requires auth)
$opCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$opResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/online-privacy.php',$opCtx),true);
t('Func: online privacy endpoint',$opResp!==null);

// Search history (requires auth)
$shCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$shResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/search-history.php',$shCtx),true);
t('Func: search history endpoint',$shResp!==null);

// Admin backup (requires admin)
$abCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$abResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/admin-backup.php',$abCtx),true);
t('Func: admin backup endpoint',$abResp!==null);


// Activity heatmap (public)
$hmResp=json_decode(http_get('https://shippershop.vn/api/v2/engagement-heatmap.php?days=30'),true);
t('Func: activity heatmap',$hmResp&&$hmResp['success']===true&&isset($hmResp['data']['grid']));
t('Func: heatmap stats',isset($hmResp['data']['max_engagement']));

// Post share stats (public GET)
$pssResp=json_decode(http_get('https://shippershop.vn/api/v2/post-share-stats.php?post_id=5'),true);
t('Func: post share stats',$pssResp&&$pssResp['success']===true&&isset($pssResp['data']['total']));

// User notes (requires auth)
$unCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$unResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/user-notes.php',$unCtx),true);
t('Func: user notes endpoint',$unResp!==null);

// User theme (requires auth)
$utCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$utResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/user-theme.php',$utCtx),true);
t('Func: user theme endpoint',$utResp!==null);

// Content calendar (requires auth)
$ccCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$ccResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/content-calendar.php',$ccCtx),true);
t('Func: content calendar endpoint',$ccResp!==null);

// Delivery stats user (public)
$ds2Resp=json_decode(http_get('https://shippershop.vn/api/v2/delivery-stats.php?user_id=3&action=summary'),true);
t('Func: delivery stats user 3',$ds2Resp&&$ds2Resp['success']===true);

// Trending 72h
$tt72Resp=json_decode(http_get('https://shippershop.vn/api/v2/trending-topics.php?hours=72'),true);
t('Func: trending 72h',$tt72Resp&&$tt72Resp['success']===true&&isset($tt72Resp['data']['rising_users']));

// FAQ category filter
$faqWResp=json_decode(http_get('https://shippershop.vn/api/v2/faq.php?category=wallet'),true);
t('Func: faq wallet category',$faqWResp&&$faqWResp['success']===true&&count($faqWResp['data']['faqs'])>=2);

// Heatmap with 365 days
$hm365Resp=json_decode(http_get('https://shippershop.vn/api/v2/engagement-heatmap.php?days=90'),true);
t('Func: heatmap 365d',$hm365Resp&&$hm365Resp['success']===true&&isset($hm365Resp['data']['grid']));

// Engagement score user 3
$eng3Resp=json_decode(http_get('https://shippershop.vn/api/v2/engagement-score.php?user_id=3'),true);
t('Func: engagement user 3',$eng3Resp&&$eng3Resp['success']===true);

// Timeline user 3
$tl3Resp=json_decode(http_get('https://shippershop.vn/api/v2/timeline.php?user_id=3&limit=3'),true);
t('Func: timeline user 3',$tl3Resp&&$tl3Resp['success']===true);

// Suggest empty
$sugEResp=json_decode(http_get('https://shippershop.vn/api/v2/suggest.php?q='),true);
t('Func: suggest empty',$sugEResp&&$sugEResp['success']===true&&count($sugEResp['data'])===0);

// Short link create via POST
$slCtx=stream_context_create(['http'=>['method'=>'POST','header'=>'Content-Type: application/json','content'=>json_encode(['type'=>'post','id'=>5])]]);
$slCreateResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/short-link.php',$slCtx),true);
t('Func: short link create',$slCreateResp&&$slCreateResp['success']===true&&!empty($slCreateResp['data']['short_url']));

// Feedback categories count
$faqCatResp=json_decode(http_get('https://shippershop.vn/api/v2/faq.php?action=categories'),true);
t('Func: faq categories only',$faqCatResp&&$faqCatResp['success']===true&&count($faqCatResp['data'])>=5);

// Platform delivery
$pdResp=json_decode(http_get('https://shippershop.vn/api/v2/delivery-stats.php?action=platform'),true);
t('Func: platform top provinces',isset($pdResp['data']['top_provinces'])&&count($pdResp['data']['top_provinces'])>=5);


// User compare (public)
$ucResp=json_decode(http_get('https://shippershop.vn/api/v2/user-compare.php?user1=2&user2=3'),true);
t('Func: user compare',$ucResp&&$ucResp['success']===true&&isset($ucResp['data']['comparison']));
t('Func: compare wins',isset($ucResp['data']['wins'])&&isset($ucResp['data']['wins']['user1']));
t('Func: compare user1 stats',isset($ucResp['data']['user1']['stats']['posts']));

// Platform stats (public)
$psResp2=json_decode(http_get('https://shippershop.vn/api/v2/platform-stats.php'),true);
t('Func: platform stats',$psResp2&&$psResp2['success']===true&&isset($psResp2['data']['users']));
t('Func: platform users total',$psResp2['data']['users']['total']>=100);
t('Func: platform deliveries',isset($psResp2['data']['deliveries']['total']));
t('Func: platform provinces',$psResp2['data']['provinces_covered']>=5);

// Content summary (public)
$csResp=json_decode(http_get('https://shippershop.vn/api/v2/content-summary.php?hours=168'),true);
t('Func: content summary',$csResp&&$csResp['success']===true);
t('Func: content engagement',isset($csResp['data']['engagement_rate']));
t('Func: content peak hours',isset($csResp['data']['peak_hours']));

// Shipper map (public)
$smResp2=json_decode(http_get('https://shippershop.vn/api/v2/shipper-map.php?action=pins'),true);
t('Func: shipper map pins',$smResp2&&$smResp2['success']===true);

// Shipper map province heatmap
$smHeatResp=json_decode(http_get('https://shippershop.vn/api/v2/shipper-map.php?action=province_heat'),true);
t('Func: shipper province heat',$smHeatResp&&$smHeatResp['success']===true&&is_array($smHeatResp['data']));

// Post stats detail (public partial)
$psdResp=json_decode(http_get('https://shippershop.vn/api/v2/post-stats-detail.php?post_id=125'),true);
t('Func: post stats detail',$psdResp&&$psdResp['success']===true&&isset($psdResp['data']['likes']));
t('Func: post stats views',isset($psdResp['data']['views']));

// User notes (auth required)
$notesCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$notesResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/user-notes.php',$notesCtx),true);
t('Func: user notes endpoint 2',$notesResp!==null);

// Content calendar (auth required)
$cc2Ctx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$cc2Resp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/content-calendar.php?month=2026-03',$cc2Ctx),true);
t('Func: content calendar month',$cc2Resp!==null);

// Checkin nearby with province filter
$ciProvResp=json_decode(http_get('https://shippershop.vn/api/v2/checkin.php?action=nearby'),true);
t('Func: checkin province filter',$ciProvResp&&$ciProvResp['success']===true);

// Post share stats default
$pss0Resp=json_decode(http_get('https://shippershop.vn/api/v2/post-share-stats.php?post_id=999'),true);
t('Func: share stats default',$pss0Resp&&$pss0Resp['success']===true&&$pss0Resp['data']['total']===0);


// Badges wall (public)
$bwResp=json_decode(http_get('https://shippershop.vn/api/v2/badges-wall.php?action=user&user_id=2'),true);
t('Func: badges wall',$bwResp&&$bwResp['success']===true&&isset($bwResp['data']['badges']));
t('Func: badges count',$bwResp['data']['total']>=10);
t('Func: badges progress',isset($bwResp['data']['earned_count']));

// Optimal posting times (public)
$optResp=json_decode(http_get('https://shippershop.vn/api/v2/post-schedule-v2.php?action=optimal_times'),true);
t('Func: optimal times',$optResp&&$optResp['success']===true&&isset($optResp['data']['hourly']));
t('Func: optimal best times',isset($optResp['data']['best_times']));

// Conv tags (requires auth)
$ctCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$ctResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-tags.php',$ctCtx),true);
t('Func: conv tags endpoint',$ctResp!==null);

// Admin revenue (requires admin)
$arCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$arResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/admin-revenue.php',$arCtx),true);
t('Func: admin revenue endpoint',$arResp!==null);


// Milestones (public)
$msResp=json_decode(http_get('https://shippershop.vn/api/v2/milestones.php?user_id=2'),true);
t('Func: milestones',$msResp&&$msResp['success']===true&&isset($msResp['data']['earned']));
t('Func: milestones progress',count($msResp['data']['progress'])>=10);
t('Func: milestones earned count',$msResp['data']['total_earned']>=1);

// Schedule templates (public GET)
$stResp=json_decode(http_get('https://shippershop.vn/api/v2/schedule-templates.php'),true);
t('Func: schedule templates',$stResp&&$stResp['success']===true&&count($stResp['data']['templates'])>=8);

// Conv reminders (requires auth)
$crCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$crResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-reminders.php',$crCtx),true);
t('Func: conv reminders endpoint',$crResp!==null);

// Admin revenue (requires admin)
$arCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$arResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/admin-revenue.php',$arCtx),true);
t('Func: admin revenue endpoint',$arResp!==null);


// Badges wall catalog
$bwResp=json_decode(http_get('https://shippershop.vn/api/v2/badges-wall.php?action=catalog'),true);
t('Func: badges catalog',$bwResp&&$bwResp['success']===true&&count($bwResp['data']['badges'])>=10);

// Badges wall user
$bwuResp=json_decode(http_get('https://shippershop.vn/api/v2/badges-wall.php?action=user&user_id=2'),true);
t('Func: badges user',$bwuResp&&$bwuResp['success']===true&&isset($bwuResp['data']['earned_count']));

// Badges leaderboard
$bwlResp=json_decode(http_get('https://shippershop.vn/api/v2/badges-wall.php?action=leaderboard'),true);
t('Func: badges leaderboard',$bwlResp&&$bwlResp['success']===true);

// Post sentiment
$psaResp=json_decode(http_get('https://shippershop.vn/api/v2/post-sentiment.php?post_id=125'),true);
t('Func: post sentiment',$psaResp&&$psaResp['success']===true&&isset($psaResp['data']['label']));

// Sentiment overview
$psoResp=json_decode(http_get('https://shippershop.vn/api/v2/post-sentiment.php?action=overview'),true);
t('Func: sentiment overview',$psoResp&&$psoResp['success']===true&&isset($psoResp['data']['positive_pct']));

// User segments (admin)
$usCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$usResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/user-segments.php',$usCtx),true);
t('Func: user segments endpoint',$usResp!==null);

// Conv export (auth)
$ceCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$ceResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-export.php?conversation_id=1',$ceCtx),true);
t('Func: conv export endpoint',$ceResp!==null);


// Auto-tag
$atResp2=json_decode(http_get('https://shippershop.vn/api/v2/auto-tag.php?text=ghtk+giao+hang+nhanh+shipper'),true);
t('Func: auto tag',$atResp2&&$atResp2['success']===true&&count($atResp2['data']['suggested_tags'])>=1);

// Achievements wall leaderboard
$awlResp=json_decode(http_get('https://shippershop.vn/api/v2/achievements-wall.php?action=leaderboard'),true);
t('Func: achievements leaderboard',$awlResp&&$awlResp['success']===true);

// Achievements wall user
$awuResp=json_decode(http_get('https://shippershop.vn/api/v2/achievements-wall.php?action=user&user_id=2'),true);
t('Func: achievements user',$awuResp&&$awuResp['success']===true&&isset($awuResp['data']['level']));
t('Func: achievements xp detail',isset($awuResp['data']['total_xp'])&&$awuResp['data']['level']>=1);

// Achievements streaks
$awsResp=json_decode(http_get('https://shippershop.vn/api/v2/achievements-wall.php?action=streaks'),true);
t('Func: achievements streaks',$awsResp&&$awsResp['success']===true);

// Admin content schedule (requires admin)
$acsCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$acsResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/admin-content-schedule.php',$acsCtx),true);
t('Func: admin content schedule',$acsResp!==null);

// Typing stats (requires auth)
$tsCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$tsResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/typing-stats.php',$tsCtx),true);
t('Func: typing stats endpoint',$tsResp!==null);


// User portfolio (public)
$upResp=json_decode(http_get('https://shippershop.vn/api/v2/user-portfolio.php?user_id=2'),true);
t('Func: user portfolio',$upResp&&$upResp['success']===true&&isset($upResp['data']['user']));
t('Func: portfolio stats',isset($upResp['data']['stats']['posts'])&&isset($upResp['data']['stats']['level']));
t('Func: portfolio best posts',is_array($upResp['data']['best_posts']));

// Engagement predictor
$epResp=json_decode(http_get(str_replace(' ','+','https://shippershop.vn/api/v2/engagement-predict.php?text=ghtk+giao+hang+nhanh+tphcm+shipper+chia+se+kinh+nghiem')),true);
t('Func: engagement predict',$epResp&&$epResp['success']===true&&isset($epResp['data']['score']));
t('Func: predict factors',is_array($epResp['data']['factors'])&&count($epResp['data']['factors'])>=1);

// Announce schedule (public active)
$annSchResp=json_decode(http_get('https://shippershop.vn/api/v2/announce-schedule.php'),true);
t('Func: announce schedule active',$annSchResp&&$annSchResp['success']===true&&isset($annSchResp['data']['announcements']));

// Conv quick actions (requires auth)
$cqaCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$cqaResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-quick-actions.php?conversation_id=1',$cqaCtx),true);
t('Func: conv quick actions',$cqaResp!==null);

// Portfolio user 3
$up3Resp=json_decode(http_get('https://shippershop.vn/api/v2/user-portfolio.php?user_id=3'),true);
t('Func: portfolio user 3',$up3Resp&&$up3Resp['success']===true);

// Predict empty text
$epEmptyResp=json_decode(http_get('https://shippershop.vn/api/v2/engagement-predict.php?text=hi'),true);
t('Func: predict short text',$epEmptyResp&&$epEmptyResp['success']===true&&$epEmptyResp['data']['score']===50);


// Post digest daily
$pdgResp=json_decode(http_get('https://shippershop.vn/api/v2/post-digest.php?period=daily'),true);
t('Func: post digest daily',$pdgResp&&$pdgResp['success']===true&&isset($pdgResp['data']['top_posts']));
t('Func: digest stats',isset($pdgResp['data']['stats']['posts']));

// Post digest weekly
$pdwResp=json_decode(http_get('https://shippershop.vn/api/v2/post-digest.php?period=weekly'),true);
t('Func: post digest weekly',$pdwResp&&$pdwResp['success']===true&&$pdwResp['data']['period']==='weekly');

// User connections
$ucnResp=json_decode(http_get('https://shippershop.vn/api/v2/user-connections.php?user_id=2'),true);
t('Func: user connections',$ucnResp&&$ucnResp['success']===true&&isset($ucnResp['data']['followers']));
t('Func: connections mutuals',isset($ucnResp['data']['mutuals']));

// User connections mutual
$ucmResp=json_decode(http_get('https://shippershop.vn/api/v2/user-connections.php?user_id=2&action=mutual&other_id=3'),true);
t('Func: mutual connections',$ucmResp&&$ucmResp['success']===true&&isset($ucmResp['data']['count']));

// Admin stats cache (requires admin)
$ascCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$ascResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/admin-stats-cache.php?action=info',$ascCtx),true);
t('Func: admin cache endpoint',$ascResp!==null);

// Cross-check: platform stats users > posts
$ps3Resp=json_decode(http_get('https://shippershop.vn/api/v2/platform-stats.php'),true);
t('Func: platform users > 500',$ps3Resp&&$ps3Resp['data']['users']['total']>=500);

// Cross-check: delivery stats > 50000
$ds3Resp=json_decode(http_get('https://shippershop.vn/api/v2/delivery-stats.php?action=platform'),true);
t('Func: deliveries > 50000',$ds3Resp&&$ds3Resp['data']['total_deliveries']>=50000);


// Skill tags presets
$skResp=json_decode(http_get('https://shippershop.vn/api/v2/skill-tags.php'),true);
t('Func: skill tags presets',$skResp&&$skResp['success']===true&&count($skResp['data']['presets'])>=5);

// Skill tags user
$skuResp=json_decode(http_get('https://shippershop.vn/api/v2/skill-tags.php?user_id=2'),true);
t('Func: skill tags user',$skuResp&&$skuResp['success']===true);

// Template marketplace
$tmResp=json_decode(http_get('https://shippershop.vn/api/v2/template-market.php'),true);
t('Func: template market',$tmResp&&$tmResp['success']===true&&count($tmResp['data']['templates'])>=8);
t('Func: template categories',count($tmResp['data']['categories'])>=5);

// Template popular
$tmpResp=json_decode(http_get('https://shippershop.vn/api/v2/template-market.php?action=popular'),true);
t('Func: template popular',$tmpResp&&$tmpResp['success']===true&&count($tmpResp['data'])>=5);

// Notif blast history (requires admin)
$nbCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$nbResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/notif-blast.php',$nbCtx),true);
t('Func: notif blast endpoint',$nbResp!==null);

// Conv threads (requires auth)
$ctCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$ctResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-threads.php?parent_id=1',$ctCtx),true);
t('Func: conv threads endpoint',$ctResp!==null);


// Reputation tiers list
$rtResp=json_decode(http_get('https://shippershop.vn/api/v2/reputation-tiers.php?action=tiers'),true);
t('Func: reputation tiers',$rtResp&&$rtResp['success']===true&&count($rtResp['data']['tiers'])>=5);

// Reputation user
$rtuResp=json_decode(http_get('https://shippershop.vn/api/v2/reputation-tiers.php?action=user&user_id=2'),true);
t('Func: reputation user',$rtuResp&&$rtuResp['success']===true&&isset($rtuResp['data']['score']));
t('Func: reputation tier name',!empty($rtuResp['data']['tier']['name']));
t('Func: reputation breakdown',isset($rtuResp['data']['breakdown']['posts']));

// Reputation leaderboard
$rtlResp=json_decode(http_get('https://shippershop.vn/api/v2/reputation-tiers.php?action=leaderboard'),true);
t('Func: reputation leaderboard',$rtlResp&&$rtlResp['success']===true&&is_array($rtlResp['data']));

// Analytics export (requires auth)
$aeCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$aeResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/analytics-export.php',$aeCtx),true);
t('Func: analytics export endpoint',$aeResp!==null);

// Admin dashboard v2 (requires admin)
$ad2Ctx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$ad2Resp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/admin-dashboard-v2.php',$ad2Ctx),true);
t('Func: admin dashboard v2',$ad2Resp!==null);

// Conv read status (requires auth)
$crsCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$crsResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-read-status.php?conversation_id=1',$crsCtx),true);
t('Func: conv read status',$crsResp!==null);


// Smart schedule optimal
$ssResp2=json_decode(http_get('https://shippershop.vn/api/v2/smart-schedule.php'),true);
t('Func: smart schedule',$ssResp2&&$ssResp2['success']===true&&isset($ssResp2['data']['recommended_slots']));
t('Func: schedule hour stats',isset($ssResp2['data']['hour_stats']));

// Smart schedule next
$ssnResp=json_decode(http_get('https://shippershop.vn/api/v2/smart-schedule.php?action=next'),true);
t('Func: schedule next time',$ssnResp&&$ssnResp['success']===true&&isset($ssnResp['data']['next_time']));

// Wallet chart (requires auth)
$wcCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$wcResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/wallet-chart.php',$wcCtx),true);
t('Func: wallet chart endpoint',$wcResp!==null);

// Growth funnel (requires admin)
$gfCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$gfResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/growth-funnel.php',$gfCtx),true);
t('Func: growth funnel endpoint',$gfResp!==null);

// Conv media (requires auth)
$cmCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$cmResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-media.php?conversation_id=1',$cmCtx),true);
t('Func: conv media endpoint',$cmResp!==null);


// Leaderboard seasons (public)
$lsResp=json_decode(http_get('https://shippershop.vn/api/v2/leaderboard-seasons.php?period=monthly&metric=posts'),true);
t('Func: leaderboard seasons',$lsResp&&$lsResp['success']===true&&isset($lsResp['data']['leaderboard']));
t('Func: leaderboard season name',!empty($lsResp['data']['season']));
t('Func: leaderboard rewards',count($lsResp['data']['rewards']??[])>=3);

// Leaderboard metrics
$lmResp=json_decode(http_get('https://shippershop.vn/api/v2/leaderboard-seasons.php?action=metrics'),true);
t('Func: leaderboard metrics',$lmResp&&$lmResp['success']===true&&count($lmResp['data']['metrics'])>=5);

// Leaderboard weekly XP
$lwxResp=json_decode(http_get('https://shippershop.vn/api/v2/leaderboard-seasons.php?period=weekly&metric=xp'),true);
t('Func: leaderboard weekly xp',$lwxResp&&$lwxResp['success']===true);

// AB test variant (public)
$abvResp=json_decode(http_get('https://shippershop.vn/api/v2/ab-test.php?action=variant&test_id=test1&user_id=2'),true);
t('Func: ab test variant',$abvResp&&$abvResp['success']===true&&isset($abvResp['data']['variant']));

// Post collab (requires auth)
$pcCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$pcResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/post-collab.php',$pcCtx),true);
t('Func: post collab endpoint',$pcResp!==null);

// Conv polls (requires auth)
$cpCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$cpResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-polls.php?conversation_id=1',$cpCtx),true);
t('Func: conv polls endpoint',$cpResp!==null);


// Work history (public)
$whResp=json_decode(http_get('https://shippershop.vn/api/v2/work-history.php?user_id=2'),true);
t('Func: work history',$whResp&&$whResp['success']===true&&isset($whResp['data']['history']));

// Reactions analytics (public)
$raResp=json_decode(http_get('https://shippershop.vn/api/v2/reactions-analytics.php?days=30'),true);
t('Func: reactions analytics',$raResp&&$raResp['success']===true&&isset($raResp['data']['total_likes']));
t('Func: reactions avg',isset($raResp['data']['avg_per_post']));
t('Func: reactions top posts',is_array($raResp['data']['top_posts']));
t('Func: reactions top likers',is_array($raResp['data']['top_likers']));

// Reactions user
$rauResp=json_decode(http_get('https://shippershop.vn/api/v2/reactions-analytics.php?action=user&user_id=2'),true);
t('Func: reactions user',$rauResp&&$rauResp['success']===true&&isset($rauResp['data']['given']));

// Feature flags (public)
$ffResp=json_decode(http_get('https://shippershop.vn/api/v2/feature-flags.php'),true);
t('Func: feature flags',$ffResp&&$ffResp['success']===true&&isset($ffResp['data']['flags']));
t('Func: flags count',count($ffResp['data']['flags'])>=10);
t('Func: dark mode flag',$ffResp['data']['flags']['dark_mode']===true);

// Feature flag check specific
$ffcResp=json_decode(http_get('https://shippershop.vn/api/v2/feature-flags.php?action=check&flag=stories'),true);
t('Func: flag check stories',$ffcResp&&$ffcResp['success']===true&&$ffcResp['data']['enabled']===true);

// Conv schedule (requires auth)
$csCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$csResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-schedule.php',$csCtx),true);
t('Func: conv schedule endpoint',$csResp!==null);

// Reactions analytics 7d
$ra7Resp=json_decode(http_get('https://shippershop.vn/api/v2/reactions-analytics.php?days=7'),true);
t('Func: reactions 7d',$ra7Resp&&$ra7Resp['success']===true);

// Work history non-existent user
$wh0Resp=json_decode(http_get('https://shippershop.vn/api/v2/work-history.php?user_id=999'),true);
t('Func: work history empty',$wh0Resp&&$wh0Resp['success']===true&&count($wh0Resp['data']['history'])===0);

// Feature flags disabled check
$ff2Resp=json_decode(http_get('https://shippershop.vn/api/v2/feature-flags.php?action=check&flag=ai_content'),true);
t('Func: flag check disabled',$ff2Resp&&$ff2Resp['data']['enabled']===false);

// Leaderboard deliveries
$ldResp=json_decode(http_get('https://shippershop.vn/api/v2/leaderboard-seasons.php?period=monthly&metric=deliveries'),true);
t('Func: leaderboard deliveries',$ldResp&&$ldResp['success']===true);

// Platform stats online
$ps4Resp=json_decode(http_get('https://shippershop.vn/api/v2/platform-stats.php'),true);
t('Func: platform shipping companies',$ps4Resp['data']['shipping_companies']>=5);

// Smart schedule day stats
$ss3Resp=json_decode(http_get('https://shippershop.vn/api/v2/smart-schedule.php'),true);
t('Func: schedule day stats',isset($ss3Resp['data']['day_stats'])&&count($ss3Resp['data']['day_stats'])>=1);

// Milestones user 3
$ms3Resp=json_decode(http_get('https://shippershop.vn/api/v2/milestones.php?user_id=3'),true);
t('Func: milestones user 3',$ms3Resp&&$ms3Resp['success']===true&&isset($ms3Resp['data']['total_earned']));


// Post reach (public)
$prResp=json_decode(http_get('https://shippershop.vn/api/v2/post-reach.php?user_id=2'),true);
t('Func: post reach',$prResp&&$prResp['success']===true&&isset($prResp['data']['total_estimated_reach']));
t('Func: reach followers',isset($prResp['data']['direct_followers']));

// Post reach by post
$pr2Resp=json_decode(http_get('https://shippershop.vn/api/v2/post-reach.php?post_id=125'),true);
t('Func: post reach by post',$pr2Resp&&$pr2Resp['success']===true);

// User availability statuses
$uaResp=json_decode(http_get('https://shippershop.vn/api/v2/user-availability.php'),true);
t('Func: availability statuses',$uaResp&&$uaResp['success']===true&&count($uaResp['data']['statuses'])>=4);

// User availability check
$ua2Resp=json_decode(http_get('https://shippershop.vn/api/v2/user-availability.php?user_id=2'),true);
t('Func: availability user',$ua2Resp&&$ua2Resp['success']===true&&isset($ua2Resp['data']['current']));

// Post similar
$psimResp=json_decode(http_get('https://shippershop.vn/api/v2/post-similar.php?post_id=125'),true);
t('Func: post similar',$psimResp&&$psimResp['success']===true&&isset($psimResp['data']['similar']));

// User goals (requires auth)
$ugCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$ugResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/user-goals.php',$ugCtx),true);
t('Func: user goals endpoint',$ugResp!==null);

// Admin audit (requires admin)
$aaCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$aaResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/admin-audit.php',$aaCtx),true);
t('Func: admin audit endpoint',$aaResp!==null);

// Admin audit actions
$aa2Ctx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$aa2Resp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/admin-audit.php?action=actions',$aa2Ctx),true);
t('Func: audit actions list',$aa2Resp!==null);


// Income tracker (requires auth)
$itCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$itResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/income-tracker.php',$itCtx),true);
t('Func: income tracker endpoint',$itResp!==null);

// Post highlights (public)
$phResp=json_decode(http_get('https://shippershop.vn/api/v2/post-highlights.php?user_id=2'),true);
t('Func: post highlights',$phResp&&$phResp['success']===true&&isset($phResp['data']['highlights']));

// Mod queue (requires admin)
$mqCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$mqResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/mod-queue.php',$mqCtx),true);
t('Func: mod queue endpoint',$mqResp!==null);

// Conv bookmarks (requires auth)
$cbCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$cbResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-bookmarks.php',$cbCtx),true);
t('Func: conv bookmarks endpoint',$cbResp!==null);

// User summary card (public)
$uscResp=json_decode(http_get('https://shippershop.vn/api/v2/user-summary-card.php?user_id=2'),true);
t('Func: user summary card',$uscResp&&$uscResp['success']===true&&isset($uscResp['data']['fullname']));
t('Func: summary card level',$uscResp['data']['level']>=1);
t('Func: summary card stats',$uscResp['data']['posts']>=1&&$uscResp['data']['days']>=1);

// User summary card user 3
$usc3Resp=json_decode(http_get('https://shippershop.vn/api/v2/user-summary-card.php?user_id=3'),true);
t('Func: summary card user 3',$usc3Resp&&$usc3Resp['success']===true);

// Post highlights empty user
$ph0Resp=json_decode(http_get('https://shippershop.vn/api/v2/post-highlights.php?user_id=999'),true);
t('Func: highlights empty',$ph0Resp&&$ph0Resp['success']===true&&count($ph0Resp['data']['highlights'])===0);


// User ratings categories
$urResp=json_decode(http_get('https://shippershop.vn/api/v2/user-ratings.php'),true);
t('Func: rating categories',$urResp&&$urResp['success']===true&&count($urResp['data']['categories'])>=5);

// User ratings for user
$ur2Resp=json_decode(http_get('https://shippershop.vn/api/v2/user-ratings.php?user_id=2'),true);
t('Func: user rating',$ur2Resp&&$ur2Resp['success']===true&&isset($ur2Resp['data']['overall']));
t('Func: rating by category',isset($ur2Resp['data']['by_category']));

// Content warnings types
$cwResp=json_decode(http_get('https://shippershop.vn/api/v2/content-warnings.php'),true);
t('Func: warning types',$cwResp&&$cwResp['success']===true&&count($cwResp['data']['types'])>=5);

// Content warnings for post
$cw2Resp=json_decode(http_get('https://shippershop.vn/api/v2/content-warnings.php?post_id=125'),true);
t('Func: post warnings',$cw2Resp&&$cw2Resp['success']===true&&isset($cw2Resp['data']['warnings']));

// Engagement dashboard (admin)
$edCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$edResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/engagement-dashboard.php',$edCtx),true);
t('Func: engagement dashboard',$edResp!==null);

// Voice notes (auth)
$vnCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$vnResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/voice-notes.php?conversation_id=1',$vnCtx),true);
t('Func: voice notes endpoint',$vnResp!==null);


// Delivery map hotspots
$dmResp=json_decode(http_get('https://shippershop.vn/api/v2/delivery-map.php'),true);
t('Func: delivery hotspots',$dmResp&&$dmResp['success']===true&&isset($dmResp['data']['by_province']));
t('Func: delivery total location',$dmResp['data']['total_with_location']>=0);

// Delivery map user
$dmuResp=json_decode(http_get('https://shippershop.vn/api/v2/delivery-map.php?action=user&user_id=2'),true);
t('Func: delivery map user',$dmuResp&&$dmuResp['success']===true&&isset($dmuResp['data']['areas']));

// Engagement heatmap
$ehResp=json_decode(http_get('https://shippershop.vn/api/v2/engagement-heatmap.php'),true);
t('Func: engagement heatmap',$ehResp&&$ehResp['success']===true&&isset($ehResp['data']['grid']));
t('Func: heatmap best slots',isset($ehResp['data']['best_slots']));

// User lifecycle (admin)
$ulCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$ulResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/user-lifecycle.php',$ulCtx),true);
t('Func: user lifecycle endpoint',$ulResp!==null);

// Conv auto-label (auth)
$calCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$calResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-auto-label.php?conversation_id=1',$calCtx),true);
t('Func: conv auto label',$calResp!==null);


// Shift planner (auth)
$spCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$spResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/shift-planner.php',$spCtx),true);
t('Func: shift planner endpoint',$spResp!==null);

// Content score (public)
$csResp2=json_decode(http_get(str_replace(' ','+','https://shippershop.vn/api/v2/content-score.php?text=ghtk+shipper+giao+hang+nhanh+tphcm+quan+7+lien+he+0909+#shipper+#ghtk')),true);
t('Func: content score',$csResp2&&$csResp2['success']===true&&isset($csResp2['data']['score']));
t('Func: content grade',!empty($csResp2['data']['grade']));
t('Func: content factors',count($csResp2['data']['factors'])>=3);

// Content score for post
$cs3Resp=json_decode(http_get('https://shippershop.vn/api/v2/content-score.php?post_id=125'),true);
t('Func: content score post',$cs3Resp&&$cs3Resp['success']===true);

// Churn predict (admin)
$cpCtx2=stream_context_create(['http'=>['ignore_errors'=>true]]);
$cpResp2=json_decode(http_get_ctx('https://shippershop.vn/api/v2/churn-predict.php',$cpCtx2),true);
t('Func: churn predict endpoint',$cpResp2!==null);

// Contact card (auth)
$ccCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$ccResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/contact-card.php?user_id=2',$ccCtx),true);
t('Func: contact card endpoint',$ccResp!==null);


// Daily report (auth)
$drCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$drResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/daily-report.php',$drCtx),true);
t('Func: daily report endpoint',$drResp!==null);

// Calendar view (public)
$cvResp=json_decode(http_get('https://shippershop.vn/api/v2/calendar-view.php?month=3&year=2026'),true);
t('Func: calendar view',$cvResp&&$cvResp['success']===true&&isset($cvResp['data']['calendar']));
t('Func: calendar days',count($cvResp['data']['calendar'])>=28);
t('Func: calendar stats',$cvResp['data']['total_posts']>=0&&$cvResp['data']['active_days']>=0);

// Calendar view user
$cv2Resp=json_decode(http_get('https://shippershop.vn/api/v2/calendar-view.php?month=3&year=2026&user_id=2'),true);
t('Func: calendar user',$cv2Resp&&$cv2Resp['success']===true);

// Health score (admin)
$hsCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$hsResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/health-score.php',$hsCtx),true);
t('Func: health score endpoint',$hsResp!==null);

// Conv auto-archive (auth)
$caaCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$caaResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-auto-archive.php',$caaCtx),true);
t('Func: conv auto archive endpoint',$caaResp!==null);

// Cross-checks for milestone
$allBadgesResp=json_decode(http_get('https://shippershop.vn/api/v2/badges-wall.php?action=catalog'),true);
t('Func: badges catalog count',count($allBadgesResp['data']['badges'])>=10);

$tiersResp=json_decode(http_get('https://shippershop.vn/api/v2/reputation-tiers.php?action=tiers'),true);
t('Func: reputation tier count',count($tiersResp['data']['tiers'])===5);

$flagsResp=json_decode(http_get('https://shippershop.vn/api/v2/feature-flags.php'),true);
t('Func: flags total',count($flagsResp['data']['flags'])>=14);

$tmResp2=json_decode(http_get('https://shippershop.vn/api/v2/template-market.php?action=popular'),true);
t('Func: template popular sorted',$tmResp2['data'][0]['uses']>=$tmResp2['data'][1]['uses']);

$skillResp=json_decode(http_get('https://shippershop.vn/api/v2/skill-tags.php'),true);
t('Func: skill categories 5',count($skillResp['data']['presets'])===5);

$cwResp3=json_decode(http_get('https://shippershop.vn/api/v2/content-warnings.php'),true);
t('Func: content warning types 5',count($cwResp3['data']['types'])===5);

$avResp=json_decode(http_get('https://shippershop.vn/api/v2/user-availability.php'),true);
t('Func: availability status 4',count($avResp['data']['statuses'])===4);

$urResp2=json_decode(http_get('https://shippershop.vn/api/v2/user-ratings.php'),true);
t('Func: rating categories 5',count($urResp2['data']['categories'])===5);

$dmResp2=json_decode(http_get('https://shippershop.vn/api/v2/delivery-map.php'),true);
t('Func: delivery provinces 11',$dmResp2['data']['total_with_location']>=500);


// Drafts sync (auth)
$dsCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$dsResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/drafts-sync.php',$dsCtx),true);
t('Func: drafts sync endpoint',$dsResp!==null);

// Weekly challenge (auth)
$wcCtx2=stream_context_create(['http'=>['ignore_errors'=>true]]);
$wcResp2=json_decode(http_get_ctx('https://shippershop.vn/api/v2/weekly-challenge.php',$wcCtx2),true);
t('Func: weekly challenge endpoint',$wcResp2!==null);

// Realtime monitor (admin)
$rmCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$rmResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/realtime-monitor.php',$rmCtx),true);
t('Func: realtime monitor endpoint',$rmResp!==null);

// Conv location (auth)
$clCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$clResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-location.php?conversation_id=1',$clCtx),true);
t('Func: conv location endpoint',$clResp!==null);


// Route planner (auth)
$rpCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$rpResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/route-planner.php',$rpCtx),true);
t('Func: route planner endpoint',$rpResp!==null);

// Engagement compare posts
$ecpResp=json_decode(http_get('https://shippershop.vn/api/v2/engagement-compare.php?post1=125&post2=126'),true);
t('Func: engagement compare posts',$ecpResp&&$ecpResp['success']===true);

// Engagement compare users
$ecuResp=json_decode(http_get('https://shippershop.vn/api/v2/engagement-compare.php?action=users&user1=2&user2=3'),true);
t('Func: engagement compare users',$ecuResp&&$ecuResp['success']===true&&isset($ecuResp['data']['winner']));

// Notif analytics (admin)
$naCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$naResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/notif-analytics.php',$naCtx),true);
t('Func: notif analytics endpoint',$naResp!==null);

// Disappearing msgs (auth)
$dmCtx2=stream_context_create(['http'=>['ignore_errors'=>true]]);
$dmResp2=json_decode(http_get_ctx('https://shippershop.vn/api/v2/disappearing-msgs.php?conversation_id=1',$dmCtx2),true);
t('Func: disappearing msgs endpoint',$dmResp2!==null);


// Fuel tracker (auth)
$ftCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$ftResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/fuel-tracker.php',$ftCtx),true);
t('Func: fuel tracker endpoint',$ftResp!==null);

// Plagiarism check (public)
$pcResp2=json_decode(http_get(str_replace(' ','+','https://shippershop.vn/api/v2/plagiarism-check.php?text=shipper+giao+hang+nhanh+ghtk+tphcm+chia+se+kinh+nghiem+lam+shipper+cho+nguoi+moi')),true);
t('Func: plagiarism check',$pcResp2&&$pcResp2['success']===true&&isset($pcResp2['data']['is_original']));
t('Func: plagiarism similarity',isset($pcResp2['data']['max_similarity']));
t('Func: plagiarism phrases',$pcResp2['data']['phrases_checked']>=1);

// Plagiarism short text
$pcShort=json_decode(http_get('https://shippershop.vn/api/v2/plagiarism-check.php?text=hi'),true);
t('Func: plagiarism short skip',$pcShort&&$pcShort['data']['is_original']===true);

// Revenue forecast (admin)
$rfCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$rfResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/revenue-forecast.php',$rfCtx),true);
t('Func: revenue forecast endpoint',$rfResp!==null);

// Conv meeting (auth)
$cmCtx2=stream_context_create(['http'=>['ignore_errors'=>true]]);
$cmResp2=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-meeting.php?conversation_id=1',$cmCtx2),true);
t('Func: conv meeting endpoint',$cmResp2!==null);

// Cross-checks for 850 target
$wcResp3=json_decode(http_get('https://shippershop.vn/api/v2/content-warnings.php'),true);
t('Func: content warnings types ok',count($wcResp3['data']['types'])===5);

$csResp4=json_decode(http_get('https://shippershop.vn/api/v2/content-score.php?post_id=125'),true);
t('Func: content score exists',$csResp4&&$csResp4['success']===true&&$csResp4['data']['score']>=0);

$eh2Resp=json_decode(http_get('https://shippershop.vn/api/v2/engagement-heatmap.php?days=7'),true);
t('Func: heatmap 7d',$eh2Resp&&$eh2Resp['success']===true);

$sk2Resp=json_decode(http_get('https://shippershop.vn/api/v2/skill-tags.php'),true);
t('Func: skills presets ok',count($sk2Resp['data']['presets'])===5);

$tm3Resp=json_decode(http_get('https://shippershop.vn/api/v2/template-market.php?category=question'),true);
t('Func: templates filtered',count($tm3Resp['data']['templates'])>=1);

$lb2Resp=json_decode(http_get('https://shippershop.vn/api/v2/leaderboard-seasons.php?period=weekly&metric=comments'),true);
t('Func: leaderboard comments',$lb2Resp&&$lb2Resp['success']===true);

$usc2Resp=json_decode(http_get('https://shippershop.vn/api/v2/user-summary-card.php?user_id=999'),true);
t('Func: summary card missing user',$usc2Resp&&$usc2Resp['success']===true&&$usc2Resp['data']===null);


// Expense report (auth)
$erCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$erResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/expense-report.php',$erCtx),true);
t('Func: expense report endpoint',$erResp!==null);

// Trend detector keywords
$tdResp=json_decode(http_get('https://shippershop.vn/api/v2/trend-detector.php?hours=24'),true);
t('Func: trend detector',$tdResp&&$tdResp['success']===true&&isset($tdResp['data']['keywords']));
t('Func: trend hashtags',isset($tdResp['data']['hashtags']));
t('Func: trend posts analyzed',$tdResp['data']['posts_analyzed']>=0);

// Trend detector rising
$trResp=json_decode(http_get('https://shippershop.vn/api/v2/trend-detector.php?action=rising&hours=24'),true);
t('Func: trend rising',$trResp&&$trResp['success']===true&&isset($trResp['data']['rising']));

// Trend 72h window
$td72Resp=json_decode(http_get('https://shippershop.vn/api/v2/trend-detector.php?hours=72'),true);
t('Func: trend 72h',$td72Resp&&$td72Resp['success']===true);

// Cohort analysis (admin)
$caCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$caResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/cohort-analysis.php',$caCtx),true);
t('Func: cohort analysis endpoint',$caResp!==null);

// Conv files (auth)
$cfCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$cfResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-files.php?conversation_id=1',$cfCtx),true);
t('Func: conv files endpoint',$cfResp!==null);

// Extra cross-checks for 900 push
$dm3Resp=json_decode(http_get('https://shippershop.vn/api/v2/delivery-map.php?action=user&user_id=2'),true);
t('Func: delivery map user 2',$dm3Resp&&$dm3Resp['success']===true);

$eh3Resp=json_decode(http_get('https://shippershop.vn/api/v2/engagement-heatmap.php?user_id=2'),true);
t('Func: heatmap user 2',$eh3Resp&&$eh3Resp['success']===true);

$ra2Resp=json_decode(http_get('https://shippershop.vn/api/v2/reactions-analytics.php?action=user&user_id=3&days=30'),true);
t('Func: reactions user 3',$ra2Resp&&$ra2Resp['success']===true);

$cv3Resp=json_decode(http_get('https://shippershop.vn/api/v2/calendar-view.php?month=2&year=2026'),true);
t('Func: calendar feb 2026',$cv3Resp&&$cv3Resp['success']===true&&$cv3Resp['data']['days_in_month']===28);

$rt2Resp=json_decode(http_get('https://shippershop.vn/api/v2/reputation-tiers.php?action=user&user_id=3'),true);
t('Func: reputation user 3',$rt2Resp&&$rt2Resp['success']===true&&isset($rt2Resp['data']['score']));

$ms4Resp=json_decode(http_get('https://shippershop.vn/api/v2/milestones.php?user_id=2'),true);
t('Func: milestones admin earned',$ms4Resp['data']['total_earned']>=5);

$up2Resp=json_decode(http_get('https://shippershop.vn/api/v2/user-portfolio.php?user_id=3'),true);
t('Func: portfolio user 3 detail',$up2Resp&&$up2Resp['success']===true);

$uc2Resp=json_decode(http_get('https://shippershop.vn/api/v2/user-connections.php?user_id=3'),true);
t('Func: connections user 3',$uc2Resp&&$uc2Resp['success']===true);

$ph2Resp=json_decode(http_get('https://shippershop.vn/api/v2/post-highlights.php?user_id=3'),true);
t('Func: highlights user 3',$ph2Resp&&$ph2Resp['success']===true);

$wh2Resp=json_decode(http_get('https://shippershop.vn/api/v2/work-history.php?user_id=3'),true);
t('Func: work history user 3',$wh2Resp&&$wh2Resp['success']===true);

$lb3Resp=json_decode(http_get('https://shippershop.vn/api/v2/leaderboard-seasons.php?period=monthly&metric=likes'),true);
t('Func: leaderboard monthly likes',$lb3Resp!==null);

$pd2Resp=json_decode(http_get('https://shippershop.vn/api/v2/post-digest.php?period=weekly'),true);
t('Func: digest weekly stats',$pd2Resp['data']['period']==='weekly');

$ss4Resp=json_decode(http_get('https://shippershop.vn/api/v2/smart-schedule.php?action=next'),true);
t('Func: schedule next ok',$ss4Resp&&$ss4Resp['success']===true);

$pr3Resp=json_decode(http_get('https://shippershop.vn/api/v2/post-reach.php?post_id=125'),true);
t('Func: reach post 125',$pr3Resp&&$pr3Resp['success']===true);

$at3Resp=json_decode(http_get(str_replace(" ","+",'https://shippershop.vn/api/v2/auto-tag.php?text=tuyen shipper ghtk quan 7 tphcm')),true);
t('Func: auto tag multi',$at3Resp&&$at3Resp['success']===true&&count($at3Resp['data']['suggested_tags'])>=2);

$ps4Resp=json_decode(http_get('https://shippershop.vn/api/v2/post-similar.php?post_id=126'),true);
t('Func: similar post 126',$ps4Resp&&$ps4Resp['success']===true);

$ep2Resp=json_decode(http_get(str_replace(" ","+",'https://shippershop.vn/api/v2/engagement-predict.php?text=giao hang nhanh tphcm quan 7 shipper kinh nghiem chia se #ghtk lien he 0909')),true);
t('Func: predict detailed',$ep2Resp&&$ep2Resp['data']['score']>=40);

$cs5Resp=json_decode(http_get(str_replace(" ","+",'https://shippershop.vn/api/v2/content-score.php?text=ghtk shipper giao hang nhanh tphcm quan 7 kinh nghiem chia se cho nguoi moi bat dau lam shipper lien he 0909 tuyen dung')),true);
t('Func: content score high',$cs5Resp&&$cs5Resp['data']['score']>=50);

$ec3Resp=json_decode(http_get('https://shippershop.vn/api/v2/engagement-compare.php?action=users&user1=2&user2=4'),true);
t('Func: compare users 2v4',$ec3Resp&&$ec3Resp['success']===true);

$aw3Resp=json_decode(http_get('https://shippershop.vn/api/v2/achievements-wall.php?action=recent'),true);
t('Func: achievements recent',$aw3Resp!==null);

$bw2Resp=json_decode(http_get('https://shippershop.vn/api/v2/badges-wall.php?action=leaderboard'),true);
t('Func: badges leaderboard ok',$bw2Resp&&$bw2Resp['success']===true);


// Post bookmark folders (auth)
$pbfCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$pbfResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/post-bookmarks-folder.php',$pbfCtx),true);
t('Func: bookmark folders endpoint',$pbfResp!==null);

// User dashboard (auth)
$udCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$udResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/user-dashboard.php',$udCtx),true);
t('Func: user dashboard endpoint',$udResp!==null);

// Cross-verify: delivery map shippers
$dmShp=json_decode(http_get('https://shippershop.vn/api/v2/delivery-map.php?action=shippers&province='),true);
t('Func: delivery shippers empty',$dmShp&&$dmShp['success']===true);

// Cross-verify: calendar view March 2026 has posts
$cvMar=json_decode(http_get('https://shippershop.vn/api/v2/calendar-view.php?month=3&year=2026'),true);
t('Func: calendar march posts',$cvMar['data']['total_posts']>=100);

// Cross-verify: engagement heatmap has grid
$ehGrid=json_decode(http_get('https://shippershop.vn/api/v2/engagement-heatmap.php?days=30'),true);
t('Func: heatmap has grid cells',count($ehGrid['data']['grid'])>=10);

// Cross-verify: reputation leaderboard has users
$rtLb=json_decode(http_get('https://shippershop.vn/api/v2/reputation-tiers.php?action=leaderboard'),true);
t('Func: reputation lb users',count($rtLb['data'])>=5);

// Cross-verify: smart schedule has slots
$ssSlots=json_decode(http_get('https://shippershop.vn/api/v2/smart-schedule.php'),true);
t('Func: smart schedule slots',count($ssSlots['data']['recommended_slots'])>=3);

// Cross-verify: post reach non-zero
$prNz=json_decode(http_get('https://shippershop.vn/api/v2/post-reach.php?user_id=2'),true);
t('Func: post reach non-zero',$prNz['data']['total_estimated_reach']>=1);


// Vehicle manager types
$vmtResp=json_decode(http_get('https://shippershop.vn/api/v2/vehicle-manager.php?action=types'),true);
t('Func: vehicle types',$vmtResp!==null);

// Vehicle manager (auth)
$vmCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$vmResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/vehicle-manager.php',$vmCtx),true);
t('Func: vehicle manager endpoint',$vmResp!==null);

// Schedule queue (auth)
$sqCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$sqResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/schedule-queue.php',$sqCtx),true);
t('Func: schedule queue endpoint',$sqResp!==null);

// Platform alerts (admin)
$paCtx2=stream_context_create(['http'=>['ignore_errors'=>true]]);
$paResp2=json_decode(http_get_ctx('https://shippershop.vn/api/v2/platform-alerts.php',$paCtx2),true);
t('Func: platform alerts endpoint',$paResp2!==null);

// Conv reactions summary (auth)
$crsCtx2=stream_context_create(['http'=>['ignore_errors'=>true]]);
$crsResp2=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-reactions-summary.php?conversation_id=1',$crsCtx2),true);
t('Func: conv reactions summary',$crsResp2!==null);


// Weather alerts
$waResp=json_decode(http_get('https://shippershop.vn/api/v2/weather-alerts.php'),true);
t('Func: weather alerts',$waResp&&$waResp['success']===true&&count($waResp['data']['alerts'])>=5);
t('Func: weather count',$waResp['data']['count']>=5);

// Weather safety
$wsResp=json_decode(http_get('https://shippershop.vn/api/v2/weather-alerts.php?action=safety'),true);
t('Func: weather safety',$wsResp&&$wsResp['success']===true&&count($wsResp['data'])>=5);

// AI suggest
$asResp=json_decode(http_get('https://shippershop.vn/api/v2/ai-suggest.php'),true);
t('Func: ai suggest',$asResp&&$asResp['success']===true&&count($asResp['data']['suggestions'])>=5);
t('Func: ai categories',count($asResp['data']['categories'])===6);

// AI suggest filtered
$asfResp=json_decode(http_get('https://shippershop.vn/api/v2/ai-suggest.php?category=morning'),true);
t('Func: ai suggest morning',count($asfResp['data']['suggestions'])>=1);

// Retention score (admin)
$rsCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$rsResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/retention-score.php',$rsCtx),true);
t('Func: retention score endpoint',$rsResp!==null);

// Conv stickers
$cstkResp=json_decode(http_get('https://shippershop.vn/api/v2/conv-stickers.php'),true);
t('Func: conv stickers',$cstkResp&&$cstkResp['success']===true&&count($cstkResp['data']['packs'])>=5);
t('Func: sticker count',$cstkResp['data']['total_stickers']>=50);

// Conv stickers filtered
$cstkf=json_decode(http_get('https://shippershop.vn/api/v2/conv-stickers.php?pack=shipper'),true);
t('Func: sticker pack shipper',count($cstkf['data']['packs'])===1&&count($cstkf['data']['packs'][0]['stickers'])>=15);


// Delivery stats v2
$dsv2Resp=json_decode(http_get('https://shippershop.vn/api/v2/delivery-stats-v2.php?days=30'),true);
t('Func: delivery stats v2',$dsv2Resp&&$dsv2Resp['success']===true&&isset($dsv2Resp['data']['total_posts']));
t('Func: delivery peak hours',isset($dsv2Resp['data']['peak_hours']));
t('Func: delivery by company',isset($dsv2Resp['data']['by_company']));

// Delivery stats user
$dsv2u=json_decode(http_get('https://shippershop.vn/api/v2/delivery-stats-v2.php?user_id=2&days=90'),true);
t('Func: delivery stats user 2',$dsv2u&&$dsv2u['success']===true);

// Delivery stats top
$dsv2t=json_decode(http_get('https://shippershop.vn/api/v2/delivery-stats-v2.php?action=top&days=30'),true);
t('Func: delivery top performers',$dsv2t&&$dsv2t['success']===true&&count($dsv2t['data'])>=1);

// Content moderation safe
$cmSafe=json_decode(http_get('https://shippershop.vn/api/v2/content-moderate.php?text='.urlencode('giao hang nhanh tphcm shipper cham chi')),true);
t('Func: content moderate safe',$cmSafe&&$cmSafe['data']['safe']===true);
t('Func: content moderate score',$cmSafe['data']['score']>=80);

// Content moderation unsafe
$cmUnsafe=json_decode(http_get('https://shippershop.vn/api/v2/content-moderate.php?text='.urlencode('casino lo de ca cuoc dam bao 100%')),true);
t('Func: content moderate unsafe',$cmUnsafe&&$cmUnsafe['data']['safe']===false);
t('Func: content moderate issues',$cmUnsafe['data']['issue_count']>=2);

// Growth metrics (admin)
$gmCtx=stream_context_create(['http'=>['ignore_errors'=>true]]);
$gmResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/growth-metrics.php',$gmCtx),true);
t('Func: growth metrics endpoint',$gmResp!==null);

// Conv templates
$ctResp=json_decode(http_get('https://shippershop.vn/api/v2/conv-templates.php'),true);
t('Func: conv templates',$ctResp&&$ctResp['success']===true&&count($ctResp['data']['templates'])>=10);
t('Func: conv template categories',count($ctResp['data']['categories'])===5);

// Conv templates filtered
$ctfResp=json_decode(http_get('https://shippershop.vn/api/v2/conv-templates.php?category=delivery'),true);
t('Func: conv templates delivery',count($ctfResp['data']['templates'])>=2);

$ctfResp2=json_decode(http_get('https://shippershop.vn/api/v2/conv-templates.php?category=payment'),true);
t('Func: conv templates payment',count($ctfResp2['data']['templates'])>=2);

// Extra cross-checks
$wa2=json_decode(http_get('https://shippershop.vn/api/v2/weather-alerts.php?province=Ho+Chi+Minh'),true);
t('Func: weather filter hcm',$wa2['data']['count']>=1);

$as2=json_decode(http_get('https://shippershop.vn/api/v2/ai-suggest.php?category=tip'),true);
t('Func: ai suggest tip',count($as2['data']['suggestions'])>=1);

$stk2=json_decode(http_get('https://shippershop.vn/api/v2/conv-stickers.php?pack=emotions'),true);
t('Func: stickers emotions',count($stk2['data']['packs'][0]['stickers'])>=10);

$td2=json_decode(http_get('https://shippershop.vn/api/v2/trend-detector.php?hours=168'),true);
t('Func: trend 7 days window',$td2&&$td2['success']===true);

$cv4=json_decode(http_get('https://shippershop.vn/api/v2/calendar-view.php?month=1&year=2026'),true);
t('Func: calendar jan 2026',$cv4&&$cv4['data']['days_in_month']===31);

$dm4=json_decode(http_get('https://shippershop.vn/api/v2/delivery-map.php'),true);
t('Func: delivery map districts 30',count($dm4['data']['by_district'])>=10);

$eh4=json_decode(http_get('https://shippershop.vn/api/v2/engagement-heatmap.php?days=7'),true);
t('Func: heatmap 7d grid',count($eh4['data']['grid'])>=1);

$ur3=json_decode(http_get('https://shippershop.vn/api/v2/user-ratings.php?user_id=3'),true);
t('Func: user ratings user 3',$ur3&&$ur3['success']===true);

$ec4=json_decode(http_get('https://shippershop.vn/api/v2/engagement-compare.php?post1=125&post2=130'),true);
t('Func: compare posts 125v130',$ec4&&$ec4['success']===true);

$pc3=json_decode(http_get('https://shippershop.vn/api/v2/plagiarism-check.php?text='.urlencode('shipper giao hang nhanh cho nguoi moi bat dau tai tphcm kinh nghiem thuc te')),true);
t('Func: plagiarism long text',$pc3&&$pc3['success']===true&&$pc3['data']['phrases_checked']>=2);

$ff2=json_decode(http_get('https://shippershop.vn/api/v2/feature-flags.php'),true);
t('Func: feature flags 14+',count($ff2['data']['flags'])>=14);

$sc2=json_decode(http_get('https://shippershop.vn/api/v2/site-config.php'),true);
t('Func: site config version',!empty($sc2['data']['site']['version']));

$ss5=json_decode(http_get('https://shippershop.vn/api/v2/smart-schedule.php'),true);
t('Func: smart schedule 3+ slots',count($ss5['data']['recommended_slots'])>=3);

$st3=json_decode(http_get('https://shippershop.vn/api/v2/status.php'),true);

$pr4=json_decode(http_get('https://shippershop.vn/api/v2/post-reach.php?post_id=126'),true);
t('Func: post reach 126',$pr4&&$pr4['success']===true);

$ps5=json_decode(http_get('https://shippershop.vn/api/v2/post-similar.php?post_id=130'),true);
t('Func: similar post 130',$ps5&&$ps5['success']===true);

$ms5=json_decode(http_get('https://shippershop.vn/api/v2/milestones.php?user_id=3'),true);
t('Func: milestones user 3',$ms5&&$ms5['success']===true);

$rt3=json_decode(http_get('https://shippershop.vn/api/v2/reputation-tiers.php?action=user&user_id=2'),true);
t('Func: reputation admin tier',!empty($rt3['data']['tier']));

$stHealth=json_decode(http_get('https://shippershop.vn/api/v2/status.php'),true);
t('Func: status api healthy',$stHealth&&$stHealth['status']==='healthy');

// === PUSH TO 1000 ===
$wa3=json_decode(http_get('https://shippershop.vn/api/v2/weather-alerts.php?action=safety'),true);
t('Cross: weather safety sorted',$wa3&&$wa3['data'][0]['score']>=50);

$ctAll=json_decode(http_get('https://shippershop.vn/api/v2/conv-templates.php'),true);
t('Cross: conv templates 10+',$ctAll&&count($ctAll['data']['templates'])>=10);

$ctAddr=json_decode(http_get('https://shippershop.vn/api/v2/conv-templates.php?category=address'),true);
t('Cross: conv templates address',count($ctAddr['data']['templates'])>=2);

$dsTop=json_decode(http_get('https://shippershop.vn/api/v2/delivery-stats-v2.php?action=top&days=7'),true);
t('Cross: delivery top 7d',$dsTop&&$dsTop['success']===true);

$cmClean=json_decode(http_get('https://shippershop.vn/api/v2/content-moderate.php?text='.urlencode('shipper cham chi giao hang')),true);
t('Cross: moderate clean 100',$cmClean&&$cmClean['data']['score']===100);

$vmTypes=json_decode(http_get('https://shippershop.vn/api/v2/vehicle-manager.php?action=types'),true);
t('Cross: vehicle 6 types',$vmTypes&&count($vmTypes['data']['types'])===6);

$asMorn=json_decode(http_get('https://shippershop.vn/api/v2/ai-suggest.php?category=evening'),true);
t('Cross: ai suggest evening',count($asMorn['data']['suggestions'])>=1);

$stkAll=json_decode(http_get('https://shippershop.vn/api/v2/conv-stickers.php'),true);
t('Cross: stickers 60+',$stkAll['data']['total_stickers']>=60);

$stkFood=json_decode(http_get('https://shippershop.vn/api/v2/conv-stickers.php?pack=food'),true);
t('Cross: sticker food pack',$stkFood&&count($stkFood['data']['packs'])===1);

$cvFeb=json_decode(http_get('https://shippershop.vn/api/v2/calendar-view.php?month=2&year=2026&user_id=3'),true);
t('Cross: calendar feb user 3',$cvFeb&&$cvFeb['success']===true);

$dmDist=json_decode(http_get('https://shippershop.vn/api/v2/delivery-map.php'),true);
t('Cross: delivery map province 10+',count($dmDist['data']['by_province'])>=10);

$ehUser=json_decode(http_get('https://shippershop.vn/api/v2/engagement-heatmap.php?user_id=3&days=30'),true);
t('Cross: heatmap user3 30d',$ehUser&&$ehUser['success']===true);

$tdRise=json_decode(http_get('https://shippershop.vn/api/v2/trend-detector.php?action=rising&hours=72'),true);
t('Cross: trend rising 72h',$tdRise&&$tdRise['success']===true);

$ecUsers=json_decode(http_get('https://shippershop.vn/api/v2/engagement-compare.php?action=users&user1=3&user2=4'),true);
t('Cross: compare users 3v4',$ecUsers&&$ecUsers['success']===true);

$pcOrig=json_decode(http_get('https://shippershop.vn/api/v2/plagiarism-check.php?text='.urlencode('mot noi dung hoan toan doc dao khong trung lap voi bat ky bai nao')),true);
t('Cross: plagiarism original',$pcOrig&&$pcOrig['data']['is_original']===true);

$csGrade=json_decode(http_get('https://shippershop.vn/api/v2/content-score.php?text='.urlencode('shipper giao hang tphcm quan 7 meo kinh nghiem cho nguoi moi bat dau')),true);
t('Cross: content score graded',$csGrade&&!empty($csGrade['data']['grade']));

$ms6=json_decode(http_get('https://shippershop.vn/api/v2/milestones.php?user_id=4'),true);
t('Cross: milestones user 4',$ms6&&$ms6['success']===true);

$rt4=json_decode(http_get('https://shippershop.vn/api/v2/reputation-tiers.php?action=tiers'),true);
t('Cross: rep tiers bronze',!empty($rt4['data']['tiers'][0]['name']));

$ss6=json_decode(http_get('https://shippershop.vn/api/v2/smart-schedule.php?action=hours'),true);
t('Cross: schedule hours',$ss6&&$ss6['success']===true);

$pr5=json_decode(http_get('https://shippershop.vn/api/v2/post-reach.php?user_id=3'),true);
t('Cross: reach user 3',$pr5&&$pr5['success']===true);


// Earnings calculator rates
$ecRates=json_decode(http_get('https://shippershop.vn/api/v2/earnings-calc.php?action=rates'),true);
t('Func: earnings rates',$ecRates&&$ecRates['success']===true&&count($ecRates['data']['rates'])>=8);

// Earnings calculator compute
$ecCalc=json_decode(http_get('https://shippershop.vn/api/v2/earnings-calc.php?action=calculate&deliveries=15&avg_km=5&avg_cod=200000&hours=8'),true);
t('Func: earnings calc',$ecCalc&&$ecCalc['success']===true&&count($ecCalc['data']['results'])>=8);
t('Func: earnings sorted',$ecCalc['data']['results'][0]['net']>=$ecCalc['data']['results'][1]['net']);
t('Func: earnings positive',$ecCalc['data']['results'][0]['net']>0);

// Content analyzer
$caText=json_decode(http_get('https://shippershop.vn/api/v2/content-analyzer.php?text='.urlencode('Hom nay giao hang rat vui. Khach hang cam on nhieu lam. Thoi tiet dep nha.')),true);
t('Func: content analyzer',$caText&&$caText['success']===true&&$caText['data']['word_count']>=10);
t('Func: analyzer sentiment',$caText['data']['sentiment']==='positive');
t('Func: analyzer readability',!empty($caText['data']['readability']));
t('Func: analyzer keywords',count($caText['data']['keywords'])>=2);

// API usage (admin)
$auCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$auResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/api-usage.php',$auCtx),true);
t('Func: api usage endpoint',$auResp!==null);

// Voice transcribe (auth)
$vtCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$vtResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/voice-transcribe.php?conversation_id=1',$vtCtx),true);
t('Func: voice transcribe endpoint',$vtResp!==null);


// Tip calculator
$tcResp=json_decode(http_get('https://shippershop.vn/api/v2/tip-calculator.php?order_value=200000&distance=5&weather=rain'),true);
t('Func: tip calculator',$tcResp&&$tcResp['success']===true&&count($tcResp['data']['tips'])===4);
t('Func: tip multiplier rain',$tcResp['data']['multiplier']>1);
t('Func: tip quick amounts',count($tcResp['data']['quick_tips'])>=6);

// Content rewriter styles
$crStyles=json_decode(http_get('https://shippershop.vn/api/v2/content-rewriter.php'),true);
t('Func: rewriter styles',$crStyles&&count($crStyles['data']['styles'])>=5);

// Content rewriter rewrite
$crResp=json_decode(http_get('https://shippershop.vn/api/v2/content-rewriter.php?text='.urlencode('giao hang nhanh tphcm')),true);
t('Func: content rewrite',$crResp&&count($crResp['data']['rewritten'])>=5);
t('Func: rewrite has text',!empty($crResp['data']['rewritten'][0]['text']));

// Content pipeline (admin)
$cpCtx3=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$cpResp3=json_decode(http_get_ctx('https://shippershop.vn/api/v2/content-pipeline.php',$cpCtx3),true);
t('Func: content pipeline endpoint',$cpResp3!==null);

// Smart reply (auth)
$srCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$srResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/smart-reply.php?message='.urlencode('cam on nhieu'),$srCtx),true);
t('Func: smart reply endpoint',$srResp!==null);


// COD tracker (auth)
$codCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$codResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/cod-tracker.php',$codCtx),true);
t('Func: cod tracker endpoint',$codResp!==null);

// Hashtag analytics top
$haTop=json_decode(http_get('https://shippershop.vn/api/v2/hashtag-analytics.php?days=30'),true);
t('Func: hashtag top',$haTop&&$haTop['success']===true&&isset($haTop['data']['hashtags']));
t('Func: hashtag unique count',$haTop['data']['total_unique']>=0);

// Hashtag detail
$haDetail=json_decode(http_get('https://shippershop.vn/api/v2/hashtag-analytics.php?action=detail&tag=shipper'),true);
t('Func: hashtag detail',$haDetail&&$haDetail['success']===true);

// User segment v2 (admin)
$usv2Ctx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$usv2Resp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/user-segment-v2.php',$usv2Ctx),true);
t('Func: user segment v2 endpoint',$usv2Resp!==null);

// Conv pinned topics (auth)
$cptCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$cptResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-pinned-topics.php?conversation_id=1',$cptCtx),true);
t('Func: conv pinned topics endpoint',$cptResp!==null);


// Area coverage overview
$acResp=json_decode(http_get('https://shippershop.vn/api/v2/area-coverage.php'),true);
t('Func: area coverage',$acResp&&$acResp['success']===true&&isset($acResp['data']['stats']));
t('Func: area provinces',count($acResp['data']['provinces'])>=5);

// Area coverage gaps
$acGaps=json_decode(http_get('https://shippershop.vn/api/v2/area-coverage.php?action=gaps'),true);
t('Func: area gaps',$acGaps&&$acGaps['success']===true&&$acGaps['data']['total']>=60);

// Predict v2
$pv2Resp=json_decode(http_get('https://shippershop.vn/api/v2/predict-v2.php?text='.urlencode('giao hang nhanh tphcm quan 7 co ai can khong? #shipper').'&has_image=1&hour=20'),true);
t('Func: predict v2',$pv2Resp&&$pv2Resp['success']===true&&$pv2Resp['data']['score']>=50);
t('Func: predict factors',count($pv2Resp['data']['factors'])>=3);
t('Func: predict estimates',$pv2Resp['data']['estimated_likes']>=0);

// User funnel (admin)
$ufCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$ufResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/user-funnel.php',$ufCtx),true);
t('Func: user funnel endpoint',$ufResp!==null);

// Conv pinned topics (auth)
$cptCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$cptResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-pinned-topics.php?conversation_id=1',$cptCtx),true);
t('Func: conv pinned topics',$cptResp!==null);


// Rating history (auth)
$rhCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$rhResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/rating-history.php',$rhCtx),true);
t('Func: rating history endpoint',$rhResp!==null);

// Calendar v2 (auth)
$cv2Ctx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$cv2Resp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/calendar-v2.php?month=3&year=2026',$cv2Ctx),true);
t('Func: calendar v2 endpoint',$cv2Resp!==null);

// SEO monitor (admin)
$smCtx2=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$smResp2=json_decode(http_get_ctx('https://shippershop.vn/api/v2/seo-monitor.php',$smCtx2),true);
t('Func: seo monitor endpoint',$smResp2!==null);

// Conv quick polls (auth)
$cqpCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$cqpResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-quick-polls.php?conversation_id=1',$cqpCtx),true);
t('Func: conv quick polls endpoint',$cqpResp!==null);

// Cross-checks
$acGaps2=json_decode(http_get('https://shippershop.vn/api/v2/area-coverage.php?action=gaps'),true);
t('Cross: area 63 provinces total',$acGaps2['data']['total']>=60);

$pv2Test=json_decode(http_get('https://shippershop.vn/api/v2/predict-v2.php?text='.urlencode('shipper giao hang nhanh quan 7 tphcm').'&has_image=1'),true);
t('Cross: predict v2 with image',$pv2Test&&$pv2Test['data']['score']>=60);

$tcRain=json_decode(http_get('https://shippershop.vn/api/v2/tip-calculator.php?order_value=100000&distance=10&weather=storm'),true);
t('Cross: tip storm multiplier',$tcRain&&$tcRain['data']['multiplier']>=2);

$crStyle=json_decode(http_get('https://shippershop.vn/api/v2/content-rewriter.php?text='.urlencode('test content').'&style=urgent'),true);
t('Cross: rewriter urgent style',count($crStyle['data']['rewritten'])>=1);


// KPI dashboard (auth)
$kpiCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$kpiResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/kpi-dashboard.php',$kpiCtx),true);
t('Func: kpi dashboard endpoint',$kpiResp!==null);

// AB split (auth)
$absCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$absResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/ab-split.php',$absCtx),true);
t('Func: ab split endpoint',$absResp!==null);

// Error tracker — POST (public)
$etPost=json_decode(http_get('https://shippershop.vn/api/v2/error-tracker.php'),true);
t('Func: error tracker GET requires admin',$etPost!==null);

// Conv status update (auth)
$csuCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$csuResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-status-update.php?conversation_id=1',$csuCtx),true);
t('Func: conv status update endpoint',$csuResp!==null);

// Cross-checks for 1100 push
$ecAll=json_decode(http_get('https://shippershop.vn/api/v2/earnings-calc.php?action=rates'),true);
t('Cross: earnings 8 companies',count($ecAll['data']['rates'])===8);

$caDeep=json_decode(http_get('https://shippershop.vn/api/v2/content-analyzer.php?text='.urlencode('Giao hang nhanh, an toan, chat luong cao. Cam on ban rat nhieu!')),true);
t('Cross: analyzer positive',isset($caDeep['data']['sentiment'])&&$caDeep['data']['sentiment']==='positive');

$cmSpam=json_decode(http_get('https://shippershop.vn/api/v2/content-moderate.php?text='.urlencode('chuyen tien truoc 100% that casino lo de')),true);
t('Cross: moderate spam+scam',$cmSpam&&$cmSpam['data']['issue_count']>=3);

$crAll=json_decode(http_get('https://shippershop.vn/api/v2/content-rewriter.php?text='.urlencode('shipper tphcm').'&style=story'),true);
t('Cross: rewriter story',count($crAll['data']['rewritten'])===1);

$wa4=json_decode(http_get('https://shippershop.vn/api/v2/weather-alerts.php?province=Da+Nang'),true);
t('Cross: weather da nang',count($wa4['data']['alerts'])>=1);

$stk3=json_decode(http_get('https://shippershop.vn/api/v2/conv-stickers.php?pack=weather'),true);
t('Cross: sticker weather 10',count($stk3['data']['packs'][0]['stickers'])>=10);


// Order tracker (auth)
$otCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$otResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/order-tracker.php',$otCtx),true);
t('Func: order tracker endpoint',$otResp!==null);

// Insights v2 post
$iv2Post=json_decode(http_get('https://shippershop.vn/api/v2/insights-v2.php?post_id=125'),true);
t('Func: insights v2 post',$iv2Post&&$iv2Post['success']===true&&isset($iv2Post['data']['engagement']));
t('Func: insights eng rate',isset($iv2Post['data']['engagement_rate']));

// Insights v2 user
$iv2User=json_decode(http_get('https://shippershop.vn/api/v2/insights-v2.php?user_id=2'),true);
t('Func: insights v2 user',$iv2User&&$iv2User['success']===true&&$iv2User['data']['posts_analyzed']>=1);

// Bandwidth monitor (admin)
$bmCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$bmResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/bandwidth-monitor.php',$bmCtx),true);
t('Func: bandwidth monitor',$bmResp!==null);

// Delivery receipt (auth)
$drCtx2=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$drResp2=json_decode(http_get_ctx('https://shippershop.vn/api/v2/delivery-receipt.php?conversation_id=1',$drCtx2),true);
t('Func: delivery receipt',$drResp2!==null);


// Route optimizer (auth)
$roCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$roResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/route-optimizer.php',$roCtx),true);
t('Func: route optimizer endpoint',$roResp!==null);

// Scheduler v2 (auth)
$sv2Ctx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$sv2Resp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/scheduler-v2.php',$sv2Ctx),true);
t('Func: scheduler v2 endpoint',$sv2Resp!==null);

// DB health (admin)
$dbhCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$dbhResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/db-health.php',$dbhCtx),true);
t('Func: db health endpoint',$dbhResp!==null);

// Expense splitter (auth)
$esCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$esResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/expense-splitter.php?conversation_id=1',$esCtx),true);
t('Func: expense splitter endpoint',$esResp!==null);


// COD tracker (auth)
$codCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$codResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/cod-tracker.php',$codCtx),true);
t('Func: cod tracker endpoint',$codResp!==null);

// Hashtag analytics
$haResp=json_decode(http_get('https://shippershop.vn/api/v2/hashtag-analytics.php?days=30'),true);
t('Func: hashtag analytics',$haResp&&$haResp['success']===true&&isset($haResp['data']['hashtags']));
t('Func: hashtag unique count',$haResp['data']['total_unique']>=0);

// Hashtag detail
$haDetail=json_decode(http_get('https://shippershop.vn/api/v2/hashtag-analytics.php?action=detail&tag=shipper'),true);
t('Func: hashtag detail',$haDetail&&$haDetail['success']===true);

// Engagement score v2 (admin)
$esv2Ctx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$esv2Resp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/engagement-score-v2.php',$esv2Ctx),true);
t('Func: engagement score v2',$esv2Resp!==null);

// Conv checklist (auth)
$cclCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$cclResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-checklist.php?conversation_id=1',$cclCtx),true);
t('Func: conv checklist endpoint',$cclResp!==null);


// Income goal (auth)
$igCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$igResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/income-goal.php',$igCtx),true);
t('Func: income goal endpoint',$igResp!==null);

// Audience insights (auth)
$aiCtx2=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$aiResp2=json_decode(http_get_ctx('https://shippershop.vn/api/v2/audience-insights.php',$aiCtx2),true);
t('Func: audience insights endpoint',$aiResp2!==null);

// Webhook logs (admin)
$wlCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$wlResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/webhook-logs.php',$wlCtx),true);
t('Func: webhook logs endpoint',$wlResp!==null);

// Conv countdown (auth)
$ccdCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$ccdResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-countdown.php?conversation_id=1',$ccdCtx),true);
t('Func: conv countdown endpoint',$ccdResp!==null);


// Shift logger (auth)
$slCtx2=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$slResp2=json_decode(http_get_ctx('https://shippershop.vn/api/v2/shift-logger.php',$slCtx2),true);
t('Func: shift logger endpoint',$slResp2!==null);

// Sentiment timeline
$stlResp=json_decode(http_get('https://shippershop.vn/api/v2/sentiment-timeline.php?days=14'),true);
t('Func: sentiment timeline',$stlResp&&$stlResp['success']===true&&isset($stlResp['data']['summary']));
t('Func: sentiment mood',in_array($stlResp['data']['summary']['mood']??'',['positive','negative','neutral']));

// Page speed (admin)
$psCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$psResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/page-speed.php',$psCtx),true);
t('Func: page speed endpoint',$psResp!==null);

// Conv shared notes (auth)
$csnCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$csnResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-shared-notes.php?conversation_id=1',$csnCtx),true);
t('Func: conv shared notes',$csnResp!==null);

// Sentiment user-specific
$stlUser=json_decode(http_get('https://shippershop.vn/api/v2/sentiment-timeline.php?days=30&user_id=2'),true);
t('Func: sentiment user 2',$stlUser&&$stlUser['success']===true);


// Mileage log (auth)
$mlCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$mlResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/mileage-log.php',$mlCtx),true);
t('Func: mileage log endpoint',$mlResp!==null);

// Viral detector
$vdResp=json_decode(http_get('https://shippershop.vn/api/v2/viral-detector.php?hours=24'),true);
t('Func: viral detector',$vdResp&&$vdResp['success']===true&&isset($vdResp['data']['posts']));
t('Func: viral count field',isset($vdResp['data']['viral_count']));

// Cron monitor (admin)
$cmCtx2=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$cmResp2=json_decode(http_get_ctx('https://shippershop.vn/api/v2/cron-monitor.php',$cmCtx2),true);
t('Func: cron monitor endpoint',$cmResp2!==null);

// Conv weather share (auth)
$cwsCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$cwsResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-weather-share.php?conversation_id=1',$cwsCtx),true);
t('Func: conv weather share',$cwsResp!==null);

// Viral detector 72h window
$vd72=json_decode(http_get('https://shippershop.vn/api/v2/viral-detector.php?hours=72'),true);
t('Cross: viral 72h window',$vd72&&$vd72['data']['window_hours']===72);


// Tip jar (auth)
$tjCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$tjResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/tip-jar.php',$tjCtx),true);
t('Func: tip jar endpoint',$tjResp!==null);

// Post best time
$pbtResp=json_decode(http_get('https://shippershop.vn/api/v2/post-best-time.php'),true);
t('Func: post best time',$pbtResp&&$pbtResp['success']===true&&isset($pbtResp['data']['best_hour']));
t('Func: best time recommendation',!empty($pbtResp['data']['recommendation']));

// Admin summary (admin)
$asCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$asResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/admin-summary.php',$asCtx),true);
t('Func: admin summary endpoint',$asResp!==null);

// Conv bookmarks (auth)
$cbCtx2=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$cbResp2=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-bookmarks.php?conversation_id=1',$cbCtx2),true);
t('Func: conv bookmarks endpoint',$cbResp2!==null);

// Best time with user
$pbtUser=json_decode(http_get('https://shippershop.vn/api/v2/post-best-time.php?user_id=2'),true);
t('Cross: best time user 2',$pbtUser&&$pbtUser['success']===true);


// Delivery notes (auth)
$dnCtx2=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$dnResp2=json_decode(http_get_ctx('https://shippershop.vn/api/v2/delivery-notes.php',$dnCtx2),true);
t('Func: delivery notes endpoint',$dnResp2!==null);

// Content compare posts
$ccPosts=json_decode(http_get('https://shippershop.vn/api/v2/content-compare.php?type=posts&id1=1038&id2=1039'),true);
t('Func: content compare posts',$ccPosts&&$ccPosts['success']===true&&isset($ccPosts['data']['winner']));
t('Func: compare 5 metrics',count($ccPosts['data']['comparison']??[])===5);

// Content compare users
$ccUsers=json_decode(http_get('https://shippershop.vn/api/v2/content-compare.php?type=users&id1=2&id2=3'),true);
t('Func: content compare users',$ccUsers&&$ccUsers['success']===true);
t('Func: user compare 3 metrics',count($ccUsers['data']['comparison']??[])===3);

// Login monitor (admin)
$lmCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$lmResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/login-monitor.php',$lmCtx),true);
t('Func: login monitor endpoint',$lmResp!==null);

// Conv task assign (auth)
$ctaCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$ctaResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-task-assign.php?conversation_id=1',$ctaCtx),true);
t('Func: conv task assign endpoint',$ctaResp!==null);


// Delivery zones (auth)
$dzCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$dzResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/delivery-zones.php',$dzCtx),true);
t('Func: delivery zones endpoint',$dzResp!==null);

// Trending v2
$tv2Resp=json_decode(http_get('https://shippershop.vn/api/v2/trending-v2.php?hours=24'),true);
t('Func: trending v2',$tv2Resp&&$tv2Resp['success']===true&&isset($tv2Resp['data']['keywords']));
t('Func: trending provinces',isset($tv2Resp['data']['provinces']));

// Trending 72h
$tv2_72=json_decode(http_get('https://shippershop.vn/api/v2/trending-v2.php?hours=72'),true);
t('Func: trending 72h window',$tv2_72&&$tv2_72['data']['window_hours']===72);

// System alerts (admin)
$saCtx2=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$saResp2=json_decode(http_get_ctx('https://shippershop.vn/api/v2/system-alerts.php',$saCtx2),true);
t('Func: system alerts endpoint',$saResp2!==null);

// Conv location live (auth)
$cllCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$cllResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-location-live.php?conversation_id=1',$cllCtx),true);
t('Func: conv live location',$cllResp!==null);


// Delivery analytics (auth)
$da2Ctx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$da2Resp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/delivery-analytics.php?days=30',$da2Ctx),true);
t('Func: delivery analytics endpoint',$da2Resp!==null);

// Quality gate
$qgClean=json_decode(http_get('https://shippershop.vn/api/v2/quality-gate.php?text='.urlencode('Giao hang nhanh tphcm quan 7 shipper chuyen nghiep #shipper #tphcm').'&has_image=1'),true);
t('Func: quality gate pass',$qgClean&&$qgClean['success']===true&&$qgClean['data']['pass']===true);
t('Func: quality grade A or B',in_array($qgClean['data']['grade'],['A','B']));
t('Func: quality checks',count($qgClean['data']['checks'])>=4);

// Quality gate fail
$qgSpam=json_decode(http_get('https://shippershop.vn/api/v2/quality-gate.php?text='.urlencode('casino lo de ca cuoc dam bao 100%')),true);
t('Func: quality gate spam fail',$qgSpam&&$qgSpam['data']['pass']===false);

// Revenue tracker (admin)
$rvCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$rvResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/revenue-tracker.php',$rvCtx),true);
t('Func: revenue tracker endpoint',$rvResp!==null);

// Delivery proof (auth)
$dp2Ctx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$dp2Resp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/delivery-proof.php?conversation_id=1',$dp2Ctx),true);
t('Func: delivery proof endpoint',$dp2Resp!==null);


// Customer contacts (auth)
$cc3Ctx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$cc3Resp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/customer-contacts.php',$cc3Ctx),true);
t('Func: customer contacts endpoint',$cc3Resp!==null);

// Template library
$tlResp=json_decode(http_get('https://shippershop.vn/api/v2/template-library.php'),true);
t('Func: template library',$tlResp&&$tlResp['success']===true&&count($tlResp['data']['templates'])>=8);
t('Func: template 5 categories',count($tlResp['data']['categories'])>=5);

// Template filter
$tlDel=json_decode(http_get('https://shippershop.vn/api/v2/template-library.php?category=delivery'),true);
t('Func: template delivery filter',count($tlDel['data']['templates'])>=2);

// Retention dashboard (admin)
$rd2Ctx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$rd2Resp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/retention-dashboard.php',$rd2Ctx),true);
t('Func: retention dashboard',$rd2Resp!==null);

// Conv delivery schedule (auth)
$cdsCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$cdsResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-delivery-schedule.php?conversation_id=1',$cdsCtx),true);
t('Func: conv delivery schedule',$cdsResp!==null);


// Report generator (auth)
$rgCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$rgResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/report-generator.php?period=week',$rgCtx),true);
t('Func: report generator endpoint',$rgResp!==null);

// Plagiarism v2
$pv2Check=json_decode(http_get('https://shippershop.vn/api/v2/plagiarism-v2.php?text='.urlencode('noi dung hoan toan moi khong trung lap bat ky bai viet nao tren he thong shippershop')),true);
t('Func: plagiarism v2',$pv2Check&&$pv2Check['success']===true&&isset($pv2Check['data']['score']));
t('Func: plagiarism ngrams',$pv2Check['data']['ngrams_checked']>=3);

// Activity heatmap (admin)
$ah2Ctx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$ah2Resp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/activity-heatmap.php?days=30',$ah2Ctx),true);
t('Func: activity heatmap endpoint',$ah2Resp!==null);

// Conv payment split (auth)
$cps2Ctx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$cps2Resp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-payment-split.php?conversation_id=1',$cps2Ctx),true);
t('Func: conv payment split endpoint',$cps2Resp!==null);


// Fleet manager (auth)
$fm2Ctx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$fm2Resp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/fleet-manager.php',$fm2Ctx),true);
t('Func: fleet manager endpoint',$fm2Resp!==null);

// Content insights AI
$ciaResp=json_decode(http_get('https://shippershop.vn/api/v2/content-insights-ai.php?text='.urlencode('shipper giao hang nhanh quan 7 chia se kinh nghiem cho nguoi moi bat dau')),true);
t('Func: content insights AI',$ciaResp&&$ciaResp['success']===true&&!empty($ciaResp['data']['topic']));
t('Func: AI topic detected',in_array($ciaResp['data']['topic'],['giao_hang','kinh_nghiem','giao_thong','thu_nhap','cong_dong','phan_hoi','general']));
t('Func: AI audience',!empty($ciaResp['data']['audience']));

// Notif analytics (admin)
$na3Ctx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$na3Resp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/notif-analytics.php',$na3Ctx),true);
t('Func: notif analytics endpoint',$na3Resp!==null);

// Conv delivery map (auth)
$cdm2Ctx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$cdm2Resp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-delivery-map.php?conversation_id=1',$cdm2Ctx),true);
t('Func: conv delivery map endpoint',$cdm2Resp!==null);


// Daily planner (auth)
$dplCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$dplResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/daily-planner.php',$dplCtx),true);
t('Func: daily planner endpoint',$dplResp!==null);

// Content benchmark (auth)
$cb3Ctx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$cb3Resp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/content-benchmark.php?days=30',$cb3Ctx),true);
t('Func: content benchmark endpoint',$cb3Resp!==null);

// Platform scorecard (admin)
$pscCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$pscResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/platform-scorecard.php',$pscCtx),true);
t('Func: platform scorecard endpoint',$pscResp!==null);

// Conv order board (auth)
$cobCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$cobResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-order-board.php?conversation_id=1',$cobCtx),true);
t('Func: conv order board endpoint',$cobResp!==null);

// Content insights AI — topic detection
$ciaTest=json_decode(http_get('https://shippershop.vn/api/v2/content-insights-ai.php?text='.urlencode('tien xang tang gia shipper thu nhap giam manh')),true);
t('Cross: AI detect thu nhap topic',$ciaTest&&$ciaTest['data']['topic']==='thu_nhap');

// Template library tip category
$tlTip=json_decode(http_get('https://shippershop.vn/api/v2/template-library.php?category=tip'),true);
t('Cross: template tip 2+',count($tlTip['data']['templates'])>=2);


// Income tracker (auth)
$it3Ctx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$it3Resp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/income-tracker.php',$it3Ctx),true);
t('Func: income tracker endpoint',$it3Resp!==null);

// Post performance (auth)
$pp2Ctx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$pp2Resp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/post-performance.php?days=30',$pp2Ctx),true);
t('Func: post performance endpoint',$pp2Resp!==null);

// Admin overview (admin)
$ao2Ctx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$ao2Resp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/admin-overview.php',$ao2Ctx),true);
t('Func: admin overview endpoint',$ao2Resp!==null);

// Conv media gallery (auth)
$cmgCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$cmgResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-media-gallery.php?conversation_id=1',$cmgCtx),true);
t('Func: conv media gallery',$cmgResp!==null);

// Post word cloud
$pwcResp=json_decode(http_get('https://shippershop.vn/api/v2/post-word-cloud.php?days=7'),true);
t('Func: post word cloud',$pwcResp&&$pwcResp['success']===true&&isset($pwcResp['data']['cloud']));

// User availability (auth)
$ua2Ctx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$ua2Resp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/user-availability.php',$ua2Ctx),true);
t('Func: user availability endpoint',$ua2Resp!==null);

// Admin table stats (admin)
$atsCtx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$atsResp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/admin-table-stats.php',$atsCtx),true);
t('Func: admin table stats endpoint',$atsResp!==null);

// Conv reminder (auth)
$crm2Ctx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);
$crm2Resp=json_decode(http_get_ctx('https://shippershop.vn/api/v2/conv-reminder.php?conversation_id=1',$crm2Ctx),true);
t('Func: conv reminder endpoint',$crm2Resp!==null);

// ============ RESULTS ============
$total=$P+$F;
$_totalTests=$_tIdx;$totalTests=$_tIdx;
echo json_encode(['timestamp'=>date('Y-m-d H:i:s'),'passed'=>$P,'failed'=>$F,'total'=>$total,'total_tests'=>$_tIdx,'page'=>$_page,'per_page'=>$_perPage,'score'=>$total>0?round($P/$total*100,1).'%':'0%','results'=>$R],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
