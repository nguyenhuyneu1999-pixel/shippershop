#!/bin/bash
cd ~/public_html || exit 1
cp index.html index.html.bak.$(date +%Y%m%d%H%M%S)

# 1. Tab-bar top: 48px → 52px (khớp chiều cao header thực tế)
sed -i "s|\.tab-bar{display:flex;background:#fff;position:fixed;top:48px;left:0;right:0;z-index:99;padding:0;border-bottom:1px solid #f0f0f0;}|.tab-bar{display:flex;background:#fff;position:fixed;top:52px;left:0;right:0;z-index:99;padding:0;border-bottom:1px solid #f0f0f0;}|" index.html

# 2. Mobile media query: top:44px → top:52px
sed -i "s|\.tab-bar{top:44px;}body{padding-top:80px;}|.tab-bar{top:52px;}body{padding-top:88px;}|" index.html

echo "Done!"
