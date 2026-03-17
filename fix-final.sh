#!/bin/bash
cd ~/public_html || exit 1
cp index.html index.html.bak.$(date +%Y%m%d%H%M%S)

# 1. Fix FAB: handleFab phải mở modal đăng bài
sed -i "s|function handleFab(){openModal('post');}|function handleFab(){if(!CU){toast('Đăng nhập để đăng bài!','warning');setTimeout(function(){location='login.html'},1000);return;}openModal('post');}|" index.html

# 2. Xóa debug overflow script
sed -i '/<script id="debugOverflow">/,/<\/script>/d' index.html

echo "Done!"
