#!/bin/bash
cd ~/public_html || exit 1
cp index.html index.html.bak.$(date +%Y%m%d%H%M%S)

# 1. Fix sort-btn gap
sed -i 's|.sort-btn{display:flex;align-items:center;justify-content:center;gap:0;|.sort-btn{display:flex;align-items:center;justify-content:center;gap:4px;|' index.html

# 2. Fix post-actions gap
sed -i 's|.post-actions{display:flex;align-items:center;gap:0;|.post-actions{display:flex;align-items:center;gap:4px;|' index.html

# 3. Fix bottom nav - add style override to ensure 5 items show
sed -i 's|<nav id="mobileBottomNav" role="navigation" aria-label="Mobile navigation">|<nav id="mobileBottomNav" role="navigation" aria-label="Mobile navigation" style="display:flex!important;justify-content:space-around">|' index.html

# 4. Fix FAB size in mobile.css - smaller camera
sed -i 's|width: 52px;|width: 44px;|' mobile.css
sed -i 's|height: 52px;|height: 44px;|' mobile.css
sed -i 's|font-size: 22px;|font-size: 18px;|' mobile.css
sed -i 's|margin-top: -24px;|margin-top: -20px;|' mobile.css

echo "Done!"
