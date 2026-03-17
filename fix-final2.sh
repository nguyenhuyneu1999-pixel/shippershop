#!/bin/bash
cd ~/public_html || exit 1
cp index.html index.html.bak.$(date +%Y%m%d%H%M%S)
cp mobile.css mobile.css.bak

# 1. Restore mobile.css FAB (undo bad sed)
sed -i 's|width: 44px;|width: 52px;|' mobile.css
sed -i 's|height: 44px;|height: 52px;|' mobile.css
sed -i 's|font-size: 18px;|font-size: 22px;|' mobile.css
sed -i 's|margin-top: -20px;|margin-top: -24px;|' mobile.css

# 2. Add CSS override before </head>
sed -i '/<\/head>/i\
<style id="fixOverride">\
#mobileBottomNav{display:flex!important;justify-content:space-around!important;align-items:center!important;}\
#mobileBottomNav .mnav-item{flex:1!important;display:flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;min-width:0!important;}\
#mobileBottomNav .mnav-fab{width:44px!important;height:44px!important;font-size:18px!important;margin-top:-20px!important;}\
.post-card{margin-bottom:0!important;}\
#feed{background:#fff;}\
.post-actions{gap:4px!important;}\
.sort-btn{gap:4px!important;}\
</style>' index.html

echo "Done!"
