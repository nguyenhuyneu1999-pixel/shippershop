#!/bin/bash
# ShipperShop Auto-Scale + Auto-Recovery
# Cron: */1 * * * * /var/www/shippershop/scripts/auto-scale.sh
# Monitors health, auto-restarts services, alerts on issues

LOG="/var/log/shippershop/auto-scale.log"
mkdir -p /var/log/shippershop

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> $LOG; }

# === 1. CHECK NGINX ===
if ! systemctl is-active --quiet nginx 2>/dev/null; then
    log "ALERT: Nginx is down! Restarting..."
    systemctl restart nginx
    log "Nginx restarted: $(systemctl is-active nginx)"
fi

# === 2. CHECK PHP-FPM ===
if ! systemctl is-active --quiet php8.2-fpm 2>/dev/null; then
    log "ALERT: PHP-FPM is down! Restarting..."
    systemctl restart php8.2-fpm
    log "PHP-FPM restarted: $(systemctl is-active php8.2-fpm)"
fi

# === 3. CHECK MYSQL ===
if ! systemctl is-active --quiet mysql 2>/dev/null; then
    log "ALERT: MySQL is down! Restarting..."
    systemctl restart mysql
    log "MySQL restarted: $(systemctl is-active mysql)"
fi

# === 4. CHECK REDIS ===
if ! systemctl is-active --quiet redis-server 2>/dev/null; then
    log "ALERT: Redis is down! Restarting..."
    systemctl restart redis-server
    log "Redis restarted: $(systemctl is-active redis-server)"
fi

# === 5. CHECK DISK SPACE ===
DISK_PCT=$(df / | tail -1 | awk '{print $5}' | tr -d '%')
if [ "$DISK_PCT" -gt 85 ]; then
    log "WARNING: Disk at ${DISK_PCT}%! Cleaning..."
    # Clean old logs
    find /var/log -name "*.gz" -mtime +7 -delete
    # Clean old cache
    find /tmp/ss_api_cache -name "*.json" -mmin +30 -delete 2>/dev/null
    find /tmp/ss_cache -name "*.cache" -mmin +30 -delete 2>/dev/null
    find /tmp/nginx_cache -type f -mmin +30 -delete 2>/dev/null
    # Clean old backups
    find /var/backups -name "ss_*.sql.gz" -mtime +14 -delete
    log "Cleanup done. Disk now: $(df / | tail -1 | awk '{print $5}')"
fi

# === 6. CHECK MEMORY ===
MEM_PCT=$(free | grep Mem | awk '{printf "%d", $3/$2*100}')
if [ "$MEM_PCT" -gt 90 ]; then
    log "WARNING: Memory at ${MEM_PCT}%! Clearing caches..."
    sync; echo 3 > /proc/sys/vm/drop_caches 2>/dev/null
    # Restart PHP-FPM to free memory
    systemctl restart php8.2-fpm
    log "Memory cleanup done. Now: $(free | grep Mem | awk '{printf "%d", $3/$2*100}')%"
fi

# === 7. CHECK API RESPONSE ===
API_CODE=$(curl -s -o /dev/null -w "%{http_code}" --max-time 5 "http://localhost/api/v2/status.php" 2>/dev/null)
if [ "$API_CODE" != "200" ]; then
    log "ALERT: API returned HTTP $API_CODE! Restarting PHP-FPM..."
    systemctl restart php8.2-fpm
    sleep 2
    API_CODE2=$(curl -s -o /dev/null -w "%{http_code}" --max-time 5 "http://localhost/api/v2/status.php" 2>/dev/null)
    log "After restart: HTTP $API_CODE2"
fi

# === 8. CHECK WEBSOCKET SERVER ===
WS_PID=$(pgrep -f "websocket-server.php" 2>/dev/null)
if [ -z "$WS_PID" ]; then
    # Only restart if websocket is expected (Redis available)
    if redis-cli ping 2>/dev/null | grep -q PONG; then
        log "WebSocket server down. Restarting..."
        nohup php /var/www/shippershop/public_html/scripts/websocket-server.php >> /var/log/shippershop/ws.log 2>&1 &
        log "WebSocket restarted: PID $!"
    fi
fi

# === 9. CHECK QUEUE WORKER ===
QW_PID=$(pgrep -f "queue-worker.php" 2>/dev/null)
if [ -z "$QW_PID" ]; then
    if redis-cli ping 2>/dev/null | grep -q PONG; then
        log "Queue worker down. Restarting..."
        nohup php /var/www/shippershop/public_html/scripts/queue-worker.php >> /var/log/shippershop/queue.log 2>&1 &
        log "Queue worker restarted: PID $!"
    fi
fi

# === 10. LOG ROTATION ===
if [ -f "$LOG" ] && [ $(wc -l < "$LOG") -gt 10000 ]; then
    tail -5000 "$LOG" > "${LOG}.tmp"
    mv "${LOG}.tmp" "$LOG"
fi
