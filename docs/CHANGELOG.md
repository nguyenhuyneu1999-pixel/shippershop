# ShipperShop Changelog

## v2.1.0 (March 21, 2026) — Sessions 13-22

### New Features
- **Stories** — 24h expiring content with fullscreen viewer, progress bars, grouped by user
- **Bookmarks & Collections** — Save posts to collections, manage saved content
- **User Verification** — Blue checkmark, request/approve/reject flow
- **Scheduled Posts & Drafts** — Schedule posts, save drafts, auto-publish via cron
- **PayOS Payment** — QR bank transfer integration + manual fallback + admin approve
- **Content Moderation** — Report posts (8 reasons), admin queue, auto-hide at 3 reports
- **Hashtags** — Trending tags, posts by hashtag, autocomplete suggest
- **Friends API** — Mutual friends, common friends, pending requests
- **Share Sheet** — Native Web Share + Zalo/Facebook/Twitter/Telegram/Email fallback
- **Dark Mode** — System preference detection, manual toggle, localStorage persist
- **PWA Install** — beforeinstallprompt capture, 7-day dismiss memory
- **FAB Menu** — Expandable quick actions (create post, sell, traffic alert)
- **Admin Analytics Charts** — Bar/sparkline/donut charts (pure CSS, no libraries)
- **Notification Polling** — 30s interval, badge update, page title, toast
- **Notification Preferences** — Toggle per type, quiet hours
- **Online Widget** — Sidebar showing online users, auto-refresh
- **Scroll-to-top + Heartbeat** — Online status tracking every 2 min
- **Report Dialog** — Report posts with reason selector + detail

### Performance
- JS Bundle: 95.9 KB (26 components minified, 31% savings vs source)
- CSS Minified: 15.5 KB (24% savings)
- Production loader: single `<script>` tag loads entire v2 stack
- Service Worker v12 with 3 cache strategies

### Infrastructure
- 6 Cron jobs (cleanup, sync, publish, offline, scheduled, stories)
- 188 automated tests (100% pass)
- Health dashboard at /api/v2/status.php
- 73 DB tables (+4 new: stories, story_views, bookmark_collections, bookmark_items)
- 28 API v2 files, 160+ endpoints

### Code Stats
- PHP API v2: 28 files, ~3,900 lines
- JS Components: 26 files, ~2,600 lines
- JS Pages: 18 files, ~2,100 lines
- Total new code: ~11,000 lines across ~120 files

## v2.0.0 (March 2026) — Full Rewrite

### Database (69 tables, was 62)
- Added 21 custom indexes across 11 tables
- Added 11 new columns (users, posts, comments)
- Created 8 new tables: post_reports, user_blocks, search_history, user_sessions, email_queue, error_logs, page_views, cron_logs
- Synced denormalized counts for 512 users

### Backend — API v2 (19 endpoints, ~3700 lines)
- **posts.php** (326): Feed (hot/new/trending/following), create/edit/delete, vote, comment, report, save, share, pin
- **messages.php** (473): Conversations, groups, send, create/rename/leave group, delete/edit msg, search, media, mute, pin
- **users.php** (294): Profile (cached), me, followers/following, blocked, suggestions, settings, follow, block, upload, delete account
- **groups.php** (314): Categories, discover, detail, posts, members, leaderboard, comments, join/leave, create, edit/delete group, ban, set role, pin post
- **notifications.php** (74): Count, list, mark read, mark all
- **search.php** (89): Global (users+posts+groups), trending hashtags, history
- **admin.php** (222): Dashboard, users, reports, deposits, errors, analytics, system, ban/unban, delete/hide post
- **wallet.php** (130): Plans, info, transactions, transfer (PIN+lock), deposit, set PIN
- **traffic.php** (60): Alerts, map data, comments
- **marketplace.php** (118): Listings (search/filter/sort), detail, create/edit/delete
- **social.php** (127): Followers, following, friends (mutual), online, suggestions, follow/unfollow with XP
- **gamification.php** (184): XP profile, levels, streaks, badges, leaderboard, achievements, daily check-in
- **content.php** (124): Content queue, scheduled posts, auto-publish management
- **push.php** (104): Push notification subscribe/unsubscribe/send
- **referrals.php** (80): Referral code gen, redeem, leaderboard, XP rewards
- **stats.php** (23): Public cached stats (users, posts, groups, online)
- **analytics.php** (34): Page view tracking
- **health.php** (29): DB health check, table count, response time
- **index.php** (46): API router + endpoint listing

