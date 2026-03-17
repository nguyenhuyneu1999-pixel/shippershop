#!/bin/bash
cd ~/public_html || exit 1
cp index.html index.html.bak.$(date +%Y%m%d%H%M%S)

# 1. top-nav: sticky → fixed, liền mạch không border
sed -i "s|\.top-nav{background:#fff;position:sticky;top:0;z-index:100;padding:8px 10px;display:flex;align-items:center;gap:8px;border-bottom:1px solid #f0f0f0;}|.top-nav{background:#fff;position:fixed;top:0;left:0;right:0;z-index:100;padding:8px 10px;display:flex;align-items:center;gap:8px;}|" index.html

# 2. tab-bar: sticky → fixed, không border
sed -i "s|\.tab-bar{display:flex;background:#fff;position:sticky;top:48px;z-index:99;padding:0;}|.tab-bar{display:flex;background:#fff;position:fixed;top:48px;left:0;right:0;z-index:99;padding:0;border-bottom:1px solid #f0f0f0;}|" index.html

# 3. tab-bar mobile: update top value
sed -i "s|\.tab-bar{top:44px;}|.tab-bar{top:44px;}body{padding-top:80px;}|" index.html

# 4. post-card: bỏ border, bỏ border-radius → liền mạch như Facebook
sed -i "s|\.post-card{background:var(--card);border:1px solid var(--border);border-radius:4px;margin-bottom:4px;display:flex;transition:\.1s;overflow:hidden;}|.post-card{background:var(--card);border:none;border-radius:0;margin-bottom:8px;display:flex;transition:.1s;overflow:hidden;}|" index.html

# 5. post-card:hover: bỏ border change
sed -i "s|\.post-card:hover{border-color:#818384;}|.post-card:hover{background:#fafafa;}|" index.html

# 6. feed-header: bỏ border, bỏ border-radius
sed -i "s|\.feed-header{background:var(--card);border:1px solid var(--border);border-radius:4px;padding:10px;display:flex;gap:8px;align-items:center;margin-bottom:10px;flex-wrap:wrap;}|.feed-header{background:var(--card);border:none;border-radius:0;padding:10px;display:flex;gap:8px;align-items:center;margin-bottom:8px;flex-wrap:wrap;}|" index.html

echo "Done! Check https://shippershop.vn"
