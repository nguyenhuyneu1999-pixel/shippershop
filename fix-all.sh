#!/bin/bash
cd ~/public_html || exit 1
cp index.html index.html.bak.$(date +%Y%m%d%H%M%S)

# 1. Replace entire bottom nav block (fix broken HTML)
sed -i '/<nav id="mobileBottomNav"/,/<\/nav>/c\
  <nav id="mobileBottomNav" role="navigation" aria-label="Mobile navigation">\
    <a href="index.html" class="mnav-item" data-page="index.html"><i class="fas fa-home"></i><span>Trang chủ</span></a>\
    <a href="marketplace.html" class="mnav-item" data-page="marketplace.html"><i class="fas fa-store"></i><span class="mnav-badge" id="navCartBadge" style="display:none">0</span><span>Mua sắm</span></a>\
    <button class="mnav-item mnav-fab-wrap" onclick="handleFab()" aria-label="Đăng bài"><div class="mnav-fab"><i class="fas fa-camera" id="fabIcon"></i></div></button>\
    <a href="map.html" class="mnav-item" data-page="map.html"><i class="fas fa-map-marked-alt"></i><span>Bản đồ</span></a>\
    <a href="profile.html" class="mnav-item" data-page="profile.html" id="navProfileItem"><i class="fas fa-user" id="navProfileIcon"></i><span id="navProfileName">Tài khoản</span></a>\
  </nav>' index.html

# 2. Fix vote-col gap that got broken by gap:0 replacement  
sed -i 's|gap:2px;flex-shrink:0|gap:2px;flex-shrink:0|' index.html

# 3. Verify post-body padding
echo "Nav replaced. Checking..."
grep -c 'mobileBottomNav' ~/public_html/index.html
grep -c 'mnav-item' ~/public_html/index.html

echo "Done!"
