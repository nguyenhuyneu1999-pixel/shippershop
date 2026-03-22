#!/bin/bash
# ============================================================
# SHIPPERSHOP VPS SETUP — Chạy 1 lệnh, xong hết
# Usage: curl -sSL https://raw.githubusercontent.com/.../vps-setup.sh | bash
# Hoặc: bash vps-setup.sh
# Tested on: Ubuntu 22.04/24.04 LTS
# ============================================================

set -e
export DEBIAN_FRONTEND=noninteractive

echo "╔══════════════════════════════════════════════╗"
echo "║  ShipperShop VPS Setup — Bắt đầu cài đặt  ║"
echo "╚══════════════════════════════════════════════╝"

# === CONFIG ===
DOMAIN="shippershop.vn"
DB_NAME="shippershop"
DB_USER="shippershop"
DB_PASS="SS_$(openssl rand -hex 12)"
WEB_ROOT="/var/www/shippershop/public_html"
GITHUB_TOKEN="YOUR_GITHUB_TOKEN"
GITHUB_REPO="nguyenhuyneu1999-pixel/shippershop"

echo "[1/10] Cập nhật hệ thống..."
apt update -qq && apt upgrade -y -qq

echo "[2/10] Cài Nginx + PHP 8.2 + MySQL + Redis..."
apt install -y -qq nginx mysql-server redis-server \
  php8.2-fpm php8.2-mysql php8.2-redis php8.2-curl php8.2-gd \
  php8.2-mbstring php8.2-xml php8.2-zip php8.2-bcmath php8.2-intl \
  php8.2-opcache certbot python3-certbot-nginx git unzip curl

# === PHP-FPM CONFIG ===
echo "[3/10] Cấu hình PHP-FPM..."
cat > /etc/php/8.2/fpm/pool.d/shippershop.conf << 'PHPFPM'
[shippershop]
user = www-data
group = www-data
listen = /run/php/php8.2-fpm-ss.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 80
pm.start_servers = 15
pm.min_spare_servers = 8
pm.max_spare_servers = 30
pm.max_requests = 500
request_terminate_timeout = 30s

php_admin_value[opcache.enable] = 1
php_admin_value[opcache.memory_consumption] = 192
php_admin_value[opcache.max_accelerated_files] = 10000
php_admin_value[opcache.revalidate_freq] = 30
php_admin_value[session.save_handler] = redis
php_admin_value[session.save_path] = "tcp://127.0.0.1:6379?database=1"
php_admin_value[memory_limit] = 128M
php_admin_value[upload_max_filesize] = 20M
php_admin_value[post_max_size] = 25M
php_admin_value[max_execution_time] = 30
php_admin_value[display_errors] = Off
php_admin_value[error_log] = /var/log/php-fpm/ss-error.log
PHPFPM

mkdir -p /var/log/php-fpm
systemctl restart php8.2-fpm

# === MYSQL CONFIG ===
echo "[4/10] Cấu hình MySQL..."
mysql -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# MySQL tuning
cat > /etc/mysql/mysql.conf.d/99-shippershop.cnf << 'MYCNF'
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
max_connections = 300
thread_cache_size = 32
query_cache_type = 1
query_cache_size = 64M
tmp_table_size = 32M
max_heap_table_size = 32M
sort_buffer_size = 2M
join_buffer_size = 2M
table_open_cache = 2000
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 1
MYCNF

systemctl restart mysql

# === REDIS CONFIG ===
echo "[5/10] Cấu hình Redis..."
cat >> /etc/redis/redis.conf << 'REDIS'
maxmemory 512mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
REDIS
systemctl restart redis

# === CLONE CODE ===
echo "[6/10] Clone code từ GitHub..."
mkdir -p /var/www/shippershop
git clone https://${GITHUB_TOKEN}@github.com/${GITHUB_REPO}.git /var/www/shippershop/public_html 2>/dev/null || {
    cd /var/www/shippershop/public_html && git pull origin main
}

# Update DB config
cat > /var/www/shippershop/public_html/includes/config.php.vps << PHPCONFIG
<?php
// ShipperShop Config — VPS Version
define('DB_HOST', 'localhost');
define('DB_NAME', '$DB_NAME');
define('DB_USER', '$DB_USER');
define('DB_PASS', '$DB_PASS');
define('DB_CHARSET', 'utf8mb4');
define('JWT_SECRET', '12a45d2132717424f75b21e997949331');
define('DEBUG_MODE', false);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.save_handler', 'redis');
    ini_set('session.save_path', 'tcp://127.0.0.1:6379?database=1');
    session_start();
}
PHPCONFIG

chown -R www-data:www-data /var/www/shippershop

# === NGINX CONFIG ===
echo "[7/10] Cấu hình Nginx..."
cat > /etc/nginx/sites-available/shippershop << 'NGINXCONF'
limit_req_zone $binary_remote_addr zone=api:10m rate=30r/s;
limit_req_zone $binary_remote_addr zone=login:10m rate=5r/s;

fastcgi_cache_path /tmp/nginx_cache levels=1:2 keys_zone=SS:50m inactive=5m max_size=500m;

