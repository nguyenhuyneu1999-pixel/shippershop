#!/bin/bash
cd ~/public_html || exit 1
cp index.html index.html.bak.$(date +%Y%m%d%H%M%S)

echo "=== NGUYÊN NHÂN GỐC ==="
echo "Dòng 618-655: HTML mồ côi từ searchOverlay bị xóa không hết"
echo "3 thẻ </div> thừa đóng nhầm container → phá vỡ bottom nav + overflow"
echo ""

# Xóa đúng 38 dòng orphan HTML (từ sau </nav> đến trước <!-- BACK TO TOP -->)
sed -i '618,655d' index.html

# Xóa CSS override cũ không cần thiết nữa
sed -i '/<style id="masterFix">/,/<\/style>/d' index.html
sed -i '/<style id="navFix">/,/<\/style>/d' index.html

echo "=== Kiểm tra ==="
echo -n "searchOverlay count (expected 1 div + 2 JS): "
grep -c 'searchOverlay' index.html

echo -n "sResults count (expected 1): "
grep -c 'id="sResults"' index.html

echo -n "Bottom nav items: "
grep -c 'mnav-item' index.html

echo -n "Total lines: "
wc -l < index.html

echo ""
echo "✅ DONE! Mở tab ẩn danh test: https://shippershop.vn"
