<?php
/**
 * ShipperShop API Documentation — Auto-generated
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'name' => 'ShipperShop API',
    'version' => 'v1.0',
    'base_url' => 'https://shippershop.vn/api',
    'auth' => 'Bearer JWT token in Authorization header',
    'endpoints' => [
        'Feed' => [
            ['GET /posts.php', 'List posts', '?limit=20&page=1&sort=new|hot|trending|top|following&cursor=1020&type=post|confession|review&province=...&search=...'],
            ['GET /posts.php?id=X', 'Single post', 'Returns post with user_liked, user_saved'],
            ['GET /posts.php?action=comments&post_id=X', 'Comments', '?cpage=1&climit=50, returns {comments,total,has_more}'],
            ['GET /posts.php?action=user_profile&user_id=X', 'User profile stats', 'posts_count, likes_count, is_following'],
            ['POST /posts.php', 'Create post', '{content, type, images[], province, district}'],
            ['POST /posts.php?action=vote', 'Like/unlike', '{post_id}'],
            ['POST /posts.php?action=comment', 'Add comment', '{post_id, content, parent_id}'],
            ['POST /posts.php?action=edit', 'Edit post', '{post_id, content} (owner only)'],
            ['POST /posts.php?action=report', 'Report post', '{post_id, reason}'],
            ['POST /posts.php?action=save', 'Save/unsave', '{post_id}'],
            ['POST /posts.php?action=share', 'Share count++', '{post_id}'],
            ['POST /posts.php?action=delete', 'Delete post', '{post_id} (owner only)'],
        ],
        'Search' => [
            ['GET /search.php', 'Global search', '?q=keyword&type=all|posts|users|groups&limit=10'],
        ],
        'Groups' => [
            ['GET /groups.php?action=discover', 'Discover groups', 'popular, recommended, by_category',
            ['POST /groups.php?action=set_role', 'Set member role', '{group_id, user_id, role} (admin only)']
        ,
            ['POST /groups.php?action=kick_member', 'Kick member', '{group_id, user_id} (admin/mod)']
        ,
            ['POST /groups.php?action=update_settings', 'Group settings', '{group_id, name, description, privacy}']
        ,
            ['POST /groups.php?action=upload_cover', 'Group cover', 'multipart {group_id, image}']
        ,
            ['POST /groups.php?action=share_post', 'Share to group', '{post_id, group_id}']
        ,
            ['POST /groups.php?action=invite', 'Invite friend', '{group_id, user_id}']
        ],
            ['GET /groups.php?action=posts&group_id=X', 'Group posts', '?page=1&sort=new|hot|top&cursor=X'],
            ['GET /groups.php?action=members&group_id=X', 'Members', ''],
            ['GET /groups.php?action=comments&post_id=X', 'Post comments', ''],
            ['POST /groups.php?action=post', 'Create group post', '{group_id, content}'],
            ['POST /groups.php?action=edit_post', 'Edit group post', '{post_id, content}'],
            ['POST /groups.php?action=like_post', 'Like group post', '{post_id}'],
            ['POST /groups.php?action=pin_post', 'Pin post', '{post_id} (admin only)'],
            ['POST /groups.php?action=comment', 'Comment', '{post_id, content}'],
        ],
        'Messages' => [
            ['GET /messages-api.php?action=conversations', 'Conversation list', '?tab=active|pending'],
            ['GET /messages-api.php?action=messages&conversation_id=X', 'Messages', ''],
            ['GET /messages-api.php?action=poll&conversation_id=X&since=timestamp', 'Poll new', 'Lightweight check'],
            ['POST /messages-api.php?action=send', 'Send message', '{conversation_id, content}'],
            ['POST /messages-api.php?action=typing', 'Typing indicator', '{conversation_id}'],
            ['POST /messages-api.php?action=read', 'Mark read', '{conversation_id}'],
        ],
        'Wallet' => [
            ['GET /wallet-api.php?action=plans', 'Subscription plans', '',
            ['GET /wallet-api.php?action=export_csv', 'Export transactions CSV', 'Download UTF-8 CSV']
        ],
            ['GET /wallet-api.php?action=info', 'Wallet info', 'balance, subscription, csrf_token'],
            ['GET /wallet-api.php?action=transactions', 'Transaction history', '?page=1'],
            ['POST /wallet-api.php?action=set_pin', 'Set PIN', '{pin} (4-6 digits)'],
            ['POST /wallet-api.php?action=subscribe', 'Subscribe', '{plan_id, pin, csrf_token}'],
            ['POST /wallet-api.php?action=deposit', 'Deposit request', '{amount, csrf_token}'],
        ],
        'Social' => [
            ['POST /social.php?action=follow', 'Follow/unfollow', '{user_id}'],
            ['POST /social.php?action=block', 'Block/unblock', '{user_id}'],
            ['GET /friends.php?action=suggestions', 'Friend suggestions', ''],
            ['GET /friends.php?action=online', 'Online users', ''],
        ],
        'Notifications' => [
            ['GET /notifications.php', 'All notifications', ''],
            ['GET /notifications.php?action=unread_count', 'Unread count', 'For badge polling'],
            ['POST /notifications.php?action=mark_all_read', 'Mark all read', ''],
        ],
        'Traffic' => [
            ['GET /traffic.php', 'Active alerts', '?category=traffic|weather|terrain'],
            ['POST /traffic.php', 'Create alert', '{content, category, severity, latitude, longitude}'],
            ['POST /traffic.php?action=vote', 'Confirm/deny', '{alert_id, vote}'],
        ],
        'Marketplace' => [
            ['GET /marketplace.php', 'Listings', '?category=X&search=X&page=1',
            ['GET /marketplace.php?sort=price_asc', 'Sort by price', '?price_min=X&price_max=Y&condition=new|used']
        ,
            ['POST /marketplace.php?action=mark_sold', 'Mark sold', '{listing_id} — toggle']
        ,
            ['GET /marketplace.php?action=categories', 'Categories', 'With listing counts']
        ],
            ['GET /marketplace.php?id=X', 'Single listing', ''],
            ['POST /marketplace.php', 'Create listing', '{title, price, category, images[]}'],
        ],
        'Map' => [
            ['GET /map-pins.php', 'Map pins', '?type=X&lat1=..&lng1=..&lat2=..&lng2=..'],
        ],
        'User' => [
            ['GET /user-page.php?id=X', 'User profile', 'sub_badge, follower_count, post_count'],
            ['GET /user-page.php?action=posts&id=X', 'User posts', ''],
        ],
        'Admin' => [
            ['GET /admin-moderation.php?action=stats', 'Dashboard stats', 'Admin only'],
            ['GET /admin-moderation.php?action=reports', 'Reports list', '?status=pending'],
            ['POST /admin-moderation.php?action=review_report', 'Review report', '{report_id, decision}'],
            ['GET /admin-moderation.php?action=users', 'User list', '?q=search&status=active&page=1'],
            ['POST /admin-moderation.php?action=ban_user', 'Ban user', '{user_id, ban, reason}'],
        ],
        
        'Batch' => [
            ['POST /batch.php', 'Batch requests', '{requests: [{url: ...}]} — max 5 parallel'],
        ],
        
        'Polls' => [
            ['POST /polls.php?action=create', 'Create poll', '{post_id, question, options[], expires_hours}'],
            ['POST /polls.php?action=vote', 'Vote on poll', '{poll_id, option_id}'],
            ['GET /polls.php?action=results', 'Poll results', '?post_id=X'],
        ],
        'Cron' => [
            ['GET /cron-master.php?key=ss_master_cron', 'Master cron (all tasks)', 'Every 5 min'],
            ['GET /cron-subscription.php?key=ss_sub_cron', 'Auto-renew subscriptions', 'Daily'],
            ['GET /cron-cleanup.php?key=ss_cleanup_cron', 'Cleanup', 'Every 6h'],
            ['GET /cron-publish.php?key=ss_pub_cron', 'Publish scheduled', 'Every 5 min'],
        ],
        'Monitoring' => [
            ['GET /health-monitor.php?key=ss_health_key', 'Health check', '6 auto-checks'],
            ['GET /infra-status.php?key=ss_infra_key', 'Infrastructure', 'Module status + capacity'],
            ['GET /load-test.php?key=ss_load_test&concurrent=10', 'Load test', 'Grade A-D'],
            ['GET /cache-warm.php?key=ss_cache_warm_key', 'Cache warmer', 'Pre-warm 6 endpoints'],
        ],
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
