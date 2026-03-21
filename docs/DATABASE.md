# ShipperShop Database Documentation

**Engine:** MySQL (InnoDB)  
**Tables:** 78  
**Charset:** utf8mb4_unicode_ci  

## Table Groups

### Users & Auth
`users` (719+), `login_attempts`, `csrf_tokens`, `user_blocks`, `social_accounts`

### Content
`posts` (786+), `comments` (3047+), `likes`, `post_likes`, `comment_likes`, `saved_posts`, `post_reactions`, `post_edits`, `post_reports`, `content_queue`, `hashtags`

### Stories
`stories`, `story_views`

### Groups
`groups` (14), `group_members`, `group_posts` (666+), `group_post_comments`, `group_post_likes`, `group_post_comment_likes`, `group_categories`, `group_rules`, `group_messages`

### Messages
`conversations`, `conversation_members`, `messages`, `pinned_messages`, `typing_indicators`

### Social
`follows` (2512+), `friends`, `mentions`

### Commerce
`wallets`, `wallet_transactions`, `products`, `orders`, `order_items`, `cart`, `marketplace_listings`, `reviews`, `addresses`, `coupons`, `wishlists`, `payos_payments`

### Subscriptions
`subscription_plans` (5), `user_subscriptions`, `payment_methods`

### Gamification
`user_badges`, `user_streaks`, `user_xp`

### Notifications
`notifications`, `notification_reads`, `push_subscriptions`

### Traffic
`traffic_alerts`, `traffic_confirms`, `traffic_comments`

### Analytics & Admin
`analytics_views`, `marketing_analytics`, `page_views`, `audit_log`, `rate_limits`, `error_logs`

### Bookmarks
`bookmark_collections`, `bookmark_items`

### Pins
`pinned_posts`

### Settings
`settings`, `map_pins`, `chat_categories`, `chat_category_items`, `referral_codes`, `referral_logs`, `referrals`

## Key Relationships

- `users.id` → FK in posts, comments, likes, follows, messages, groups, wallet, etc.
- `posts.id` → FK in comments, likes, post_reactions, saved_posts, mentions, pinned_posts
- `groups.id` → FK in group_members, group_posts, group_messages
- `conversations.id` → FK in conversation_members, messages

## Important Notes

- User `id=1` does NOT exist. First real user = `id=2` (Admin)
- `status` is MySQL reserved word → always use backticks
- JWT payload key: `user_id`
- `getLastInsertId()` unreliable → fallback `SELECT MAX(id)`
- `db()->getConnection()` for PDO (NOT `getPdo()`)
