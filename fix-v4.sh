#!/bin/bash
cd ~/public_html || exit 1
cp index.html index.html.bak.$(date +%Y%m%d%H%M%S)

# Tab-bar top: 52px → 58px
sed -i "s|\.tab-bar{display:flex;background:#fff;position:fixed;top:52px;left:0;right:0;z-index:99;padding:0;border-bottom:1px solid #f0f0f0;}|.tab-bar{display:flex;background:#fff;position:fixed;top:58px;left:0;right:0;z-index:99;padding:0;border-bottom:1px solid #f0f0f0;}|" index.html

# Mobile: top:52px → 58px, padding-top:88px → 96px
sed -i "s|\.tab-bar{top:52px;}body{padding-top:88px;}|.tab-bar{top:58px;}body{padding-top:96px;}|" index.html

echo "Done!"
