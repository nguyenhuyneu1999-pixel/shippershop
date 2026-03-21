# ShipperShop API v2 Documentation
## Base URL: https://shippershop.vn/api/v2

---

## Authentication
All authenticated endpoints require Bearer token:
```
Authorization: Bearer <JWT_TOKEN>
```
Get token: POST /api/auth.php?action=login

---

## Posts — /posts.php

### GET / — Feed
| Param | Type | Default | Description |
|-------|------|---------|-------------|
| sort | string | hot | hot, new, trending, following |
| page | int | 1 | Page number |
| limit | int | 20 | Items per page (max 50) |
| province | string | - | Filter by province |
| district | string | - | Filter by district |
| ward | string | - | Filter by ward |
| company | string | - | Filter by shipping company |
| type | string | all | Filter by post type |
| search | string | - | Search in content |
| user_id | int | - | Filter by user |

Response: `{success, data: {posts: [], meta: {page, per_page, total, total_pages}}}`

### GET ?id=X — Single Post
Response: `{success, data: {id, content, likes_count, user_liked, user_saved, view_count, ...}}`

### GET ?action=comments&post_id=X — Comments
Response: `{success, data: [{id, content, user_name, user_avatar, likes_count, user_liked, parent_id, ...}]}`

### POST ?action=create — Create Post (Auth required)
Body: `{content, type?, province?, district?, ward?}` + multipart images[]
Rate limit: 10/hour

### POST ?action=edit — Edit Post (Owner only)
Body: `{post_id, content, type?}`

### POST ?action=delete — Delete Post (Owner/Admin)
Body: `{post_id}`

### POST ?action=vote — Like/Unlike Toggle
Body: `{post_id}`
Response: `{success, data: {score, user_vote}}`

### POST ?action=comment — Add Comment
Body: `{post_id, content, parent_id?}`
Rate limit: 30/hour

### POST ?action=vote_comment — Like Comment
Body: `{comment_id}`

### POST ?action=report — Report Post
Body: `{post_id, reason: spam|inappropriate|harassment|misinformation|other, detail?}`

### POST ?action=save — Save/Unsave Toggle
Body: `{post_id}`

### POST ?action=share — Increment Share Count
Body: `{post_id}`

### POST ?action=pin — Pin/Unpin (Admin only)
Body: `{post_id}`

---

## Messages — /messages.php

### GET ?action=conversations — List Conversations
### GET ?action=group_conversations — List Group Chats
### GET ?action=messages&conversation_id=X — Load Messages
### GET ?action=user_info&id=X — User Info for Chat
### GET ?action=online_friends — Mutual Follows Online
### GET ?action=pending_count — Pending Messages Count
### GET ?action=search_messages&conversation_id=X&q=text — Search in Chat
### GET ?action=media&conversation_id=X&type=image — Shared Media
### GET ?action=group_info&conversation_id=X — Group Details
### GET ?action=group_members&conversation_id=X — Group Members
### GET ?action=pinned_messages&conversation_id=X

### POST ?action=send — Send Message (Auth required)
Body: `{to_user_id, content}` (private) or `{group_id, content}` (group)

### POST ?action=create_group
Body: `{name, member_ids: [int], description?}`

### POST ?action=upload_message — Send File/Image/Video/Location (multipart)
### POST ?action=read — Mark Conversation Read
### POST ?action=accept — Accept Pending Conversation
### POST ?action=delete_message — Delete (sender, within 1h)
### POST ?action=edit_message — Edit (sender, within 15min)
### POST ?action=mute_conversation — Toggle Mute
### POST ?action=pin_message — Toggle Pin
### POST ?action=rename_group — Rename Group
### POST ?action=add_member — Add Member
### POST ?action=remove_member — Remove Member (Admin)
### POST ?action=leave_group — Leave Group
### POST ?action=delete_conversation — Delete Conversation

---

## Users — /users.php

### GET ?action=profile&id=X — Public Profile (cached 5min)
### GET ?action=me — Current User Info
### GET ?action=followers&user_id=X — Followers (paginated)
### GET ?action=following&user_id=X — Following (paginated)
### GET ?action=blocked — Blocked Users List
### GET ?action=suggestions — Follow Suggestions
### GET ?action=sessions — Active Sessions
### GET ?action=settings — User Settings
### GET ?action=search&q=text — Search Users

