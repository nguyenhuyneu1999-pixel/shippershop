# SHIPPERSHOP — HƯỚNG DẪN SETUP CLOUDFLARE (MIỄN PHÍ)
# Thời gian: 15-30 phút | Chi phí: $0

---

## TẠI SAO CẦN CLOUDFLARE?
- Cache static files (JS/CSS/images) gần user → nhanh gấp 10x
- DDOS protection miễn phí
- SSL miễn phí
- HTTP/2 tự động
- Giảm 70% load server
- Tăng capacity từ 5K → 50K+ concurrent

---

## BƯỚC 1: TẠO TÀI KHOẢN (3 phút)
1. Vào https://dash.cloudflare.com/sign-up
2. Đăng ký bằng email
3. Chọn "Free plan"

## BƯỚC 2: THÊM DOMAIN (5 phút)
1. Click "Add a Site"
2. Nhập: shippershop.vn
3. Chọn "Free" plan → Continue
4. Cloudflare sẽ scan DNS records hiện tại

## BƯỚC 3: ĐỔI NAMESERVER (5 phút)
1. Cloudflare cho bạn 2 nameservers, ví dụ:
   - ada.ns.cloudflare.com
   - bob.ns.cloudflare.com
2. Vào nhà đăng ký domain (Nhân Hòa / P.A Vietnam / etc.)
3. Đổi Nameserver → nhập 2 cái Cloudflare cho
4. Chờ 5-30 phút để DNS cập nhật

## BƯỚC 4: CẤU HÌNH DNS (3 phút)
Trong Cloudflare Dashboard → DNS:
- A record: shippershop.vn → IP server (Proxy ON - đám mây cam)
- A record: www → IP server (Proxy ON)
- Xóa các record không cần thiết

## BƯỚC 5: SSL (2 phút)
1. SSL/TLS → chọn "Full (strict)"
2. Edge Certificates → Always Use HTTPS: ON
3. Minimum TLS: 1.2

## BƯỚC 6: CACHING (5 phút)
1. Caching → Configuration:
   - Browser Cache TTL: 1 month
   - Always Online: ON
   
2. Page Rules (3 free rules):
   
   Rule 1: Cache API bypass
   - URL: shippershop.vn/api/*
   - Setting: Cache Level → Bypass
   
   Rule 2: Cache static files aggressively
   - URL: shippershop.vn/*.js
   - Setting: Cache Level → Cache Everything, Edge TTL → 7 days
   
   Rule 3: Cache images
   - URL: shippershop.vn/uploads/*
   - Setting: Cache Level → Cache Everything, Edge TTL → 30 days

## BƯỚC 7: PERFORMANCE (2 phút)
1. Speed → Optimization:
   - Auto Minify: CSS ✅, JS ✅, HTML ✅
   - Brotli: ON
   - Rocket Loader: OFF (có thể break inline JS)
   - Early Hints: ON
   - HTTP/2: ON (tự động)

## BƯỚC 8: SECURITY (2 phút)
1. Security → Settings:
   - Security Level: Medium
   - Challenge Passage: 30 minutes
   - Bot Fight Mode: ON

---

## KIỂM TRA SAU KHI SETUP:
curl -I https://shippershop.vn | grep -i "cf-\|server"
# Phải thấy: server: cloudflare, cf-cache-status: HIT/MISS

## KẾT QUẢ:
- Static files: < 50ms (thay vì 800ms)
- DDOS protection: miễn phí
- SSL: miễn phí, auto-renew
- Bandwidth server: giảm 70%
