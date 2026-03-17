#!/bin/bash
cd ~/public_html || exit 1
cp index.html index.html.bak.$(date +%Y%m%d%H%M%S)

# 1. FAB: thu nhỏ camera + mở modal đăng bài
sed -i 's|<div class="mnav-fab" style="width:42px;height:42px"><i class="fas fa-camera" id="fabIcon" style="font-size:16px"></i></div>|<div class="mnav-fab" style="width:40px;height:40px;box-shadow:0 2px 8px rgba(238,77,45,.4)"><i class="fas fa-camera" id="fabIcon" style="font-size:15px"></i></div>|' index.html

# 2. Post spacing: sát nhau
sed -i 's|.post-card{background:var(--card);border:none;border-radius:0;margin-bottom:1px;display:flex;transition:.1s;overflow:hidden;}|.post-card{background:var(--card);border:none;border-radius:0;margin-bottom:1px;display:flex;transition:.1s;overflow:hidden;border-bottom:1px solid #f0f0f0;}|' index.html

echo "Done!"