### POST ?action=update_profile
Body: `{fullname?, bio?, shipping_company?, phone?, address?, username?}`

### POST ?action=upload_avatar — Upload Avatar (multipart)
### POST ?action=upload_cover — Upload Cover (multipart)
### POST ?action=follow — Follow/Unfollow Toggle
Body: `{user_id}`

### POST ?action=block — Block User
Body: `{user_id}`

### POST ?action=unblock — Unblock User
### POST ?action=update_settings — Update Notification Settings
### POST ?action=delete_account — Delete Account (requires password)
### POST ?action=terminate_session — Kill Session

---

## Notifications — /notifications.php

### GET ?action=count — Unread Count
Response: `{success, count: int}`

### GET ?action=list — List Notifications (paginated)
### POST ?action=mark_read — Mark Single Read
### POST ?action=mark_all_read — Mark All Read

---

## Search — /search.php

### GET ?action=global&q=text — Search Users + Posts + Groups
### GET ?action=users&q=text — Search Users Only
### GET ?action=posts&q=text — Search Posts Only
### GET ?action=groups&q=text — Search Groups Only
### GET ?action=trending — Trending Hashtags
### GET ?action=history — Search History
### GET ?action=clear_history — Clear History

---

## Admin — /admin.php (Admin role required)

### GET ?action=dashboard — Stats (users, posts, messages, revenue, reports, errors)
### GET ?action=users — User List (search, filter, paginated)
### GET ?action=reports — Reported Posts (filter by status)
### GET ?action=deposits — Pending Deposit Requests
### GET ?action=errors — Error Logs
### GET ?action=analytics&days=7 — Growth Charts Data
### GET ?action=system — PHP version, DB size, disk space

### POST ?action=ban_user — Ban User
### POST ?action=unban_user — Unban User
### POST ?action=delete_post / hide_post — Content Moderation
### POST ?action=resolve_report — Resolve Report
### POST ?action=approve_deposit / reject_deposit — Wallet Admin
### POST ?action=set_role — Set User Role

---

## Wallet — /wallet.php

### GET ?action=plans — Subscription Plans (public)
### GET ?action=info — Wallet Info + Subscription + Transactions
### GET ?action=transactions — Transaction History (paginated)

### POST ?action=transfer — Transfer Money
Body: `{to_user_id, amount, pin}`

### POST ?action=deposit — Request Deposit
### POST ?action=set_pin — Set/Change PIN

---

## Traffic — /traffic.php

### GET / — List Active Alerts
### GET ?action=map_data — Alerts with Coordinates
### GET ?action=comments&alert_id=X — Alert Comments

---

## Marketplace — /marketplace.php

### GET / — List Listings (search, filter, sort, paginated)
### GET ?action=detail&id=X — Single Listing + Reviews

### POST ?action=create — Create Listing
### POST ?action=edit — Edit Listing
### POST ?action=delete — Delete Listing

---

## Other Endpoints

### GET /health.php — Health Check
### POST /analytics.php — Track Page View
### GET /api/cron-run.php?key=SECRET — Run Cron Jobs

### Auth (v1): /api/auth.php
- POST ?action=login
- POST ?action=register
- POST ?action=forgot_password
- POST ?action=reset_password
- POST ?action=change_password
- POST ?action=refresh_token

---

## Error Codes
| Code | Meaning |
|------|---------|
| 200 | Success |
| 400 | Bad Request (validation error) |
| 401 | Unauthorized (missing/invalid token) |
| 403 | Forbidden (no permission) |
| 404 | Not Found |
| 405 | Method Not Allowed |
| 429 | Rate Limited |
| 500 | Server Error |

## Rate Limits
| Endpoint | Limit |
|----------|-------|
| Login | 5 per 5 min per IP |
| Post create | 10 per hour |
| Comment | 30 per hour |
| Message send | 100 per hour |
| Wallet transfer | 5 per hour |
| Deposit request | 3 per hour |
| Listing create | 5 per hour |
