# ShipperShop Database Schema
## MySQL 8.x | 69 Tables | ~7 MB

---

## Core Tables

### users (719 rows)
Primary user accounts table.
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK | Auto-increment, first user = id=2 |
| fullname | VARCHAR | Display name |
| username | VARCHAR | Unique login name |
| email | VARCHAR | Unique email |
| password | VARCHAR | bcrypt hash |
| phone | VARCHAR | Phone number |
| avatar | VARCHAR | Avatar image path |
| cover_image | VARCHAR | Cover image path |
| bio | TEXT | User bio |
| shipping_company | VARCHAR | GHTK, GHN, J&T, etc |
| role | ENUM | user, admin, moderator |
| status | ENUM | active, deleted, banned |
| is_online | TINYINT | 0/1 |
| last_active | DATETIME | Last activity timestamp |
| last_login | DATETIME | |
| total_success | INT | Denormalized: SUM(likes) from posts+group_posts |
| total_posts | INT | Denormalized: COUNT posts+group_posts |
| settings | JSON | Notification preferences etc |
| banned_until | DATETIME | Null if not banned |
| ban_reason | VARCHAR(500) | |
| email_verified_at | DATETIME | |
| created_at | DATETIME | |

### posts (786 rows)
Main feed posts.
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK | |
| user_id | INT FK | |
| content | TEXT | Post body |
| type | VARCHAR | post, question, review, news, tip |
| images | JSON | Array of image URLs |
| video_url | VARCHAR | |
| province, district, ward | VARCHAR | Location |
| likes_count | INT | Denormalized from likes table |
| comments_count | INT | Denormalized from comments table |
| shares_count | INT | |
| view_count | INT | Incremented on view |
| report_count | INT | Auto-hide at 5+ |
| is_pinned | TINYINT | Admin can pin |
| edited_at | DATETIME | Null if never edited |
| status | ENUM | active, hidden, deleted |
| created_at | DATETIME | |

### comments (3047 rows)
Nested comments on posts.
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK | |
| post_id | INT FK | |
| user_id | INT FK | |
| parent_id | INT | Null for root, id for reply |
| content | TEXT | |
| likes_count | INT | |
| edited_at | DATETIME | |
| status | ENUM | active, deleted |
| created_at | DATETIME | |

---

## Interaction Tables

### likes — Post likes (real votes, synced with posts.likes_count)
### post_likes — Legacy seeded likes (do NOT use for new logic)
### comment_likes — Comment likes
### saved_posts — Bookmarked posts
### follows (2512 rows) — follower_id follows following_id
### post_reports — Report queue with reason ENUM + auto-hide
### user_blocks — Block relationships (auto-unfollows both)

---

## Messages (12 conversations, 42 messages)

### conversations
| Column | Notes |
|--------|-------|
| type | 'private' or 'group' |
| user1_id, user2_id | For private chats |
| name, avatar, description | For group chats |
| creator_id | Group creator |
| invite_link | 12-char hash |
| last_message, last_message_at | Denormalized |
| status | active, pending |
| is_muted, is_pinned | Per-conversation flags |

### conversation_members — Group chat membership with role (admin/member)
### messages — Content, type (text/image/video/file/location), file_url, reply_to_id, is_pinned, is_read

---

## Groups (14 groups, 666 posts)

### groups — name, description, avatar, cover, category, member_count, rules
### group_members — group_id, user_id, role
### group_posts — Same structure as posts but for groups
### group_post_comments — Comments on group posts
### group_post_likes, group_post_comment_likes
### group_categories (19), group_rules (18)

---

## Commerce

### marketplace_listings (8) — Products for sale
### products, orders, order_items, cart — E-commerce (unused)
### wallets (11) — balance, pin_hash, locked_until
### wallet_transactions — type (deposit/transfer_in/transfer_out), status, amount
### subscription_plans (5) — Free/Pro/VIP/Premium plans
### user_subscriptions — Active subscriptions
### coupons (3), wishlists, reviews, addresses, payment_methods

---

## Traffic & Location

### traffic_alerts (6) — Category, severity, lat/lng, expires_at
### traffic_confirms — Confirm/deny votes
### traffic_comments
### map_pins (10) — Shared map markers

---

## System Tables (new in v2)

### post_reports — Content moderation queue
### user_blocks — User block relationships
### search_history — Search query history per user
### user_sessions — Active login sessions
### email_queue — Outgoing email queue
### error_logs — Application error tracking
### page_views — Analytics page view tracking
### cron_logs — Cron job execution history
### rate_limits — Per-IP per-endpoint rate limiting
### login_attempts — Brute force protection
### audit_log — Financial operation audit trail
### csrf_tokens — CSRF token storage

---

## Indexes (21 custom indexes added)

| Table | Index | Columns |
|-------|-------|---------|
| posts | idx_user_status | user_id, status, created_at |
| posts | idx_province | province, district |
| posts | idx_sort | likes_count DESC, comments_count DESC, created_at DESC |
| comments | idx_post_status | post_id, status, created_at |
| likes | idx_post_user | post_id, user_id |
| follows | idx_pair | follower_id, following_id |
| follows | idx_rev | following_id, follower_id |
| messages | idx_conv_created | conversation_id, created_at |
| conversations | idx_u1_status | user1_id, status, last_message_at DESC |
| conversations | idx_u2_status | user2_id, status, last_message_at DESC |
| group_posts | idx_group_status | group_id, status, created_at DESC |
| notifications | idx_notif_user | user_id, created_at DESC |
| users | idx_company | shipping_company |
| users | idx_online | status, is_online, last_active DESC |
| wallet_transactions | idx_wallet_user | user_id, created_at DESC |

---

## Key Rules
- User id=1 does NOT exist. First user = id=2
- `status` is MySQL reserved word — always use backticks
- getLastInsertId() unreliable on shared hosting — use PDO direct + fallback
- likes table = real votes (synced). post_likes = seeded data (legacy)
