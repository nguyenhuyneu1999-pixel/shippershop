# ShipperShop API v2 Documentation

**Base URL:** `https://shippershop.vn/api/v2/`  
**Auth:** Bearer token in `Authorization` header  
**Format:** JSON request/response  

## Endpoints (83 files, 246+ routes)

### Content

| Endpoint | File | Description |
|---|---|---|
| `posts` | posts.php | |
| `stories` | stories.php | |
| `hashtags` | hashtags.php | |
| `scheduled` | scheduled.php | |
| `post-views` | post-views.php | |
| `content` | content.php | |

### Social

| Endpoint | File | Description |
|---|---|---|
| `social` | social.php | |
| `friends` | friends.php | |
| `mentions` | mentions.php | |
| `reactions` | reactions.php | |
| `pins` | pins.php | |

### Users

| Endpoint | File | Description |
|---|---|---|
| `users` | users.php | |
| `verification` | verification.php | |
| `profile-score` | profile-score.php | |
| `user-activity` | user-activity.php | |
| `export` | export.php | |

### Communication

| Endpoint | File | Description |
|---|---|---|
| `messages` | messages.php | |
| `msg-poll` | msg-poll.php | |
| `chat-extras` | chat-extras.php | |
| `conv-search` | conv-search.php | |
| `group-chat` | group-chat.php | |
| `notifications` | notifications.php | |
| `notif-prefs` | notif-prefs.php | |
| `push` | push.php | |

### Commerce

| Endpoint | File | Description |
|---|---|---|
| `wallet` | wallet.php | |
| `payment` | payment.php | |
| `marketplace` | marketplace.php | |

### Community

| Endpoint | File | Description |
|---|---|---|
| `groups` | groups.php | |
| `gamification` | gamification.php | |
| `traffic` | traffic.php | |
| `referrals` | referrals.php | |

### Admin

| Endpoint | File | Description |
|---|---|---|
| `admin` | admin.php | |
| `admin-batch` | admin-batch.php | |
| `moderation` | moderation.php | |
| `rate-monitor` | rate-monitor.php | |

### Media

| Endpoint | File | Description |
|---|---|---|
| `media` | media.php | |
| `upload-media` | upload-media.php | |
| `qr` | qr.php | |

### Platform

| Endpoint | File | Description |
|---|---|---|
| `search` | search.php | |
| `analytics` | analytics.php | |
| `bookmarks` | bookmarks.php | |
| `stats` | stats.php | |
| `status` | status.php | |
| `health` | health.php | |

### Other

| Endpoint | File |
|---|---|
| `account` | account.php |
| `activity-feed` | activity-feed.php |
| `admin-export` | admin-export.php |
| `admin-notes` | admin-notes.php |
| `admin-users` | admin-users.php |
| `announcements` | announcements.php |
| `badges` | badges.php |
| `batch` | batch.php |
| `calendar` | calendar.php |
| `content-queue` | content-queue.php |
| `conv-labels` | conv-labels.php |
| `dashboard-summary` | dashboard-summary.php |
| `follow-suggest` | follow-suggest.php |
| `group-admin` | group-admin.php |
| `group-settings` | group-settings.php |
| `heatmap` | heatmap.php |
| `insights` | insights.php |
| `link-preview` | link-preview.php |
| `logs` | logs.php |
| `mute` | mute.php |
| `notif-grouped` | notif-grouped.php |
| `og-tags` | og-tags.php |
| `polls` | polls.php |
| `post-analytics` | post-analytics.php |
| `preferences` | preferences.php |
| `presence` | presence.php |
| `profile-card` | profile-card.php |
| `profile-theme` | profile-theme.php |
| `recommend` | recommend.php |
| `report-analytics` | report-analytics.php |
| `reputation` | reputation.php |
| `sitemap` | sitemap.php |
| `sse` | sse.php |
| `system-config` | system-config.php |
| `templates` | templates.php |
| `trending` | trending.php |
| `two-factor` | two-factor.php |
| `webhooks` | webhooks.php |

## Authentication

```
POST /api/v2/users.php?action=login
Body: {"email": "...", "password": "..."}
Response: {"token": "JWT...", "user": {...}}
```

Use token in all subsequent requests:
```
Authorization: Bearer <token>
```

## Monitoring

| URL | Purpose |
|---|---|
| `/api/v2/` | API index (auto-detected endpoints) |
| `/api/v2/status.php` | Health dashboard (8 checks) |
| `/api/v2/health.php` | Quick health check |
| `/api/v2/stats.php` | Public cached stats |
| `/api/test-suite.php?key=ss_test_secret` | Automated test suite |
| `/api/cron-run.php?key=ss_cron_8f3a2b1c` | Cron jobs (8 jobs) |
