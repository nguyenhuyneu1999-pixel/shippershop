# SHIPPERSHOP — TỐI ƯU MIỄN PHÍ CHO 100K USERS
# Tất cả đều free, chỉ cần code + cấu hình

---

## TỔNG QUAN VẤN ĐỀ HIỆN TẠI

| Vấn đề | Chi tiết | Impact |
|--------|----------|--------|
| Inline JS/CSS 81KB | Không browser-cache được → tải lại MỌI LẦN | 🔴 CỰC LỚN |
| 30-44 DB queries/API | Mỗi request tốn 30-44 queries | 🔴 CỰC LỚN |
| Không CDN | Mọi request đều hit server trực tiếp | 🔴 LỚN |
| Session mỗi API call | PHP session lock = bottleneck | 🟡 VỪA |
| Không persistent DB | Mỗi request mở/đóng MySQL connection | 🟡 VỪA |
| HTML chưa minify | 112KB → ~70KB sau minify | 🟡 VỪA |
| Ảnh chưa lazy load | Tải hết ảnh cùng lúc | 🟡 VỪA |
| SW caching yếu | Không cache hiệu quả | 🟢 NHỎ |

---

## 10 TỐI ƯU MIỄN PHÍ (Theo thứ tự impact)

### 1. 🔴 CLOUDFLARE CDN (FREE) — Impact: 10x
- Đăng ký free tại cloudflare.com
- Trỏ DNS qua Cloudflare
- Tự động: CDN toàn cầu, DDOS protection, SSL, HTTP/2, Brotli compression
- Page Rules: Cache HTML 2h, API bypass
- Kết quả: Static files < 50ms toàn cầu, giảm 80% load server
- CHI PHÍ: $0

### 2. 🔴 TÁCH INLINE JS/CSS RA FILE NGOÀI — Impact: 5x
- index.html: 54KB JS + 27KB CSS inline → tách ra feed.js + feed.css
- messages.html: 48KB JS + 18KB CSS → tách ra messages-page.js + messages-page.css  
- Browser cache file ngoài → repeat visit chỉ tải ~30KB HTML thay vì 112KB
- CHI PHÍ: $0 (chỉ code)

### 3. 🔴 GIẢM DB QUERIES — Impact: 5x
- posts.php: 35 queries → 3 queries (JOIN + batch)
- messages-api.php: 65 queries → 5 queries
- groups.php: 64 queries → 5 queries
- wallet-api.php: 30 queries → 3 queries
- CHI PHÍ: $0 (chỉ code)

### 4. 🔴 API CACHE LAYER (ĐÃ LÀM) — Impact: 3x ✅
- Feed cache 30s, Traffic 20s, Marketplace 60s, Groups 45s
- Cache HIT: 0 DB queries, <100ms response
- ĐÃ TRIỂN KHAI

### 5. 🟡 PERSISTENT DB CONNECTION — Impact: 2x
- Thêm PDO::ATTR_PERSISTENT => true
- Giảm connection overhead từ ~50ms → 0ms
- CHI PHÍ: $0 (1 dòng code)

### 6. 🟡 BỎ SESSION CHO API READ — Impact: 2x
- GET APIs không cần session_start()
- session_start() = file lock = blocking trên shared hosting
- CHI PHÍ: $0 (bỏ 1 dòng)

### 7. 🟡 HTML MINIFICATION — Impact: 1.5x
- Minify HTML: 112KB → ~75KB (-33%)
- Minify CSS: 72KB → ~50KB
- Minify JS: đã minify bundle, legacy chưa
- CHI PHÍ: $0

### 8. 🟡 IMAGE LAZY LOADING — Impact: 1.5x
- Thêm loading="lazy" cho tất cả <img> below fold
- Defer ảnh feed → chỉ tải khi scroll đến
- CHI PHÍ: $0

### 9. 🟢 SERVICE WORKER OPTIMIZATION — Impact: 1.3x
- Cache HTML pages (stale-while-revalidate)
- Cache API responses 15s
- Offline fallback page
- CHI PHÍ: $0

### 10. 🟢 PRELOAD + PREFETCH — Impact: 1.2x
- Preload: critical CSS, main JS
- Prefetch: API data for likely next page
- Preconnect: CDN, fonts, Firebase
- CHI PHÍ: $0

---

## ƯỚC TÍNH CAPACITY SAU TỐI ƯU

| Tối ưu | Concurrent Users | Ghi chú |
|--------|-----------------|---------|
| Hiện tại | ~50-100 | Shared hosting, no cache |
| + API Cache (done) | ~200-500 | Giảm DB load 80% |
| + Cloudflare CDN | ~2,000-5,000 | Static offload, DDOS |
| + Tách inline + minify | ~3,000-8,000 | Giảm bandwidth 60% |
| + Query optimization | ~5,000-15,000 | DB handle nhiều hơn |
| + Persistent DB + no session | ~8,000-20,000 | Giảm overhead |
| TẤT CẢ MIỄN PHÍ | ~10,000-20,000 | Trên shared hosting |

**Để đạt 100K cần thêm:**
- VPS ~$5-10/tháng (DigitalOcean/Vultr)
- Redis ~$0 (self-hosted on VPS)
- Total: ~$5-10/tháng cho 50-100K users

---

## TRIỂN KHAI NGAY (Session này)

1. ✅ API Cache Layer — ĐÃ XONG
2. ⬜ Persistent DB connections
3. ⬜ Remove session_start from read APIs
4. ⬜ Optimize posts.php queries (35→3)
5. ⬜ Optimize messages-api.php queries (65→5)
6. ⬜ Tách inline JS/CSS từ index.html
7. ⬜ HTML minification script
8. ⬜ Image lazy loading
9. ⬜ Service Worker v14 optimization
10. ⬜ Hướng dẫn setup Cloudflare (free)
