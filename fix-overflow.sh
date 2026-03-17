#!/bin/bash
cd ~/public_html || exit 1
cp index.html index.html.bak.$(date +%Y%m%d%H%M%S)

# 1. Remove old navFix override
sed -i '/<style id="navFix">/,/<\/style>/d' index.html

# 2. Add proper fix before </head> - fix ROOT CAUSE overflow
sed -i '/<\/head>/i\
<style id="masterFix">\
html,body{max-width:100vw!important;overflow-x:hidden!important;}\
#mobileBottomNav{left:0!important;right:0!important;width:100%!important;}\
#mobileBottomNav .mnav-fab{width:44px!important;height:44px!important;font-size:18px!important;margin-top:-18px!important;box-shadow:0 3px 10px rgba(238,77,45,.4)!important;}\
.post-card{margin-bottom:0!important;}\
#feed{background:#fff!important;}\
.post-actions{gap:4px!important;}\
.sort-btn{gap:4px!important;}\
.main-layout,.main-layout>*{max-width:100vw!important;}\
</style>' index.html

# 3. Remove duplicate searchOverlay (keep first, remove second)
# Count lines and remove the second one (around line 622)
SECOND_LINE=$(grep -n 'id="searchOverlay"' index.html | tail -1 | cut -d: -f1)
if [ ! -z "$SECOND_LINE" ] && [ "$SECOND_LINE" -gt 600 ]; then
  # Find the closing </div> of this block
  END_LINE=$(tail -n +"$SECOND_LINE" index.html | grep -n '</div>' | head -3 | tail -1 | cut -d: -f1)
  END_LINE=$((SECOND_LINE + END_LINE - 1))
  echo "Removing duplicate searchOverlay: lines $SECOND_LINE to $END_LINE"
  sed -i "${SECOND_LINE},${END_LINE}d" index.html
fi

echo "Verify:"
grep -c 'searchOverlay' index.html
echo "searchOverlay count (should be 2 - open+close of 1 block)"

echo "Done! Test in incognito"
