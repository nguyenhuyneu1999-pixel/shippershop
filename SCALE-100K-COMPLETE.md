# SHIPPERSHOP — HƯỚNG DẪN SCALE 100K USERS (HOÀN CHỈNH)
# Cập nhật: 22/03/2026 | Sessions 107-115

---

## KIẾN TRÚC HIỆN TẠI (shared hosting)

```
User → shippershop.vn (LiteSpeed)
         ├── HTML pages (187KB total, -61%)
         ├── 22 extracted JS/CSS files (292KB, browser-cached)
         ├── API → PHP → MySQL + Redis (auto-detected!)
         └── File cache + Redis cache (hybrid)
```

Capacity: 5,000-30,000 concurrent users

---

## KIẾN TRÚC VPS (1 server — $7/tháng)

```
User → Cloudflare CDN ($0)
         ├── Static: JS/CSS/images (cached at edge, <50ms)
         └── API → Nginx (rate limit + FastCGI cache)
                    ├── PHP-FPM (80 workers + OPcache)
                    ├── MySQL (1GB buffer, tuned)
                    ├── Redis (cache + session + queue)
                    ├── Queue Worker (async jobs)
                    └── WebSocket Server (real-time chat)
```

Capacity: 30,000-100,000 concurrent users

---

## KIẾN TRÚC DOCKER (scale horizontal)

```
User → Cloudflare CDN
         └── Nginx (Load Balancer)
              ├── PHP Worker 1 ──┐
              ├── PHP Worker 2 ──┼── MySQL Primary
              ├── PHP Worker 3 ──┘   └── MySQL Replica
              ├── Redis Cluster
              ├── Queue Workers ×2
              └── WebSocket Server
```

Capacity: 100,000+ concurrent users

---

## FILES VÀ MODULES ĐÃ TẠO

### Infrastructure Adapters (auto-detect)
| File | Shared Hosting | VPS |
|------|---------------|-----|
| includes/smart-cache.php | File cache | Redis |
| includes/queue-adapter.php | Sync (chạy ngay) | Async Redis |
| includes/db-router.php | Single DB | Primary+Replica |
| includes/storage-adapter.php | Local /uploads | S3/MinIO |
| includes/realtime-adapter.php | DB polling | Redis PubSub |

### Utilities
| File | Chức năng |
|------|-----------|
| includes/api-cache.php | API response cache layer |
| includes/api-error-handler.php | Clean JSON errors, logging |
| includes/request-validator.php | Input sanitize + rate limit |
| includes/image-optimizer.php | Auto resize 1200px + compress |

### Deployment
| File | Chức năng |
|------|-----------|
| docker-compose.yml | 6 services, 1 command deploy |
| vps-setup.sh | Complete VPS setup script |
| vps-config/nginx/*.conf | Nginx LB + FastCGI cache |
| vps-config/php/*.conf | PHP-FPM 80 workers + OPcache |
| vps-config/mysql/*.cnf | MySQL tuned for 100K |
| vps-config/systemd/*.service | Systemd for WS + Queue |

### Scripts
| File | Chức năng |
|------|-----------|
| scripts/websocket-server.php | Real-time chat (Ratchet) |
| scripts/queue-worker.php | Redis queue consumer |
| scripts/auto-scale.sh | Auto-recovery + monitoring |

### Monitoring
| URL | Chức năng |
|-----|-----------|
| /api/health-monitor.php?key=ss_health_key | 6 health checks |
| /api/cache-warm.php?key=ss_cache_warm_key | Pre-warm cache |
| /api/infra-status.php?key=ss_infra_key | Module status |
| /api/load-test.php?key=ss_load_test | Concurrent test |

### Extracted Files (22 total)
| JS (15) | CSS (7) |
|---------|---------|
| feed-data.js (25KB) | messages-page.css (18KB) |
| feed-comments.js (10KB) | traffic-page.css (9KB) |
| feed-notifications.js (6KB) | marketplace-page.css (9KB) |
| feed-search.js (2KB) | groups-page.css (9KB) |
| messages-core.js (12KB) | group-page.css (11KB) |
| messages-filter.js (9KB) | map-page.css (9KB) |
| messages-options.js (22KB) | wallet-page.css (7KB) |
| groups-page.js (12KB) | |
| group-detail.js (25KB) | |
| traffic-page.js (18KB) | |
| marketplace-page.js (17KB) | |
| map-page.js (26KB) | |
| wallet-page.js (11KB) | |
| profile-page.js (26KB) | |
| network-handler.js (3KB) | |
| realtime-client.js (5KB) | |

---

## LỘ TRÌNH TRIỂN KHAI

### Giai đoạn 1: Hiện tại (0-5,000 users)
- [x] Code optimize 9 pages
- [x] 22 extracted files
- [x] API cache layer
- [x] Redis auto-detected
- [ ] Setup Cloudflare (bạn làm, 30 phút, $0)

### Giai đoạn 2: Tăng trưởng (5,000-30,000 users)
- [ ] Mua VPS Contabo $7/tháng
- [ ] Chạy: bash vps-setup.sh
- [ ] Import DB + uploads
- [ ] Trỏ domain
- [ ] Thời gian: 30 phút, 0 downtime

### Giai đoạn 3: Scale (30,000-100,000 users)
- [ ] Docker compose: docker-compose up -d
- [ ] Enable WebSocket: systemctl start shippershop-ws
- [ ] Enable Queue: systemctl start shippershop-queue
- [ ] Auto-scale cron: scripts/auto-scale.sh

### Giai đoạn 4: Beyond 100K
- [ ] Thêm VPS thứ 2 ($7)
- [ ] DB Replica (tạo includes/db-replica.php)
- [ ] S3 storage (tạo includes/storage-config.php)
- [ ] Horizontal scale PHP workers

---

## CHI PHÍ THEO TỪNG MỐC

| Users | Hạ tầng | Chi phí/tháng |
|-------|---------|--------------|
| 0-5K | Shared hosting + Cloudflare | 150K + $0 |
| 5K-30K | VPS + Cloudflare | 175K + $0 |
| 30K-100K | VPS + Docker | 175K-350K |
| 100K+ | 2-3 VPS + Docker | 350K-700K |

---

## MONITORING COMMANDS

```bash
# Health check
curl -s https://shippershop.vn/api/health-monitor.php?key=ss_health_key | python3 -m json.tool

# Infrastructure status
curl -s https://shippershop.vn/api/infra-status.php?key=ss_infra_key | python3 -m json.tool

# Load test
curl -s https://shippershop.vn/api/load-test.php?key=ss_load_test&concurrent=10 | python3 -m json.tool

# Cache warm
curl -s https://shippershop.vn/api/cache-warm.php?key=ss_cache_warm_key
```