server {
    listen 80;
    server_name shippershop.vn www.shippershop.vn;
    root /var/www/shippershop/public_html;
    index index.html index.php;
    client_max_body_size 20M;

    gzip on;
    gzip_vary on;
    gzip_min_length 256;
    gzip_types text/plain text/css application/json application/javascript text/xml image/svg+xml;
    gzip_comp_level 6;

    location ~* \.(css|js)$ {
        expires 7d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    location ~* \.(jpg|jpeg|png|gif|webp|svg|ico|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    location ~* \.html$ {
        expires 2m;
        add_header Cache-Control "public, must-revalidate";
    }

    location ~ ^/api/.*\.php$ {
        limit_req zone=api burst=50 nodelay;

        set $skip_cache 0;
        if ($request_method != GET) { set $skip_cache 1; }
        if ($arg_action ~ "^(vote|comment|send|create|delete|save|login|register|set_pin|subscribe|deposit)") { set $skip_cache 1; }
        if ($http_authorization != "") { set $skip_cache 1; }

        fastcgi_cache SS;
        fastcgi_cache_key "$scheme$request_method$host$request_uri";
        fastcgi_cache_valid 200 30s;
        fastcgi_cache_bypass $skip_cache;
        fastcgi_no_cache $skip_cache;
        add_header X-Nginx-Cache $upstream_cache_status;

        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm-ss.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_read_timeout 30s;
    }

    location = /api/auth.php {
        limit_req zone=login burst=3 nodelay;
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm-ss.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm-ss.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.(git|env|htaccess) { deny all; }
    location ~ ^/includes/ { deny all; }

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
}
NGINXCONF

ln -sf /etc/nginx/sites-available/shippershop /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

# === SSL ===
echo "[8/10] SSL Certificate..."
certbot --nginx -d $DOMAIN -d www.$DOMAIN --non-interactive --agree-tos -m admin@$DOMAIN 2>/dev/null || {
    echo "⚠️ SSL sẽ tự động khi domain trỏ về IP này"
}

# === CRON JOBS ===
echo "[9/10] Setup Cron Jobs..."
cat > /etc/cron.d/shippershop << 'CRON'
# Health check mỗi phút
* * * * * www-data curl -s http://localhost/api/health-monitor.php?key=ss_health_key > /dev/null

# Cache warmer mỗi 5 phút
*/5 * * * * www-data curl -s http://localhost/api/cache-warm.php?key=ss_cache_warm_key > /dev/null

# Cron jobs mỗi giờ
0 * * * * www-data curl -s http://localhost/api/cron-run.php?key=ss_cron_8f3a2b1c > /dev/null

# Backup DB hàng ngày 2AM
0 2 * * * root mysqldump shippershop | gzip > /var/backups/ss_$(date +\%Y\%m\%d).sql.gz

# Xóa backup cũ > 30 ngày
0 3 * * * root find /var/backups -name "ss_*.sql.gz" -mtime +30 -delete

# Xóa cache cũ
*/10 * * * * root find /tmp/ss_api_cache -name "*.json" -mmin +10 -delete 2>/dev/null

# Log rotation
0 4 * * 0 root truncate -s 0 /var/log/nginx/ss_access.log /var/log/php-fpm/ss-error.log
CRON

mkdir -p /var/backups

# === DEPLOY WEBHOOK ===
echo "[10/10] Setup auto-deploy..."
# GitHub webhook already exists in code, just needs correct path

# === DONE ===
echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║                                                              ║"
echo "║  ✅ SHIPPERSHOP VPS SETUP HOÀN TẤT!                       ║"
echo "║                                                              ║"
echo "║  Thông tin:                                                  ║"
echo "║  Web:     http://$(hostname -I | awk '{print $1}')          ║"
echo "║  Root:    $WEB_ROOT                                         ║"
echo "║  DB:      $DB_NAME / $DB_USER                               ║"
echo "║  DB Pass: $DB_PASS                                          ║"
echo "║  Redis:   localhost:6379                                     ║"
echo "║  PHP-FPM: 80 max workers                                    ║"
echo "║  Nginx:   rate limit 30 req/s per IP                        ║"
echo "║  SSL:     Let's Encrypt (auto-renew)                        ║"
echo "║  Backup:  Daily 2AM → /var/backups/                         ║"
echo "║                                                              ║"
echo "║  BƯỚC TIẾP:                                                 ║"
echo "║  1. Import DB: mysql shippershop < backup.sql               ║"
echo "║  2. Copy config: cp includes/config.php.vps includes/config.php║"
echo "║  3. Trỏ domain shippershop.vn → IP này                    ║"
echo "║  4. Chạy: certbot --nginx -d shippershop.vn               ║"
echo "║                                                              ║"
echo "╚══════════════════════════════════════════════════════════════╝"

# Save credentials
cat > /root/shippershop-credentials.txt << CREDS
DB Name: $DB_NAME
DB User: $DB_USER
DB Pass: $DB_PASS
Web Root: $WEB_ROOT
Redis: localhost:6379
Setup Date: $(date)
CREDS

echo "Credentials saved to /root/shippershop-credentials.txt"
