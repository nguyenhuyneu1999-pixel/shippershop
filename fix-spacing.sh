#!/bin/bash
cd ~/public_html || exit 1
cp index.html index.html.bak.$(date +%Y%m%d%H%M%S)

# 1. Feed background trắng - xóa khoảng xám giữa bài
sed -i 's|<div id="feed">|<div id="feed" style="background:#fff">|' index.html

# 2. Main padding 0
sed -i 's|.main-layout{grid-template-columns:1fr;padding:10px 0;gap:0;|.main-layout{grid-template-columns:1fr;padding:0;gap:0;|' index.html

# 3. Feed header margin
sed -i 's|.feed-header{background:var(--card);border:none;border-radius:0;padding:6px 0;display:flex;gap:0;align-items:center;margin-bottom:1px;flex-wrap:nowrap;}|.feed-header{background:var(--card);border:none;border-radius:0;padding:6px 0;display:flex;gap:0;align-items:center;margin-bottom:0;flex-wrap:nowrap;border-bottom:1px solid #ececec;}|' index.html

# 4. Load-more button margin
sed -i 's|.load-more{width:100%;padding:10px;background:var(--border);border:none;border-radius:4px;|.load-more{width:100%;padding:10px;background:var(--border);border:none;border-radius:0;|' index.html

echo "Done! Clear cache: Ctrl+Shift+R"
