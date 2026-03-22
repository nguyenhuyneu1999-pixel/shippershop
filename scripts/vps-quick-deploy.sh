#!/bin/bash
# ShipperShop — Quick VPS Deploy (run after vps-setup.sh)
# Usage: bash scripts/vps-quick-deploy.sh

set -e
echo "=== ShipperShop VPS Quick Deploy ==="

# Pull latest code
cd /var/www/shippershop/public_html
git pull origin main

# Clear caches
redis-cli SELECT 1 FLUSHDB 2>/dev/null || true
redis-cli SELECT 2 FLUSHDB 2>/dev/null || true
rm -rf /tmp/ss_api_cache /tmp/ss_c /tmp/ss_cache

# Restart PHP-FPM (pick up code changes)
systemctl restart php8.2-fpm 2>/dev/null || systemctl restart php-fpm

# Warm cache
curl -s "http://localhost/api/cache-warm.php?key=ss_cache_warm_key" > /dev/null

echo "✅ Deployed + cache cleared + warmed"
echo "Run: curl https://shippershop.vn/api/health-monitor.php?key=ss_health_key"
