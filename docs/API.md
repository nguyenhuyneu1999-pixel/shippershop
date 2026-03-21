# ShipperShop API v2 Documentation

Base URL: `https://shippershop.vn/api/v2/`
Auth: Bearer JWT token in Authorization header
Index: `GET /api/v2/` — auto-lists all endpoints

## Posts (posts.php)
- `GET /` — List posts (sort, search, limit, page, province, district, type)
- `GET /?id=X` — Single post
- `GET /?action=comments&post_id=X` — Comments (nested)
- `GET /?action=edit_history&post_id=X` — Edit history
- `POST /` — Create post
- `POST /?action=edit` — Edit post (saves history)
- `POST /?action=vote` — Like/unlike
- `POST /?action=save` — Save/unsave
- `POST /?action=comment` — Add comment
- `POST /?action=vote_comment` — Like comment
- `POST /?action=delete` — Delete post
- `POST /?action=share` — Share count++
- `POST /?action=pin` — Pin/unpin (max 1/user)

## Reactions (reactions.php)
- `GET /?post_id=X` — Reaction counts + my reaction
- `GET /?action=reactors&post_id=X` — Who reacted
- `POST /?action=react` — Toggle reaction (like/love/fire/wow/sad/angry)

## Messages (messages.php)
- `GET /?action=conversations` — Chat list
- `GET /?action=messages&conversation_id=X` — Messages (supports after_id)
- `GET /?action=search_conversations&q=X` — Search conversations
- `GET /?action=search_messages&conversation_id=X&q=X` — Search messages
- `GET /?action=typing_status&conversation_id=X` — Who's typing
- `POST /?action=send` — Send message
- `POST /?action=typing` — Send typing signal
- `POST /?action=mark_read` — Mark conversation read
- `POST /?action=react` — Message reaction
- `POST /?action=forward` — Forward message
- `POST /?action=create_group` — Create group chat
- `POST /?action=delete_conversation` — Delete conversation

## Users (users.php)
- `GET /?action=me` — Current user profile
- `GET /?action=profile&id=X` — User profile
- `POST /?action=update_profile` — Update profile
- `POST /?action=upload_avatar` — Upload avatar
- `POST /?action=change_password` — Change password

## Account (account.php)
- `GET /` — Account status (verified, 2FA, deletion pending)
- `POST /?action=deactivate` — Deactivate account
- `POST /?action=reactivate` — Reactivate
- `POST /?action=delete` — Schedule deletion (30 days)
- `POST /?action=cancel_delete` — Cancel deletion
- `POST /?action=change_email` — Change email

## Auth (auth.php — v1)
- `POST /?action=login` — Login (returns JWT)
- `POST /?action=register` — Register
- `POST /?action=forgot_password` — Reset email
- `POST /?action=refresh_token` — Refresh JWT

## Two-Factor (two-factor.php)
- `GET /` — 2FA status
- `POST /?action=setup` — Generate TOTP secret + QR
- `POST /?action=verify` — Verify code + enable
- `POST /?action=disable` — Disable (requires password + code)

## Stories (stories.php)
- `GET /?action=feed` — Stories feed (grouped by user)
- `GET /?action=detail&id=X` — Single story + viewers
- `GET /?action=user&user_id=X` — User's stories
- `POST /?action=create` — Create text story
- `POST /?action=upload` — Create image story (multipart)
- `POST /?action=view` — Mark story viewed
- `POST /?action=delete` — Delete story

## Social (social.php)
- `GET /?action=followers` — My followers
- `GET /?action=following` — My following
- `GET /?action=friends` — Mutual follows
- `GET /?action=suggestions` — Suggested users
- `GET /?action=online` — Online users
- `GET /?action=blocked` — Blocked list
- `GET /?action=is_blocked&user_id=X` — Check block status
- `POST /?action=follow` — Follow/unfollow toggle
- `POST /?action=block` — Block/unblock toggle