### Backend — Services (8 files, 850 lines)
- **cache.php**: File-based cache (get/set/del/remember)
- **rate-limiter.php**: Per-IP per-endpoint rate limiting
- **validator.php**: Input validation + sanitization
- **upload-handler.php**: MIME check, resize, EXIF strip
- **error-handler.php**: DB error logging
- **auth-v2.php**: require_auth, optional_auth, require_admin, client_ip
- **cron/runner.php**: 4 jobs (cleanup, sync counts, auto-publish, mark offline)

### Frontend — CSS Design System (349 lines)
- 50+ components: buttons, cards, badges, chips, avatars, forms, modals, sheets, toasts, skeletons, tabs, lists, dropdowns, progress bars, tooltips
- Dark mode support (@media prefers-color-scheme)
- Utility classes: flex, spacing, text, color, size, display, overflow, border, position
- Animations: fadeIn, slideUp/Down/Left/Right, spin, pulse, bounce, shimmer
- Shipping company colors, responsive breakpoints

### Frontend — JS Core (4 files, 601 lines)
- **api.js**: Auto Bearer token, error handling, 401 redirect, upload support
- **store.js**: localStorage wrapper, login/logout, user/token management
- **ui.js**: Toast, modal, confirm, bottom sheet, loading spinner, skeleton, dropdown
- **utils.js**: esc, ago, fN, debounce, throttle, formatDate/Money, copyText, page tracking

### Frontend — JS Components (12 files, 1550 lines)
- **post-card.js**: Render post cards, like/save/share/report/edit/delete, 3-dot menu
- **comment-sheet.js**: Bottom sheet with nested comments, reply, like
- **image-viewer.js**: Lightbox, swipe, double-tap zoom, keyboard nav
- **notification-bell.js**: Bell icon, badge count, dropdown, 30s polling
- **search-overlay.js**: Global search (users+posts+groups), history, debounced
- **upload.js**: Multi-file upload, preview, validate type/size
- **video-player.js**: IntersectionObserver autoplay/pause on scroll
- **location-picker.js**: Province/District/Ward cascade API
- **gamification.js**: XP card, level progress, streak, leaderboard, badges
- **emoji-picker.js**: 4 category tabs, 150+ emoji, click-to-insert
- **post-create.js**: Create/edit post modal, image upload, emoji, location
- **user-card.js**: User cards with follow button, compact mode, skeleton

### Frontend — JS Pages (3 files, 407 lines)
- **feed.js**: Infinite scroll, pull-to-refresh, skeleton loading, filters
- **user-profile.js**: Profile header, posts/followers/following tabs
- **messages.js**: Unread count, search, media gallery, typing indicator

### Frontend — Integration
- 27 HTML pages: design-system CSS + JS core + notification bell
- 9 pages: Auto Bearer token interceptor
- Service Worker v11: Cache First (static) + Network First (API) + Stale While Revalidate (images)
- JS loader (ss-loader.js): Single-tag sequential module loading

### Assets
- 33 SVG files: 11 avatars, 6 empty states, 4 badges, 11 company logos, 1 cover
- 3 email templates: welcome, reset-password, deposit-approved
- Landing page with live stats

### Security
- 7 HTTP headers: CSP, HSTS preload, X-Frame, X-Content-Type, X-XSS, Referrer-Policy, Permissions-Policy
- XSS: esc() added to call.js, post-modal.js
- CSRF tokens for wallet operations
- Rate limiting on all write endpoints
- Upload: finfo MIME check, PHP execution blocked in uploads/
- Auth: JWT HMAC verify, 7-day expiry, banned check

### Performance
- 69 images lazy loaded across 22 pages
- CSS/JS caching: 1 week + ETag
- Gzip compression on all text content
- File-based cache for frequent queries (5min TTL)
- Preconnect hints for Google Fonts

### SEO
- robots.txt with proper Disallow rules
- sitemap.xml (static, 10 pages)
- OG meta tags on all pages
- Landing page for marketing

### Docs
- API.md: Full endpoint reference
- DATABASE.md: 69 tables documented
- CHANGELOG.md: This file

### Testing
- 146 automated tests (100% pass)
- Categories: DB, Services, API, Pages, Assets, Integrity, Security, SEO, Cron, Docs, Templates, Static
