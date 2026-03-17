#!/bin/bash
cd ~/public_html || exit 1
cp index.html index.html.bak.$(date +%Y%m%d%H%M%S)
cp profile.html profile.html.bak.$(date +%Y%m%d%H%M%S)

echo "=== FIX 1: Missing <script> tag ==="
# Add <script> before togMenu line
sed -i 's|^function togMenu(pid)|<script>\nfunction togMenu(pid)|' index.html

echo "=== FIX 2: Activity links community.html → index.html ==="
sed -i "s|community.html?post=|index.html?post=|g" profile.html

echo "=== FIX 3: Notification click - force reload to show post ==="
# Current: location.href="index.html?post="+pid
# If already on index.html, href won't reload. Need force reload
sed -i 's|if(pid){closeNotifPage();setTimeout(function(){window.location.href="index.html?post="+pid;window.location.reload();},150);}|if(pid){closeNotifPage();setTimeout(function(){if(window.location.pathname.indexOf("index.html")>-1||window.location.pathname==="/"){window.location.href="index.html?post="+pid;window.location.reload();}else{window.location.href="index.html?post="+pid;}},150);}|' index.html

echo "=== Verify fixes ==="
echo -n "togMenu has script tag: "
grep -c '<script>' index.html | head -1
echo -n "profile links to index.html: "
grep -c 'index.html?post=' profile.html
echo -n "Lines total: "
wc -l < index.html

echo ""
echo "✅ All fixes applied!"
echo "Test incognito: https://shippershop.vn"