## Friends (friends.php)
- `GET /` — Mutual friends
- `GET /?action=count` — Friend count
- `GET /?action=common&user_id=X` — Common friends
- `GET /?action=pending` — Pending (follow me but I don't follow back)
- `POST /?action=accept` — Follow back

## Groups (groups.php)
- `GET /?action=discover` — Discover groups
- `GET /?action=categories` — Categories
- `GET /?action=detail&id=X` — Group detail
- `GET /?action=posts&group_id=X` — Group posts
- `GET /?action=members&group_id=X` — Members
- `GET /?action=leaderboard&group_id=X` — Group leaderboard
- `POST /?action=create` — Create group
- `POST /?action=join` — Join/leave
- `POST /?action=post` — Create group post
- `POST /?action=like_post` — Like group post
- `POST /?action=comment` — Group comment
- `POST /?action=edit_group` — Edit group (admin)
- `POST /?action=delete_group` — Delete group (admin)

## Wallet (wallet.php)
- `GET /?action=plans` — Subscription plans
- `GET /?action=info` — Wallet info + subscription
- `GET /?action=transactions` — Transaction history
- `POST /?action=set_pin` — Set/change PIN
- `POST /?action=subscribe` — Subscribe to plan
- `POST /?action=deposit` — Deposit request

## Payment (payment.php)
- `GET /?action=check&order_code=X` — Payment status
- `GET /?action=history` — Payment history
- `POST /?action=create` — Create PayOS payment / manual transfer
- `POST /?action=webhook` — PayOS callback
- `POST /?action=admin_approve` — Admin approve manual payment

## Marketplace (marketplace.php)
- `GET /` — Listings
- `POST /` — Create listing

## Traffic (traffic.php)
- `GET /` — Active alerts
- `POST /` — Create alert
- `POST /?action=vote` — Confirm/deny
- `POST /?action=comment` — Comment

## Search (search.php)
- `GET /?q=X` — Global search (posts, users, groups)

## Hashtags (hashtags.php)
- `GET /` — Trending hashtags
- `GET /?action=posts&tag=X` — Posts by hashtag
- `GET /?action=suggest&q=X` — Autocomplete

## Mentions (mentions.php)
- `GET /?action=suggest&q=X` — User autocomplete for @mention
- `GET /?action=my_mentions` — My mentions feed
- `POST /?action=extract` — Extract + save mentions from text

## Bookmarks (bookmarks.php)
- `GET /?action=collections` — My collections
- `GET /?action=posts` — Saved posts (all or by collection)
- `POST /?action=create_collection` — Create collection
- `POST /?action=delete_collection` — Delete collection
- `POST /?action=add_to_collection` — Add post
- `POST /?action=remove_from_collection` — Remove post

## Scheduled (scheduled.php)
- `GET /?action=list` — Drafts + scheduled posts
- `POST /?action=create` — Create draft/scheduled
- `POST /?action=edit` — Edit
- `POST /?action=publish_now` — Publish immediately
- `POST /?action=delete` — Delete

## Gamification (gamification.php)
- `GET /?action=profile` — XP, level, streak
- `GET /?action=leaderboard` — Top users by XP
- `GET /?action=badges` — Badge list
- `GET /?action=achievements` — Achievements
- `POST /?action=checkin` — Daily check-in

## Notifications (notifications.php)
- `GET /` — Recent notifications
- `GET /?action=unread_count` — Unread count
- `POST /?action=mark_read` — Mark all read

## Notification Prefs (notif-prefs.php)
- `GET /` — Current preferences
- `POST /` — Update (per-type toggles + quiet hours)

## Trending (trending.php)
- `GET /?action=hot` — Hot posts (score-ranked)
- `GET /?action=rising` — Rising posts
- `GET /?action=top_users` — Top users by engagement
- `GET /?action=topics` — Trending topics

## Post Analytics (post-analytics.php)
- `GET /?post_id=X` — Single post stats
- `GET /?action=overview` — Creator dashboard

## Activity Feed (activity-feed.php)
- `GET /?action=friends` — Friends' activity
- `GET /?action=me` — My activity
- `GET /?action=author_stats&user_id=X` — Author stats

## Verification (verification.php)
- `GET /?action=status` — Verification status
- `GET /?action=verified` — Verified users list
- `POST /?action=request` — Request verification
- `POST /?action=approve` — Admin approve
- `POST /?action=reject` — Admin reject

## Export (export.php)
- `GET /` — Full data export (GDPR)
- `GET /?action=summary` — Lightweight summary

## Media (media.php)
- `GET /?action=gallery&user_id=X` — User media gallery
- `GET /?action=stats&user_id=X` — Media stats

## Moderation (moderation.php)
- `GET /?action=queue` — Report queue (admin)
- `GET /?action=reasons` — Report reasons
- `POST /?action=report` — Report post
- `POST /?action=resolve` — Resolve report (admin)

## Referrals (referrals.php)
- `GET /` — My referral code
- `GET /?action=leaderboard` — Top referrers
- `POST /?action=redeem` — Redeem code

## Webhooks (webhooks.php)
- `GET /?action=events` — Available events
- `GET /` — List registered webhooks (admin)
- `POST /?action=register` — Register webhook
- `POST /?action=delete` — Delete webhook
- `POST /?action=test` — Send test ping

## Admin (admin.php)
- `GET /?action=dashboard` — Stats overview
- `GET /?action=analytics&days=X` — Time-series data
- `GET /?action=system` — System health
- `GET /?action=users` — User list
- `GET /?action=reports` — Report queue
- `GET /?action=deposits` — Pending deposits
- `POST /?action=ban_user` — Ban user
- `POST /?action=unban_user` — Unban
- `POST /?action=approve_deposit` — Approve deposit
- `POST /?action=reject_deposit` — Reject deposit

## Logs (logs.php) — Admin
- `GET /?action=audit` — Audit logs
- `GET /?action=cron` — Cron logs
- `GET /?action=errors` — Error logs
- `GET /?action=rate_limits` — Rate limit stats
- `GET /?action=login_attempts` — Login attempts

## System
- `GET /health.php` — Quick health check
- `GET /status.php` — Comprehensive health dashboard
- `GET /stats.php` — Public cached stats
- `GET /link-preview.php?url=X` — OG metadata fetcher
- `GET /analytics.php` — Page view tracking
- `GET /index.php` — API index (auto-detect all endpoints)
