# ShipperShop Changelog

## v2.0.0 (March 2026) — Full Rewrite

### Database (69 tables, was 62)
- Added 21 custom indexes across 11 tables (10x query speed)
- Added 11 new columns (users, posts, comments)
- Created 8 new tables: post_reports, user_blocks, search_history, user_sessions, email_queue, error_logs, page_views, cron_logs
- Synced denormalized counts for 512 users

### Backend — API v2 (11 endpoints, 1849 lines)
- **posts.php**: Feed (hot/new/trending/following), single post, comments, create/edit/delete, vote, comment (nested), report (auto-hide at 5+), save, share, pin. Block filter, company filter, province normalize.
- **messages.php**: Conversations, messages, group chats, send (PDO direct), create/rename/leave group, delete/edit message (time-limited), search in chat, media gallery, mute, pin. Block filter. JWT direct auth (no exit trap).
- **users.php**: Profile (cached 5min), me, followers/following (paginated), blocked list, suggestions (same company priority), settings, follow/unfollow (with notification), block/unblock (auto-unfollow), upload avatar/cover, update profile, delete account, terminate session.
- **notifications.php**: Count unread, list (paginated, filter by type), mark read, mark all read.
- **search.php**: Global (users+posts+groups), per-type search, trending hashtags, search history.
- **admin.php**: Dashboard stats, user management (search/filter/paginate), reports queue, deposits queue, error logs, analytics (growth charts), system info, ban/unban, delete/hide post, resolve report, approve/reject deposit, set role.
- **wallet.php**: Plans, info, transactions (paginated), transfer between users (PIN+lock), deposit request, set/change PIN.
- **traffic.php**: Alerts list, map data coordinates, comments.
- **marketplace.php**: Listings (search, filter, sort, paginated), detail with reviews, create/edit/delete.
- **analytics.php**: Page view tracking.
- **health.php**: DB check, tables count, response time.

### Backend — PHP Services (7 files, 844 lines)
- **cache.php**: File-based cache (get/set/del/remember), shared hosting compatible
- **rate-limiter.php**: Per-IP per-endpoint rate limiting via DB
- **validator.php**: Input validation (required, email, min, max, in, unique), sanitize_html, validate_image (finfo_file)
- **upload-handler.php**: Secure upload with MIME check, auto-resize, EXIF strip, safe rename
- **error-handler.php**: Error logging to DB + file fallback, exception handler, shutdown handler
- **auth-v2.php**: require_auth, optional_auth, require_admin, current_user (cached), banned check, client_ip
- **cron/runner.php**: Cleanup, sync counts, auto-publish content queue, mark offline users

### Backend — Auth Enhancements
- Forgot password (email queue with reset link)
- Reset password (token validation, 1h expiry)
- Refresh token (extend if within 2 days of expiry)
- JWT 7-day expiry enforcement

### Frontend — CSS Design System (349 lines, 21KB)
- 50+ reusable components: buttons, cards, badges, chips, avatars, forms, modals, bottom sheets, toast, skeleton loading, empty states, tabs, stats, lists, dropdowns, progress bars, tooltips
- Utility classes: flex, spacing, text, colors, display, overflow, borders, z-index, animations
- Dark mode support via CSS variables
- Shipping company color classes
- Page transition animations

### Frontend — JS Core (4 modules, 601 lines, 18KB)
- **api.js**: Fetch wrapper with auto Bearer token, 401 redirect, 429 toast, error handling
- **store.js**: localStorage wrapper for user/token, isLoggedIn, isAdmin
- **ui.js**: Toast, modal, confirm, bottom sheet, loading spinner, skeleton renderer, dropdown
- **utils.js**: esc (XSS), ago (relative time VN), fN (format number), debounce, throttle, formatDate, formatMoney, copyText, page view tracking

### Frontend — JS Components (8 components, 1158 lines, 47KB)
- **post-card.js**: Full post card renderer with avatar, meta, content (truncate+expand), images (grid), video, stats, actions, 3-dot menu (edit/delete/report/save/pin)
- **comment-sheet.js**: Bottom sheet with nested comments, like, reply, submit
- **image-viewer.js**: Fullscreen lightbox, gallery navigation, swipe, double-tap zoom, keyboard support
- **notification-bell.js**: Bell icon with unread badge, dropdown list, mark read, 30s polling
- **search-overlay.js**: Global search with live results (users+posts+groups), search history
- **upload.js**: Multi-file upload with preview, compress, validate, remove
- **video-player.js**: IntersectionObserver autoplay muted, pause when off-screen
- **location-picker.js**: Province/District/Ward cascade from open API

### Frontend — Page Integration
- Design system CSS integrated into 26 HTML pages
- JS core + components loaded on all pages
- Bearer token auto-inject interceptor on 9 key pages
- Post-card component on 4 pages (user, post-detail, group, groups)
- Upload component on 3 pages (messages, marketplace, create-group)
- Location picker on 2 pages (traffic, map)

### Assets (33 SVG files)
- 11 default avatars (color-coded)
- 6 empty state illustrations (no posts, no messages, no groups, no results, no notifications, offline)
- 4 subscription badges (free, pro, vip, premium)
- 11 shipping company logos
- 1 default cover image

### Security
- Content-Security-Policy header (restrict script/style/font/img sources)
- HSTS with preload flag
- XSS: Added _esc() to call.js and post-modal.js
- CSRF tokens for financial operations
- Rate limiting on all write endpoints
- Input validation on all API endpoints
- Upload security: finfo MIME check, safe rename, EXIF strip, PHP execution disabled in uploads/
- SQL injection: all queries use prepared statements
- JWT HMAC-SHA256 signature verification
- Banned user check on auth
- 8/8 security headers present

### Performance
- 69 images across 22 pages got loading="lazy"
- CSS/JS cache: 1 week (was 1 day)
- Image cache: 1 year
- ETag enabled
- Gzip compression for text/html/css/js/json/svg
- Preconnect for Google Fonts
- File-based query cache (30s feed, 5min profiles)
- Denormalized counts (no N+1 queries)
- 21 database indexes

### SEO & PWA
- robots.txt with proper disallow rules
- sitemap.xml with priority/changefreq
- 404 error page
- Offline fallback page
- All pages have og:title, og:description, og:image

### Cron Jobs
- Cleanup: old rate limits, login attempts, error logs, page views, search history, file cache
- Sync counts: users.total_success + total_posts
- Auto-publish: content queue scheduled items
- Mark offline: users inactive > 5 minutes

### Cleanup
- Removed 18 old fix-*.sh scripts
- Removed .bak files
- Updated .gitignore
- API documentation (docs/API.md)
- Database documentation (docs/DATABASE.md)
