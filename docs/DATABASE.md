# ShipperShop Database Schema (v2.2 — 79 tables)

## Core
| Table | Description |
|---|---|
| users | User accounts (719+), auth, profile, verification |
| posts | Feed posts (786+), content, images, location, pinned, scheduled |
| comments | Nested comments (3047+), likes |
| likes | Post likes (backward compat) |
| post_likes | Post like records |
| post_reactions | Emoji reactions (like/love/fire/wow/sad/angry) |
| comment_likes | Comment likes |
| saved_posts | Bookmarked posts |
| follows | Follow relationships |
| friends | Friend connections |
| user_blocks | Blocked users |

## Content
| Table | Description |
|---|---|
| stories | 24h expiring stories with text/image/video |
| story_views | Story view tracking |
| post_edits | Post edit history |
| bookmark_collections | User bookmark collections |
| bookmark_items | Posts in collections |
| hashtags | Hashtag index |
| mentions | @mention tracking |
| content_queue | Scheduled auto-publish content |
| post_reports | Content reports with 8 reasons |
| activity_feed | Social activity stream |

## Groups
| Table | Description |
|---|---|
| groups | Shipper groups (14+) |
| group_members | Memberships + roles |
| group_posts | Group feed posts (666+) |
| group_post_comments | Group comments |
| group_post_likes | Group likes |
| group_post_comment_likes | Group comment likes |
| group_categories | Group categories (19) |
| group_rules | Group rules |

## Messaging
| Table | Description |
|---|---|
| conversations | Chat threads (private + group) |
| conversation_members | Group chat members |
| messages | Chat messages with reactions, read receipts |
| pinned_messages | Pinned messages |
| chat_categories | User-organized chat folders |
| chat_category_items | Chats in folders |

## Commerce
| Table | Description |
|---|---|
| products | Shop products |
| orders | Orders |
| order_items | Order line items |
| cart | Shopping cart |
| marketplace_listings | Marketplace listings (8+) |
| reviews | Product reviews |
| addresses | Shipping addresses |
| coupons | Discount coupons (3) |
| wishlists | Product wishlists |

## Finance
| Table | Description |
|---|---|
| wallets | User wallets with PIN |
| wallet_transactions | Transaction log |
| subscription_plans | 5 plans (Free/Pro/VIP/Premium) |
| user_subscriptions | Active subscriptions |
| payment_methods | Saved payment methods |
| payos_payments | PayOS payment records |

## Gamification
| Table | Description |
|---|---|
| user_badges | Earned badges |
| user_streaks | Login streaks |
| user_xp | XP history |
| referral_codes | Referral codes |
| referral_logs | Referral tracking |
| referrals | Referral relationships |

## System
| Table | Description |
|---|---|
| notifications | Push notifications |
| notification_reads | Read tracking |
| push_subscriptions | Web push subscriptions |
| settings | Key-value settings + webhook configs + notif prefs |
| map_pins | Map markers |
| traffic_alerts | Traffic alerts |
| traffic_confirms | Alert votes |
| traffic_comments | Alert comments |
| analytics_views | Page view tracking |
| marketing_analytics | Marketing data |
| social_accounts | Linked social accounts |

## Security & Logging
| Table | Description |
|---|---|
| rate_limits | API rate limiting |
| login_attempts | Login attempt tracking |
| csrf_tokens | CSRF tokens |
| audit_log | All sensitive action audit trail |
| cron_logs | Cron job execution history |
| error_logs | Application error tracking |
| page_views | Page analytics |

## Database Class (includes/db.php)
```php
$d = db();                        // Singleton
$d->fetchOne($sql, $params);     // Single row
$d->fetchAll($sql, $params);     // All rows
$d->query($sql, $params);        // INSERT/UPDATE/DELETE
$d->getConnection();              // PDO object (NOT getPdo())
$d->beginTransaction();           // Transaction start
$d->commit();                     // Commit
$d->rollback();                   // Rollback
$d->getLastInsertId();            // Last ID (use fallback SELECT MAX)
```

## Key Rules
- User id=1 does NOT exist. First user = id=2
- `status` is MySQL reserved word → always use backticks
- JWT payload key: `user_id`
- All financial ops use DB transactions (SELECT FOR UPDATE)
