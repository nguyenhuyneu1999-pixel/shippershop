# SHIPPERSHOP VPS MIGRATION CHECKLIST
# Mở file này khi mua VPS, check từng bước

## TRƯỚC KHI CHUYỂN
- [ ] Backup DB: `mysqldump -u user -p db > backup.sql`
- [ ] Backup uploads: `tar czf uploads.tar.gz uploads/`
- [ ] Note DNS settings hiện tại

## TRÊN VPS MỚI (30 phút)
- [ ] `bash vps-setup.sh` (cài Nginx, PHP, MySQL, Redis)
- [ ] Import DB: `mysql -u root -p shippershop < backup.sql`
- [ ] Upload files: `scp uploads.tar.gz vps:` → extract
- [ ] Clone repo: `git clone ... /var/www/shippershop/public_html`
- [ ] Copy config: `cp includes/config.php.vps includes/config.php`
- [ ] Test: `curl http://localhost/api/posts.php?limit=1`

## ENABLE FEATURES (15 phút)
- [ ] Redis session: uncomment in config.php
- [ ] OPcache: `cp vps-config/php/opcache.ini /etc/php/8.2/fpm/conf.d/`
- [ ] WebSocket: `systemctl enable --now shippershop-ws`
- [ ] Queue worker: `systemctl enable --now shippershop-queue`
- [ ] Auto-recovery: `crontab -e` → `*/1 * * * * /var/www/.../scripts/auto-scale.sh`
- [ ] Telegram alerts: set BOT_TOKEN + CHAT_ID in telegram-alert.sh
- [ ] Quick deploy: `bash scripts/vps-quick-deploy.sh`

## DNS SWITCH (0 downtime)
- [ ] Trỏ A record → IP VPS mới
- [ ] Chờ DNS propagate (5-30 phút)
- [ ] Test: `curl https://shippershop.vn/api/posts.php?limit=1`

## VERIFY
- [ ] Health: `/api/health-monitor.php?key=ss_health_key`
- [ ] Infra: `/api/infra-status.php?key=ss_infra_key`
- [ ] Tests: `/api/test-suite.php?key=ss_test_secret&page=1`
- [ ] Load: `/api/load-test.php?key=ss_load_test&concurrent=20`

## SAU KHI ỔN ĐỊNH
- [ ] Setup Cloudflare (xem CLOUDFLARE-GUIDE.md)
- [ ] Enable Redis session in config.php
- [ ] Monitor 24h first
