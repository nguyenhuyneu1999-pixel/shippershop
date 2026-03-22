# SHIPPERSHOP — HƯỚNG DẪN CHUYỂN SANG VPS
# Thời gian: ~30 phút | Downtime: 0 phút

---

## BƯỚC 1: MUA VPS (10 phút)
- Vào https://contabo.com → Cloud VPS → VPS 20 → Singapore
- Chọn Ubuntu 22.04 LTS
- Thanh toán ($7/tháng)
- Nhận email: IP + root password

## BƯỚC 2: SSH VÀO VPS (2 phút)
```bash
ssh root@IP_VPS
# Nhập password từ email Contabo
```

## BƯỚC 3: CHẠY SCRIPT SETUP (15 phút — tự động)
```bash
cd /root
wget https://raw.githubusercontent.com/nguyenhuyneu1999-pixel/shippershop/main/vps-setup.sh
bash vps-setup.sh
```
Script tự động cài: Nginx + PHP 8.2 + MySQL + Redis + Clone code

## BƯỚC 4: EXPORT DB TỪ HOSTING CŨ (5 phút)
```bash
# Trên máy tính, chạy:
curl -o db-export.json "https://shippershop.vn/api/db-export.php?key=YOUR_SECRET_KEY"

# Upload lên VPS:
scp db-export.json root@IP_VPS:/root/

# Trên VPS, import:
php /var/www/shippershop/public_html/api/db-import.php /root/db-export.json
```

## BƯỚC 5: CẬP NHẬT CONFIG (2 phút)
```bash
# Trên VPS:
cd /var/www/shippershop/public_html/includes
cp config.php.vps config.php
# Sửa DB password theo credentials trong /root/shippershop-credentials.txt
```

## BƯỚC 6: COPY UPLOADS (5 phút)
```bash
# Từ máy tính, rsync uploads từ hosting cũ:
rsync -avz nhshiw2j@103.124.95.161:public_html/uploads/ root@IP_VPS:/var/www/shippershop/public_html/uploads/
# Hoặc download + upload thủ công qua FTP
```

## BƯỚC 7: TEST TRÊN IP MỚI (2 phút)
```bash
curl http://IP_VPS/api/v2/status.php
curl http://IP_VPS/api/posts.php?limit=1
# Nếu OK → tiếp bước 8
# Nếu lỗi → check logs: tail /var/log/nginx/ss_error.log
```

## BƯỚC 8: TRỎ DOMAIN (2 phút)
**Cách 1 — Qua Cloudflare (khuyên dùng, tức thì):**
- Vào Cloudflare → DNS → đổi A record shippershop.vn → IP mới
- Có hiệu lực NGAY LẬP TỨC

**Cách 2 — Qua nhà đăng ký domain:**
- Đổi A record shippershop.vn → IP mới
- Chờ 5 phút - 24 giờ DNS propagate

## BƯỚC 9: SSL (1 phút)
```bash
# Trên VPS, sau khi domain đã trỏ:
certbot --nginx -d shippershop.vn -d www.shippershop.vn
```

## BƯỚC 10: XÁC NHẬN (2 phút)
```bash
curl https://shippershop.vn/api/v2/status.php
curl https://shippershop.vn/api/posts.php?limit=1
# Check health monitor:
curl https://shippershop.vn/api/health-monitor.php?key=ss_health_key
```

---

## NẾU CÓ LỖI — QUAY VỀ HOSTING CŨ
```bash
# Đổi DNS A record về IP cũ: 103.124.95.161
# Hosting cũ vẫn chạy bình thường
# Fix lỗi trên VPS rồi trỏ lại
```

---

## SAU KHI CHUYỂN XONG
- [ ] Giữ hosting cũ thêm 1 tuần để backup
- [ ] Setup Cloudflare CDN (free)
- [ ] Monitor health-monitor.php
- [ ] Xóa db-export.php (bảo mật)
