# SHIPPERSHOP — KẾ HOẠCH TỐI ƯU 100K USERS
# Ngày: 22/03/2026 | Session 105+

---

## HIỆN TRẠNG (VẤN ĐỀ)

| Trang | HTML | DB Queries | Response | Vấn đề |
|-------|------|-----------|----------|--------|
| Trang chủ | 112KB | 35/load | 1.66s | QUÁ NẶNG |
| Tin nhắn | 82KB | 65/load | 1.22s | QUÁ NHIỀU QUERY |
| Cộng đồng | 73KB | 64/load | 1.02s | QUÁ NHIỀU QUERY |
| Ví tiền | 30KB | 30/load | 0.46s | Nhiều query |
| Giao thông | 42KB | 21/load | 1.19s | OK |
| Mua sắm | 39KB | 7/load | 1.10s | HTML nặng |
| Bản đồ | 47KB | 7/load | ~1s | OK |

**Tổng: 257 queries cho 7 trang = THẢM HỌA khi scale**

---

## MỤC TIÊU

- Phase 1 (NGAY): 50→500 users — Tối ưu code, cache API
- Phase 2 (1 tuần): 500→5,000 users — VPS + Redis + CDN  
- Phase 3 (1 tháng): 5K→100K users — Cloud + Load Balancer + DB Replica

---

## PHASE 1: TỐI ƯU CODE (Làm ngay)

### 1.1 Feed Cache Layer (posts.php)
- Cache feed result 30s cho mỗi combination sort+page
- Giảm 35 queries → 1 cache hit + 1 DB query nếu miss
- Cache key: `feed_{sort}_{page}_{province}_{district}`

### 1.2 Messages Optimization (messages-api.php)
- Batch load conversations + last message trong 1 query
- Cache conversation list 15s
- Giảm 65 queries → 3-5 queries

### 1.3 Groups Optimization (groups.php)
- Single query JOIN cho group list + member count + post count
- Cache discover list 60s
- Giảm 64 queries → 3-5 queries

### 1.4 Wallet Optimization (wallet-api.php)
- Cache plans list (static, cache 1h)
- Cache wallet info 10s
- Giảm 30 queries → 2-3 queries

### 1.5 Response Headers
- Add Cache-Control cho static APIs
- ETag cho feed data
- Gzip đã có (LiteSpeed)

### 1.6 HTML Optimization
- Tách inline CSS/JS thành external files (browser cache)
- Lazy load images below fold
- Defer non-critical JS

---

## PHASE 2: INFRASTRUCTURE (1 tuần)

### 2.1 VPS Migration
- DigitalOcean/Vultr 4-8GB RAM
- Nginx + PHP-FPM (thay LiteSpeed shared)
- PHP OPcache enabled
- MySQL tuning (innodb_buffer_pool_size = 2GB)

### 2.2 Redis Cache
- Session storage: PHP → Redis
- API cache: File → Redis  
- Feed cache: 30s TTL
- User session: 24h TTL
- Rate limiting: Redis counter

### 2.3 Cloudflare CDN
- Free tier: DNS + CDN
- Cache static: HTML, CSS, JS, images (1 day)
- Page Rules: API bypass cache
- Auto-minify CSS/JS
- DDOS protection included

### 2.4 Database Tuning
- Enable query cache
- Optimize slow queries
- Add missing composite indexes
- Connection pooling (persistent connections)

---

## PHASE 3: SCALE 100K (1 tháng)

### 3.1 Cloud Architecture
```
[Cloudflare CDN]
     ↓
[Load Balancer (HAProxy/ALB)]
     ↓
[App Server 1] [App Server 2] [App Server 3]
     ↓              ↓              ↓
[Redis Cluster] ← shared cache + sessions
     ↓
[MySQL Primary] → [Read Replica 1] [Read Replica 2]
     ↓
[Object Storage (S3)] ← uploads, images
```

### 3.2 Auto-scaling
- Docker containers
- Kubernetes hoặc Docker Swarm
- Auto-scale PHP-FPM workers: min 10, max 100
- Health check mỗi 10s, restart if unhealthy

### 3.3 Message Queue
- RabbitMQ/Redis Queue cho:
  - Notification push
  - Email sending
  - Analytics write
  - Content moderation
- Workers process async, không block API

### 3.4 WebSocket (real-time)
- Socket.IO hoặc Ratchet PHP
- Real-time: messages, notifications, typing
- Giảm polling từ 10s → instant push
- 100K persistent connections → cần dedicated WS server

### 3.5 Database Sharding (nếu >1M users)
- Shard by user_id
- Messages: shard by conversation_id
- Posts: partition by month

---

## MONITORING & AUTO-RECOVERY

### Health Check (chạy mỗi 30s)
```
check_api → response < 2s? OK : ALERT
check_db  → connection OK? → query < 100ms? OK : SLOW
check_disk → usage < 80%? OK : ALERT
check_memory → usage < 85%? OK : ALERT
check_error_rate → < 1%? OK : ALERT
```

### Auto-Recovery
```
IF api_down > 30s → restart PHP-FPM
IF db_slow > 5s → kill long queries + alert
IF memory > 90% → clear cache + restart workers
IF disk > 85% → clean logs + old cache + alert
IF error_rate > 5% → enable maintenance mode + alert
```

### Cron Jobs (đã có 8 jobs)
- Health check: */1 minute
- Cache warm: */5 minutes (pre-cache hot feeds)
- DB cleanup: daily 3am
- Log rotation: daily 2am
- Backup: daily 1am
- Analytics aggregate: */4 hours

---

## IMPLEMENTATION ORDER

1. ✅ Cache feed API (biggest impact)
2. ✅ Optimize messages queries
3. ✅ Optimize groups queries
4. ✅ Cache wallet/traffic/marketplace
5. ✅ Add performance monitoring
6. □ Cloudflare setup (manual)
7. □ VPS migration (manual)
8. □ Redis installation (on VPS)
9. □ Docker containerization
10. □ Load balancer setup

