#!/bin/bash
cd ~/public_html || exit 1
cp index.html index.html.bak.$(date +%Y%m%d%H%M%S)

# 1. Fix bottom nav - xóa </a> thừa ở Bản đồ
sed -i 's|<i class="fas fa-map-marked-alt"></i><span>Bản đồ</span></a>|<i class="fas fa-map-marked-alt"></i><span>Bản đồ</span>|' index.html

# 2. Fix FAB - bỏ inline style, để mobile.css control
sed -i 's|<div class="mnav-fab" style="width:40px;height:40px;box-shadow:0 2px 8px rgba(238,77,45,.4)"><i class="fas fa-camera" id="fabIcon" style="font-size:15px"></i></div>|<div class="mnav-fab"><i class="fas fa-camera" id="fabIcon"></i></div>|' index.html

echo "Done!"
