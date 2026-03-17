#!/bin/bash
cd ~/public_html || exit 1
cp index.html index.html.bak.$(date +%Y%m%d%H%M%S)

# 1. FAB: đổi icon + thành camera, thu nhỏ
sed -i 's|<div class="mnav-fab"><i class="fas fa-plus" id="fabIcon"></i></div>|<div class="mnav-fab" style="width:44px;height:44px"><i class="fas fa-camera" id="fabIcon" style="font-size:18px"></i></div>|' index.html

# 2. Ẩn create-box trên trang chủ (cả desktop)
sed -i 's|.create-box{background:var(--card);border:1px solid var(--border);border-radius:4px;padding:8px;display:flex;gap:8px;align-items:center;margin-bottom:4px;}|.create-box{display:none!important;}|' index.html

# 3. Feed header: 4 mục cân đối, không xuống dòng
sed -i 's|.feed-header{background:var(--card);border:none;border-radius:0;padding:10px;display:flex;gap:8px;align-items:center;margin-bottom:8px;flex-wrap:wrap;}|.feed-header{background:var(--card);border:none;border-radius:0;padding:8px 4px;display:flex;gap:0;align-items:center;margin-bottom:8px;flex-wrap:nowrap;}|' index.html

# 4. Sort buttons: chỉ 4 mục, đều nhau
sed -i 's|.sort-btn{display:flex;align-items:center;gap:6px;padding:8px 12px;border-radius:2px;font-size:14px;font-weight:700;cursor:pointer;border:none;background:none;color:var(--muted);}|.sort-btn{display:flex;align-items:center;justify-content:center;gap:4px;padding:8px 0;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;border:none;background:none;color:var(--muted);flex:1;white-space:nowrap;}|' index.html

# 5. Thay 5 sort buttons thành 4 mục
sed -i 's|<button class="sort-btn active" id="s-hot" onclick="setSort('\''hot'\'',this)"><i class="fas fa-fire-flame-curved" style="color:#ff4500"></i>Hot</button>|<button class="sort-btn active" id="s-hot" onclick="setSort('\''hot'\'',this)"><i class="fas fa-fire-flame-curved" style="color:#ff4500"></i> Hot</button>|' index.html

sed -i 's|<button class="sort-btn" id="s-new" onclick="setSort('\''new'\'',this)"><i class="fas fa-certificate" style="color:#0dd3bb"></i>Mới</button>|<button class="sort-btn" id="s-new" onclick="setSort('\''new'\'',this)"><i class="fas fa-certificate" style="color:#0dd3bb"></i> Mới</button>|' index.html

sed -i 's|<button class="sort-btn" id="s-top" onclick="setSort('\''top'\'',this)"><i class="fas fa-chart-line" style="color:#ffb000"></i>Top</button>|<button class="sort-btn" id="s-top" onclick="setSort('\''top'\'',this)"><i class="fas fa-arrow-trend-up" style="color:#ffb000"></i> Xu hướng</button>|' index.html

sed -i 's|<button class="sort-btn" id="s-rising" onclick="setSort('\''rising'\'',this)"><i class="fas fa-arrow-trend-up" style="color:#ff585b"></i>Đang lên</button>|<button class="sort-btn" id="s-rising" onclick="setSort('\''rising'\'',this)"><i class="fas fa-user-check" style="color:#ff585b"></i> Theo dõi</button>|' index.html

# Xóa nút Tranh cãi
sed -i 's|<button class="sort-btn" id="s-controversial" onclick="setSort('\''controversial'\'',this)"><i class="fas fa-bolt" style="color:#46d160"></i>Tranh cãi</button>||' index.html

# 6. Hội nhóm tab → link groups.html
sed -i "s|<a href=\"#\" class=\"tab-item\" onclick=\"fType('discussion');return false\"><i class=\"fas fa-users-rectangle\"></i><span>Hội nhóm</span></a>|<a href=\"groups.html\" class=\"tab-item\"><i class=\"fas fa-users-rectangle\"></i><span>Hội nhóm</span></a>|" index.html

# 7. Fix thông báo: đảm bảo navigate đến bài viết
# Thay clickNotif để mở bài viết đúng cách
sed -i 's|if(pid){closeNotifPage();setTimeout(function(){location.href="index.html?post="+pid},100);}|if(pid){closeNotifPage();setTimeout(function(){window.location.href="index.html?post="+pid;window.location.reload();},150);}|' index.html

echo "✅ Done! Tất cả lỗi đã fix."
