#!/bin/bash
# ShipperShop Telegram Alert — Gửi thông báo khi server có vấn đề
# Setup: tạo bot qua @BotFather, lấy token + chat_id
# Cron: */5 * * * * /var/www/shippershop/scripts/telegram-alert.sh

BOT_TOKEN="${SS_TELEGRAM_BOT:-YOUR_BOT_TOKEN}"
CHAT_ID="${SS_TELEGRAM_CHAT:-YOUR_CHAT_ID}"
SITE="https://shippershop.vn"

send_alert() {
    local msg="$1"
    curl -s -X POST "https://api.telegram.org/bot${BOT_TOKEN}/sendMessage" \
        -d "chat_id=${CHAT_ID}" \
        -d "text=🚨 ShipperShop Alert: ${msg}" \
        -d "parse_mode=HTML" > /dev/null 2>&1
}

# Check site
HTTP=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "${SITE}/api/posts.php?limit=1")
if [ "$HTTP" != "200" ]; then
    send_alert "API down! posts.php HTTP ${HTTP}"
fi

# Check other APIs
for api in "groups.php?action=discover" "wallet-api.php?action=plans"; do
    H=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "${SITE}/api/${api}")
    if [ "$H" != "200" ]; then
        send_alert "${api} HTTP ${H}"
    fi
done

# Check disk
DISK=$(df / | tail -1 | awk '{print $5}' | tr -d '%')
if [ "$DISK" -gt 90 ]; then
    send_alert "Disk ${DISK}% full!"
fi

# Check memory (VPS only)
if command -v free &> /dev/null; then
    MEM=$(free | grep Mem | awk '{printf "%d", $3/$2*100}')
    if [ "$MEM" -gt 90 ]; then
        send_alert "Memory ${MEM}% used!"
    fi
fi
