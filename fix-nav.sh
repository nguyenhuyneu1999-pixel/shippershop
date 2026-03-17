#!/bin/bash
cd ~/public_html || exit 1
cp index.html index.html.bak.$(date +%Y%m%d%H%M%S)

# 1. Fix post card spacing - bỏ margin-bottom lớn
sed -i 's|.post-card{background:var(--card);border:none;border-radius:0;margin-bottom:1px;display:flex;transition:.1s;overflow:hidden;border-bottom:1px solid #f0f0f0;}|.post-card{background:var(--card);border:none;border-radius:0;margin-bottom:0;display:flex;transition:.1s;overflow:hidden;border-bottom:1px solid #ececec;}|' index.html

# 2. Fix feed gap
sed -i 's|gap:4px;|gap:0;|' index.html

echo "Done!"
