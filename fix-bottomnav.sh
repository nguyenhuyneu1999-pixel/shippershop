#!/bin/bash
cd ~/public_html || exit 1
cp index.html index.html.bak.$(date +%Y%m%d%H%M%S)

# 1. Remove bad inline style from nav
sed -i 's| style="display:flex!important;justify-content:space-around"||' index.html

# 2. Remove old fixOverride
sed -i '/<style id="fixOverride">/,/<\/style>/d' index.html

# 3. Add proper override before </head>
sed -i '/<\/head>/i\
<style id="navFix">\
@media(max-width:768px){\
  #mobileBottomNav{position:fixed!important;bottom:0!important;left:0!important;right:0!important;height:56px!important;background:#fff!important;border-top:1px solid #e8e8e8!important;display:flex!important;align-items:center!important;z-index:1000!important;padding:0!important;box-shadow:0 -2px 12px rgba(0,0,0,.1)!important;}\
  #mobileBottomNav .mnav-item{flex:1 1 0%!important;display:flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;height:100%!important;min-width:0!important;max-width:20%!important;text-decoration:none!important;color:#999!important;font-size:9px!important;font-weight:600!important;gap:2px!important;border:none!important;background:none!important;padding:0!important;}\
  #mobileBottomNav .mnav-item.active,#mobileBottomNav .mnav-item[data-page="index.html"]{color:var(--primary)!important;}\
  #mobileBottomNav .mnav-item i{font-size:20px!important;}\
  #mobileBottomNav .mnav-fab-wrap{overflow:visible!important;}\
  #mobileBottomNav .mnav-fab{width:44px!important;height:44px!important;background:var(--primary)!important;border-radius:50%!important;display:flex!important;align-items:center!important;justify-content:center!important;color:#fff!important;font-size:18px!important;box-shadow:0 4px 12px rgba(238,77,45,.5)!important;margin-top:-18px!important;}\
}\
.post-card{margin-bottom:0!important;}\
#feed{background:#fff!important;}\
.post-actions{gap:4px!important;}\
.sort-btn{gap:4px!important;}\
</style>' index.html

echo "Done! Open in incognito: https://shippershop.vn"
