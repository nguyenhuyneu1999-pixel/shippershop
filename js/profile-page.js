// ShipperShop profile.html — extracted for browser caching

// --- block 16398B ---

        // ==========================================
        // PROFILE PAGE JAVASCRIPT v6
        // ==========================================
        let currentUser = null;

        function checkAuth() {
            var u = localStorage.getItem('user');
            if (!u) { window.location.href = 'login.html'; return false; }
            return true;
        }

        async function loadUserProfile() {
            if (!checkAuth()) return;
            try {
                var r = await fetch('/api/auth.php?action=me', {
                    headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') }
                });
                var text = await r.text();
                var data;
                try { data = JSON.parse(text); } catch(e) {
                    // console.error('JSON parse error:', text.substring(0,200));
                    var user = JSON.parse(localStorage.getItem('user'));
                    displayUserInfo(user);
                    return;
                }
                if (data.success) {
                    currentUser = data.data;
                    displayUserInfo(currentUser);
                    loadStats(currentUser.stats);
                } else {
                    localStorage.removeItem('user');
                    localStorage.removeItem('token');
                    window.location.href = 'login.html';
                }
            } catch (error) {
                // console.error('Load profile error:', error);
                var user = JSON.parse(localStorage.getItem('user'));
                if (user) displayUserInfo(user);
            }
        }

        function displayUserInfo(user) {
            if (!user) return;
            var avatarEl = document.getElementById('userAvatar');
            if (avatarEl) {
                if (user.avatar) {
                    avatarEl.innerHTML = '<img src="' + user.avatar + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%" onerror="this.parentElement.textContent=\'' + (user.fullname||'U').charAt(0).toUpperCase() + '\'"><div style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,.5);color:#fff;font-size:10px;text-align:center;padding:2px 0"><i class="fas fa-camera"></i></div>';
                } else {
                    avatarEl.innerHTML = (user.fullname||'U').charAt(0).toUpperCase() + '<div style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,.5);color:#fff;font-size:10px;text-align:center;padding:2px 0"><i class="fas fa-camera"></i></div>';
                }
            }
            var el;
            el = document.getElementById('userName'); if (el) el.textContent = user.fullname || 'User';
            el = document.getElementById('userEmail'); if (el) el.textContent = user.username ? '@' + user.username : (user.email || '');
            el = document.getElementById('welcomeName'); if (el) el.textContent = user.fullname || 'bạn';
            el = document.getElementById('profileFullname'); if (el) el.value = user.fullname || '';
            el = document.getElementById('profileEmail'); if (el) el.value = user.email || '';
            el = document.getElementById('profilePhone'); if (el) el.value = user.phone || '';
            el = document.getElementById('profileCity'); if (el) el.value = user.city || '';
            el = document.getElementById('profileAddress'); if (el) el.value = user.address || '';
        }

        function loadStats(stats) {
            if (!stats) return;
            var el;
            el = document.getElementById('totalOrders'); if (el) el.textContent = stats.total_orders || 0;
            el = document.getElementById('totalSpent'); if (el) el.textContent = (stats.total_spent || 0).toLocaleString('vi-VN') + '₫';
            el = document.getElementById('cartItems'); if (el) el.textContent = stats.cart_items || 0;
        }

        function showTab(tabName, clickedEl) {
            document.querySelectorAll('.tab-content').forEach(function(t) { t.classList.remove('active'); });
            document.querySelectorAll('.sidebar-menu a').forEach(function(a) { a.classList.remove('active'); });
            var tab = document.getElementById(tabName);
            if (tab) { tab.classList.add('active'); tab.scrollIntoView({behavior:'smooth',block:'start'}); }
            if (clickedEl) clickedEl.classList.add('active');
            if (tabName === 'orders') loadOrders();
            if (tabName === 'activity') location.href='activity-log.html';
        }

        // UPLOAD AVATAR
        async function uploadAvatar(input) {
            if (!input.files || !input.files[0]) return;
            var file = input.files[0];
            if (file.size > 5*1024*1024) { alert('Ảnh tối đa 5MB'); return; }
            var allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            if (allowed.indexOf(file.type) === -1) { alert('Chỉ chấp nhận JPG, PNG, GIF, WebP'); return; }
            var fd = new FormData();
            fd.append('avatar', file);
            try {
                var r = await fetch('/api/auth.php?action=upload_avatar', {
                    method: 'POST',
                    headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') },
                    body: fd
                });
                var text = await r.text();
                var d;
                try { d = JSON.parse(text); } catch(e) { alert('Lỗi server: ' + text.substring(0,300)); return; }
                if (d.success && d.data && d.data.avatar) {
                    var user = JSON.parse(localStorage.getItem('user'));
                    user.avatar = d.data.avatar;
                    localStorage.setItem('user', JSON.stringify(user));
                    displayUserInfo(user);
                    alert('✅ Đã cập nhật ảnh đại diện!');
                } else {
                    alert('❌ ' + (d.message || 'Lỗi upload'));
                }
            } catch(e) { alert('Lỗi kết nối: ' + e.message); }
        }

        // ACTIVITY FEED (Facebook-style)
        async function loadActivity(tab) {
            var user = JSON.parse(localStorage.getItem('user'));
            if (!user) return;
            ['posts','saved','liked','commented'].forEach(function(t) {
                var btn = document.getElementById('actBtn' + t.charAt(0).toUpperCase() + t.slice(1));
                if (btn) {
                    if (t === tab) { btn.style.background = 'var(--primary,#7C3AED)'; btn.style.color = '#fff'; }
                    else { btn.style.background = '#e0e0e0'; btn.style.color = '#333'; }
                }
            });
            var feed = document.getElementById('activityFeed');
            if (!feed) return;
            feed.innerHTML = '<div style="text-align:center;padding:40px"><div class="loading-spinner" style="width:30px;height:30px;border:3px solid #e0e0e0;border-top-color:var(--primary,#7C3AED);border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 12px"></div>Đang tải...</div>';
            try {
                var r = await fetch('/api/user-profile.php?id=' + user.id + '&tab=' + tab, {
                    headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') }
                });
                var text = await r.text();
                var d;
                try { d = JSON.parse(text); } catch(e) { feed.innerHTML = '<p style="text-align:center;color:#f44;padding:40px">Lỗi: Server trả về không hợp lệ</p>'; return; }
                if (d.success && d.data && d.data.items && d.data.items.length > 0) {
                    var icons = {posts:'📝',saved:'🔖',liked:'❤️',commented:'💬'};
                    var labels = {posts:'Đã đăng',saved:'Đã lưu',liked:'Đã thích',commented:'Đã ghi chú'};
                    feed.innerHTML = d.data.items.map(function(p) {
                        var imgs = [];
                        try { if (p.images) imgs = JSON.parse(p.images); } catch(e) {}
                        if (!Array.isArray(imgs)) imgs = [];
                        var thumb = imgs.length > 0
                            ? '<img src="' + imgs[0] + '" style="width:64px;height:64px;border-radius:8px;object-fit:cover;flex-shrink:0" onerror="this.style.display=\'none\'" loading=\"lazy\">'
                            : '<div style="width:64px;height:64px;border-radius:8px;background:linear-gradient(135deg,#e8e8e8,#f5f5f5);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:24px">' + (icons[tab]||'📄') + '</div>';
                        var content = (p.content||'').substring(0, 140);
                        var timeAgo = p.created_at ? new Date(p.created_at).toLocaleDateString('vi-VN') : '';
                        return '<a href="post-detail.html?id=' + p.id + '" style="display:flex;gap:12px;padding:14px;background:#fff;border-radius:12px;margin-bottom:8px;text-decoration:none;color:#333;box-shadow:0 1px 3px rgba(0,0,0,.06)">'
                            + thumb
                            + '<div style="flex:1;min-width:0">'
                            + '<div style="font-size:11px;color:var(--primary,#7C3AED);margin-bottom:4px;font-weight:600">' + (icons[tab]||'') + ' ' + (labels[tab]||'') + '</div>'
                            + '<p style="font-size:13px;line-height:1.5;margin:0 0 6px;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">' + content + '</p>'
                            + '<div style="font-size:11px;color:#999;display:flex;gap:10px"><span>❤ ' + (p.likes_count||0) + '</span><span>💬 ' + (p.comments_count||0) + '</span><span>' + timeAgo + '</span></div>'
                            + '</div></a>';
                    }).join('');
                } else {
                    var emptyIcons = {posts:'📝',saved:'🔖',liked:'❤️',commented:'💬'};
                    var emptyMsgs = {posts:'Chưa đăng bài viết nào',saved:'Chưa lưu bài viết nào',liked:'Chưa thích bài viết nào',commented:'Chưa bình luận bài nào'};
                    feed.innerHTML = '<div style="text-align:center;padding:50px 20px"><div style="font-size:48px;margin-bottom:12px">' + (emptyIcons[tab]||'📄') + '</div><p style="color:#999;font-size:14px">' + (emptyMsgs[tab]||'Không có dữ liệu') + '</p></div>';
                }
            } catch(e) {
                // console.error('Activity load error:', e);
                feed.innerHTML = '<p style="text-align:center;color:#f44;padding:40px">Lỗi kết nối: ' + e.message + '</p>';
            }
        }

        // UPDATE PROFILE
        var profileFormEl = document.getElementById('profileForm');
        if (profileFormEl) {
            profileFormEl.addEventListener('submit', async function(e) {
                e.preventDefault();
                var btn = this.querySelector('button[type=submit]');
                if (btn) { btn.disabled = true; btn.textContent = 'Đang lưu...'; }
                try {
                    var r = await fetch('/api/auth.php?action=update_profile', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + localStorage.getItem('token') },
                        body: JSON.stringify({
                            fullname: document.getElementById('profileFullname').value,
                            phone: document.getElementById('profilePhone').value,
                            city: (document.getElementById('profileCity')||{}).value || '',
                            address: (document.getElementById('profileAddress')||{}).value || ''
                        })
                    });
                    var d = await r.json();
                    if (d.success) {
                        var user = JSON.parse(localStorage.getItem('user'));
                        user.fullname = document.getElementById('profileFullname').value;
                        user.phone = document.getElementById('profilePhone').value;
                        localStorage.setItem('user', JSON.stringify(user));
                        displayUserInfo(user);
                        alert('✅ Cập nhật thành công!');
                    } else { alert('❌ ' + (d.message||'Lỗi')); }
                } catch(e) { alert('Lỗi kết nối'); }
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Lưu thay đổi'; }
            });
        }

        // CHANGE PASSWORD
        var pwFormEl = document.getElementById('passwordForm');
        if (pwFormEl) {
            pwFormEl.addEventListener('submit', async function(e) {
                e.preventDefault();
                var curPw = document.getElementById('currentPassword').value;
                var newPw = document.getElementById('newPassword').value;
                var cfmPw = document.getElementById('confirmNewPassword').value;
                if (!curPw || !newPw) { alert('Vui lòng nhập đầy đủ'); return; }
                if (newPw !== cfmPw) { alert('Mật khẩu mới không khớp!'); return; }
                if (newPw.length < 6) { alert('Mật khẩu mới tối thiểu 6 ký tự'); return; }
                var btn = this.querySelector('button[type=submit]');
                if (btn) { btn.disabled = true; btn.textContent = 'Đang xử lý...'; }
                try {
                    var r = await fetch('/api/auth.php?action=change_password', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + localStorage.getItem('token') },
                        body: JSON.stringify({ old_password: curPw, new_password: newPw })
                    });
                    var text = await r.text();
                    var d;
                    try { d = JSON.parse(text); } catch(pe) { alert('Lỗi server: ' + text.substring(0,200)); return; }
                    if (d.success) {
                        alert('✅ Đổi mật khẩu thành công!');
                        this.reset();
                    } else {
                        alert('❌ ' + (d.message || 'Mật khẩu cũ không đúng'));
                    }
                } catch(e) { alert('Lỗi kết nối: ' + e.message); }
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-key"></i> Đổi mật khẩu'; }
            });
        }

        // LOAD ORDERS
        async function loadOrders() {
            var c = document.getElementById('ordersList');
            if (!c) return;
            c.innerHTML = '<p style="text-align:center;padding:40px;color:#999">Đang tải...</p>';
            try {
                var r = await fetch('/api/orders.php', { headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') } });
                var d = await r.json();
                if (d.success && d.data && d.data.orders && d.data.orders.length > 0) {
                    c.innerHTML = d.data.orders.map(function(o) {
                        return '<div style="background:#fff;border-radius:10px;padding:16px;margin-bottom:10px;box-shadow:0 1px 3px rgba(0,0,0,.06)"><div style="display:flex;justify-content:space-between;margin-bottom:8px"><strong>#' + o.order_number + '</strong><span style="color:var(--primary);font-size:13px">' + o.status + '</span></div><div style="color:#666;font-size:14px">' + ((o.total||0).toLocaleString('vi-VN')) + '₫</div></div>';
                    }).join('');
                } else {
                    c.innerHTML = '<div style="text-align:center;padding:50px 20px"><div style="font-size:48px;margin-bottom:12px">🛒</div><p style="color:#999">Chưa có đơn hàng</p><a href="marketplace.html" style="display:inline-block;margin-top:12px;background:var(--primary,#7C3AED);color:#fff;padding:10px 24px;border-radius:20px;text-decoration:none">Mua sắm ngay</a></div>';
                }
            } catch(e) { c.innerHTML = '<p style="text-align:center;padding:40px;color:#999">Chưa có đơn hàng</p>'; }
        }

        function logout() {
            if (confirm('Bạn có chắc muốn đăng xuất?')) {
                localStorage.removeItem('user'); localStorage.removeItem('token');
                window.location.href = 'index.html';
            }
        }

        // INIT
        window.addEventListener('DOMContentLoaded', function() {
            loadUserProfile();
            try { if (typeof cart !== 'undefined') cart.updateUI(); } catch(e) {}
        });
    
// --- block 4212B ---

function toggleNotif(ev){
    if(ev)ev.stopPropagation();
    var p=document.getElementById('notifPanel');
    if(!p)return;
    var isOpen=p.style.display==='block';
    p.style.display=isOpen?'none':'block';
    if(!isOpen)loadNotifs();
}
document.addEventListener('click',function(e){
    var w=document.getElementById('notifWrap');
    var p=document.getElementById('notifPanel');
    if(w&&p&&!w.contains(e.target))p.style.display='none';
});

async function loadNotifs(){
    var list=document.getElementById('notifList');
    if(!list)return;
    var token=localStorage.getItem('token');
    if(!token)return;
    try{
        var r=await fetch('/api/notifications.php',{headers:{'Authorization':'Bearer '+token}});
        var d=await r.json();
        if(!d.success||!d.data||d.data.length===0){
            list.innerHTML='<div style="text-align:center;padding:40px;color:#999"><div style="font-size:36px;margin-bottom:8px">\ud83d\udd14</div>Chua co thong bao</div>';
            return;
        }
        list.innerHTML=d.data.map(function(n){
            var icon=n.type==='like'?'\u2764\ufe0f':'\ud83d\udcac';
            var label=n.type==='like'?'da thich':'da binh luan';
            var timeAgo=ntimeAgo(n.created_at);
            var bg=n.is_read?'background:#fff;':'background:#FFF0EB;';
            var av=n.actor_avatar
                ?'<img src="'+n.actor_avatar+'" style="width:40px;height:40px;border-radius:50%;object-fit:cover;flex-shrink:0" loading=\"lazy\">'
                :'<div style="width:40px;height:40px;border-radius:50%;background:#7C3AED;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0">'+((n.actor_name||'U').charAt(0))+'</div>';
            return '<div class="notif-item" data-key="'+n.notif_key+'" data-postid="'+n.post_id+'" data-read="'+(n.is_read?'1':'0')+'" style="display:flex;gap:10px;padding:10px 12px;border-radius:8px;cursor:pointer;margin-bottom:2px;'+bg+'" onclick="markRead(this)">'
                +av
                +'<div style="flex:1;min-width:0">'
                +'<div style="font-size:13px;line-height:1.4;color:#333"><b style="color:#111">'+n.actor_name+'</b> '+label+' bai viet cua ban</div>'
                +'<div style="font-size:11px;color:#7C3AED;margin-top:3px">'+icon+' '+timeAgo+'</div>'
                +'</div></div>';
        }).join('');
        updateBadge();
    }catch(e){
        list.innerHTML='<div style="text-align:center;padding:30px;color:#999">Loi tai thong bao</div>';
    }
}

function markRead(el){
    var key=el.dataset.key;
    var postId=el.dataset.postid;
    if(el.dataset.read!=='1'){
        el.dataset.read='1';
        el.style.background='#fff';
        var token=localStorage.getItem('token');
        fetch('/api/notifications.php',{
            method:'POST',
            headers:{'Authorization':'Bearer '+token,'Content-Type':'application/json'},
            body:JSON.stringify({notif_key:key})
        });
        updateBadge();
    }
    if(postId)window.location.href='post-detail.html?id='+postId;
}

function updateBadge(){
    var items=document.querySelectorAll('.notif-item[data-read="0"]');
    var badge=document.getElementById('notifBadge');
    if(!badge)return;
    if(items.length>0){badge.style.display='flex';badge.textContent=items.length>99?'99+':items.length;}
    else badge.style.display='none';
}

function ntimeAgo(d){
    var s=Math.floor((Date.now()-new Date(d).getTime())/1000);
    if(s<60)return s+' giay';
    if(s<3600)return Math.floor(s/60)+' phut';
    if(s<86400)return Math.floor(s/3600)+' gio';
    return Math.floor(s/86400)+' ngay';
}

document.addEventListener('DOMContentLoaded',function(){
    var token=localStorage.getItem('token');
    if(!token)return;
    fetch('/api/notifications.php',{headers:{'Authorization':'Bearer '+token}})
    .then(function(r){return r.json()})
    .then(function(d){
        if(d.success&&d.data){
            var unread=d.data.filter(function(n){return !n.is_read}).length;
            var badge=document.getElementById('notifBadge');
            if(badge&&unread>0){badge.style.display='flex';badge.textContent=unread>99?'99+':unread;}
        }
    }).catch(function(){});
});

// --- block 3576B ---

// Referral System
(function(){
var tk=localStorage.getItem("token");if(!tk)return;
document.getElementById("refSection").style.display="block";
fetch("/api/referral.php?action=my_code",{headers:{"Authorization":"Bearer "+tk}}).then(function(r){return r.json();}).then(function(d){
  if(d.success){
    document.getElementById("refLink").value=d.data.link;
    document.getElementById("refCount").textContent=d.data.uses_count;
  }
}).catch(function(){});
})();
function showRefCard(){var code=document.getElementById("refLink").value.split("/r/")[1];if(!code)return;var img=document.getElementById("refCard");img.src="/api/share-card.php?action=referral&code="+code;img.style.display="block";}
function copyRef(){var i=document.getElementById("refLink");i.select();document.execCommand("copy");if(typeof toast==="function")toast("Đã copy link mời!","success");else alert("Đã copy!");}

// XP & Gamification
(function(){
var tk=localStorage.getItem("token");if(!tk)return;
document.getElementById("xpSection").style.display="block";
fetch("/api/deliveries.php?action=today",{headers:{"Authorization":"Bearer "+tk}}).then(function(r){return r.json();}).then(function(d){
    if(!d.success)return;
    var p=d.data;
    document.getElementById("xpSection").style.display="block";
    document.getElementById("xpLevelName").textContent="Shipper";
    document.getElementById("xpLevel").textContent=p.month_label||"";
    document.getElementById("xpTotal").textContent=p.month||0;
    document.getElementById("xpBar").style.width=p.month_progress+"%";
    document.getElementById("xpNextInfo").textContent=p.month+" / 1.300 đơn → 100.000đ";
    document.getElementById("xpProgress").textContent=p.month_progress+"%";
    document.getElementById("xpStreak").textContent=p.today||0;
    document.getElementById("xpPosts").textContent=p.streak||0;
    var balStr=(p.balance||0).toString().replace(/\B(?=(\d{3})+(?!\d))/g,".")+"đ";
    document.getElementById("xpRefs").textContent=balStr;
    var btn=document.getElementById("checkinBtn");
    if(btn){
        if(p.today>=0){
            fetch("/api/checkin.php?action=status",{headers:{"Authorization":"Bearer "+tk}}).then(function(r2){return r2.json();}).then(function(d2){
                if(d2.data&&d2.data.checked_in){btn.textContent="✅ Đã điểm danh hôm nay";btn.disabled=true;btn.style.background="#333";btn.style.color="#888";}
            });
        }
    }
}).catch(function(){});

function doCheckin(){
var tk=localStorage.getItem("token");if(!tk)return;
var btn=document.getElementById("checkinBtn");
btn.disabled=true;btn.textContent="⏳ Đang check-in...";
fetch("/api/checkin.php?action=checkin",{method:"POST",headers:{"Authorization":"Bearer "+tk}}).then(function(r){return r.json();}).then(function(d){
  if(d.success){btn.textContent="✅ +1 đơn giao thành công!";btn.style.background="#00b14f";btn.style.color="#fff";
    var todayEl=document.getElementById("xpStreak");if(todayEl)todayEl.textContent=parseInt(todayEl.textContent||0)+1;
  }else{btn.textContent="⚠️ "+d.message;btn.style.background="#ff9800";}
}).catch(function(){btn.textContent="❌ Lỗi kết nối";});
}

function shareRef(type){var url=document.getElementById("refLink").value;var text="🏍️ Ae shipper ơi! Tham gia cộng đồng ShipperShop - tips giao hàng, cảnh báo GT real-time, chợ đồ nghề. 100% free! "+url;if(type==="zalo"){window.open("https://zalo.me/share?url="+encodeURIComponent(url)+"&title="+encodeURIComponent("Tham gia ShipperShop"));}else if(type==="fb"){window.open("https://www.facebook.com/sharer/sharer.php?u="+encodeURIComponent(url));}else{if(navigator.share)navigator.share({title:"ShipperShop",text:"Tham gia cộng đồng shipper #1 VN",url:url});else{copyRef();}}}

// --- block 1490B ---

(function(){
var VP="BK3cBLMYiJYXNIyw4zXLosWVJuhZwoaMjF7DX0JBYpJRQFHiaSClhO5Qfahv1yAH9Y7AuNVRaLb1syrF7HwC_88";
var U=JSON.parse(localStorage.getItem("user")||"null");
if(!U||!("serviceWorker" in navigator)||!("PushManager" in window))return;
function u2a(b){var p="=".repeat((4-b.length%4)%4);var r=atob(b.replace(/-/g,"+").replace(/_/g,"/")+p);var a=new Uint8Array(r.length);for(var i=0;i<r.length;i++)a[i]=r.charCodeAt(i);return a;}
navigator.serviceWorker.ready.then(function(reg){reg.pushManager.getSubscription().then(function(sub){if(sub){ss(sub);return;}if(Notification.permission==="granted"){sb(reg);}else if(Notification.permission!=="denied"){setTimeout(function(){Notification.requestPermission().then(function(p){if(p==="granted")sb(reg);});},5000);}});});
function sb(r){r.pushManager.subscribe({userVisibleOnly:true,applicationServerKey:u2a(VP)}).then(function(s){ss(s);}).catch(function(){});}
function ss(s){var k=s.getKey("p256dh"),a=s.getKey("auth");var d={endpoint:s.endpoint,keys:{p256dh:btoa(String.fromCharCode.apply(null,new Uint8Array(k))).replace(/\+/g,"-").replace(/\//g,"_").replace(/=+$/,""),auth:btoa(String.fromCharCode.apply(null,new Uint8Array(a))).replace(/\+/g,"-").replace(/\//g,"_").replace(/=+$/,"")}};var t=localStorage.getItem("token"),h={"Content-Type":"application/json"};if(t)h["Authorization"]="Bearer "+t;fetch("/api/push.php?action=subscribe",{method:"POST",headers:h,credentials:"include",body:JSON.stringify(d)}).catch(function(){});}
})();

function openChangePassword(){
  var modal=document.createElement('div');
  modal.id='pwdModal';
  modal.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:2000;display:flex;align-items:center;justify-content:center';
  modal.innerHTML='<div style="background:#fff;border-radius:12px;padding:24px;max-width:340px;width:90%"><h3 style="margin:0 0 16px;font-size:17px">Đổi mật khẩu</h3><input id="pwdCurrent" type="password" placeholder="Mật khẩu hiện tại" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;font-size:14px;margin-bottom:10px;box-sizing:border-box"><input id="pwdNew" type="password" placeholder="Mật khẩu mới (tối thiểu 6 ký tự)" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;font-size:14px;margin-bottom:10px;box-sizing:border-box"><input id="pwdConfirm" type="password" placeholder="Xác nhận mật khẩu mới" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;font-size:14px;margin-bottom:16px;box-sizing:border-box"><div style="display:flex;gap:8px;justify-content:flex-end"><button onclick="document.getElementById(\'pwdModal\').remove()" style="padding:10px 20px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer">Hủy</button><button onclick="submitChangePassword()" style="padding:10px 20px;border:none;border-radius:8px;background:#7C3AED;color:#fff;cursor:pointer;font-weight:600">Đổi</button></div></div>';
  modal.onclick=function(e){if(e.target===modal)modal.remove();};
  document.body.appendChild(modal);
}
function submitChangePassword(){
  var cur=document.getElementById('pwdCurrent').value;
  var nw=document.getElementById('pwdNew').value;
  var cf=document.getElementById('pwdConfirm').value;
  if(nw.length<6){toast('Tối thiểu 6 ký tự','error');return;}
  if(nw!==cf){toast('Mật khẩu xác nhận không khớp','error');return;}
  var token=localStorage.getItem('token');
  fetch('/api/auth.php?action=change_password',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+(token||'')},body:JSON.stringify({current_password:cur,new_password:nw})}).then(function(r){return r.json()}).then(function(d){
    if(d.success){document.getElementById('pwdModal').remove();toast('Đã đổi mật khẩu!','success');}
    else{toast(d.message||'Lỗi','error');}
  }).catch(function(){toast('Lỗi kết nối','error');});
}

function editShippingCompany(){
  var companies=['GHTK','J&T','GHN','Viettel Post','BEST','Ninja Van','SPX','Ahamove','Grab','Be','Gojek','Khác'];
  var current=(JSON.parse(localStorage.getItem('user')||'{}')).shipping_company||'';
  var ov=document.createElement('div');
  ov.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:2000;display:flex;align-items:center;justify-content:center';
  var html='<div style="background:#fff;border-radius:12px;max-width:320px;width:90%;max-height:70vh;overflow-y:auto"><div style="padding:14px 16px;font-weight:700;border-bottom:1px solid #f0f0f0">Chọn hãng vận chuyển</div>';
  companies.forEach(function(c){
    var active=c===current?' style="background:#f5f3ff;color:#7C3AED;font-weight:600"':'';
    html+='<div onclick="saveShippingCompany(\''+c+'\',this.closest(\'[style]\'))"'+active+' style="padding:12px 16px;cursor:pointer;border-bottom:1px solid #f8f8f8">'+c+'</div>';
  });
  html+='<div onclick="this.closest(\'[style]\').remove()" style="padding:12px;text-align:center;color:#999;cursor:pointer">Hủy</div></div>';
  ov.innerHTML=html;
  ov.onclick=function(e){if(e.target===ov)ov.remove();};
  document.body.appendChild(ov);
}
function saveShippingCompany(company,overlay){
  var token=localStorage.getItem('token');
  fetch('/api/auth.php?action=update_profile',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+(token||'')},body:JSON.stringify({shipping_company:company})})
    .then(function(r){return r.json()})
    .then(function(d){
      if(d.success){
        var user=JSON.parse(localStorage.getItem('user')||'{}');
        user.shipping_company=company;
        localStorage.setItem('user',JSON.stringify(user));
        if(overlay)overlay.remove();
        toast('Đã cập nhật!','success');
        location.reload();
      }else{toast(d.message||'Lỗi','error');}
    });
}

function openNotifPrefs(){
  var token=localStorage.getItem('token');
  if(!token)return;
  fetch('/api/auth.php?action=get_preferences',{headers:{'Authorization':'Bearer '+token}})
    .then(function(r){return r.json()})
    .then(function(d){
      var p=d.data||{};
      var ov=document.createElement('div');
      ov.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:2000;display:flex;align-items:center;justify-content:center';
      ov.innerHTML='<div style="background:#fff;border-radius:12px;padding:20px;max-width:340px;width:90%"><h3 style="margin:0 0 16px;font-size:17px">Cài đặt thông báo</h3>'
        +'<label style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0f0f0"><span>Thông báo email</span><input type="checkbox" id="pref_email" '+(p.notification_email?'checked':'')+' style="width:18px;height:18px"></label>'
        +'<label style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0f0f0"><span>Thông báo đẩy</span><input type="checkbox" id="pref_push" '+(p.notification_push?'checked':'')+' style="width:18px;height:18px"></label>'
        +'<label style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0f0f0"><span>Hiển thị online</span><input type="checkbox" id="pref_online" '+(p.privacy_online?'checked':'')+' style="width:18px;height:18px"></label>'
        +'<label style="display:flex;align-items:center;justify-content:space-between;padding:10px 0"><span>Hồ sơ</span><select id="pref_profile" style="padding:4px 8px;border:1px solid #ddd;border-radius:6px"><option value="public"'+(p.privacy_profile==='public'?' selected':'')+'>Công khai</option><option value="private"'+(p.privacy_profile==='private'?' selected':'')+'>Riêng tư</option></select></label>'
        +'<div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px"><button onclick="this.closest(\'[style]\').remove()" style="padding:8px 16px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer">Hủy</button><button onclick="saveNotifPrefs(this.closest(\'[style]\'))" style="padding:8px 16px;border:none;border-radius:8px;background:#7C3AED;color:#fff;cursor:pointer;font-weight:600">Lưu</button></div></div>';
      ov.onclick=function(e){if(e.target===ov)ov.remove();};
      document.body.appendChild(ov);
    });
}
function saveNotifPrefs(overlay){
  var token=localStorage.getItem('token');
  var data={
    notification_email:document.getElementById('pref_email').checked?1:0,
    notification_push:document.getElementById('pref_push').checked?1:0,
    privacy_online:document.getElementById('pref_online').checked?1:0,
    privacy_profile:document.getElementById('pref_profile').value
  };
  fetch('/api/auth.php?action=update_preferences',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+(token||'')},body:JSON.stringify(data)})
    .then(function(r){return r.json()})
    .then(function(d){
      if(d.success){if(overlay)overlay.remove();toast('Đã lưu!','success');}
      else toast(d.message||'Lỗi','error');
    });
}

function showReferral(){
  var token=localStorage.getItem('token');
  if(!token)return;
  fetch('/api/social.php?action=get_referral',{headers:{'Authorization':'Bearer '+token}})
    .then(function(r){return r.json()})
    .then(function(d){
      if(!d.success)return;
      var data=d.data;
      var ov=document.createElement('div');
      ov.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:2000;display:flex;align-items:center;justify-content:center';
      ov.innerHTML='<div style="background:#fff;border-radius:16px;padding:24px;max-width:340px;width:90%;text-align:center"><div style="font-size:24px;margin-bottom:8px">🎁</div><h3 style="margin:0 0 8px;font-size:17px">Mời bạn bè</h3><div style="font-size:13px;color:#65676B;margin-bottom:16px">Chia sẻ mã giới thiệu để nhận XP!</div><div style="background:#f5f3ff;border-radius:12px;padding:12px;margin-bottom:12px"><div style="font-size:24px;font-weight:700;color:#7C3AED;letter-spacing:3px">'+data.code+'</div></div><div style="font-size:12px;color:#999;margin-bottom:12px">'+data.referrals+' lượt giới thiệu thành công</div><button onclick="copyRef(\''+data.url+'\')" style="width:100%;padding:10px;background:#7C3AED;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer"><i class="fas fa-copy"></i> Sao chép link giới thiệu</button><div onclick="this.closest(\'[style]\').remove()" style="padding:10px;color:#999;cursor:pointer;font-size:14px;margin-top:4px">Đóng</div></div>';
      ov.onclick=function(e){if(e.target===ov)ov.remove();};
      document.body.appendChild(ov);
    });
}
function copyRef(url){
  if(navigator.clipboard){navigator.clipboard.writeText(url).then(function(){toast('Đã sao chép!','success');});}
}

// Settings gear icon in profile header
function addSettingsLink(){
  var header=document.querySelector('.profile-header')||document.querySelector('[class*="header"]');
  if(!header)return;
  var existing=document.getElementById('profileSettingsBtn');
  if(existing)return;
  var btn=document.createElement('a');
  btn.id='profileSettingsBtn';
  btn.href='settings.html';
  btn.innerHTML='<i class="fas fa-cog"></i>';
  btn.style.cssText='position:absolute;top:12px;right:12px;color:#fff;font-size:18px;text-decoration:none;background:rgba(0,0,0,.3);width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center';
  header.style.position='relative';
  header.appendChild(btn);
}

// Load delivery stats for profile
async function loadProfileDeliveries(){
  var token=localStorage.getItem('token');
  if(!token)return;
  try{
    var r=await fetch('/api/deliveries.php?action=today',{headers:{'Authorization':'Bearer '+token}});
    var d=await r.json();
    if(!d.success)return;
    var data=d.data;
    // Update XP display to show deliveries
    var xpEl=document.querySelector('.xp-display,.xp-number,[id*="xp"]');
    // Update streak display
    var el=document.getElementById('profileDeliveries');
    if(el){
      el.innerHTML='<div style="display:flex;gap:8px;margin:8px 0"><div style="flex:1;text-align:center;padding:8px;background:#f3f0ff;border-radius:10px"><div style="font-size:20px;font-weight:800;color:#7C3AED">'+data.deliveries_today+'</div><div style="font-size:10px;color:#666">Đơn hôm nay</div></div><div style="flex:1;text-align:center;padding:8px;background:#f0fdf4;border-radius:10px"><div style="font-size:20px;font-weight:800;color:#00b14f">'+data.all_time+'</div><div style="font-size:10px;color:#666">Tổng đơn</div></div><div style="flex:1;text-align:center;padding:8px;background:#fef3c7;border-radius:10px"><div style="font-size:20px;font-weight:800;color:#92400E">'+data.best_day.count+'</div><div style="font-size:10px;color:#666">Kỷ lục/ngày</div></div></div>';
    }
  }catch(e){}
}

})();

// Load delivery counter on profile page
if(document.getElementById('deliveryWidget')){
  if(typeof loadDeliveryCounter==='function'){loadDeliveryCounter();}
  else{
    // Load from feed-data.js functions
    var token=localStorage.getItem('token');
    if(token){
      fetch('/api/deliveries.php?action=today',{headers:{'Authorization':'Bearer '+token}})
      .then(function(r){return r.json();})
      .then(function(d){
        if(!d.success)return;
        var data=d.data;
        var box=document.getElementById('deliveryWidget');
        if(!box)return;
        
        function fmtM(n){return n?n.toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.')+'\u0111':'0\u0111';}
        function fmtN(n){return n?n.toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.'):'0';}
        
        var html='<div style="padding:14px 16px;background:linear-gradient(135deg,#7C3AED,#5B21B6);border-radius:12px;margin:8px 0;color:#fff">';
        html+='<div style="display:flex;justify-content:space-between;align-items:center">';
        html+='<div><div style="font-size:11px;opacity:.8">\u0110\u01a1n giao th\u00e0nh c\u00f4ng h\u00f4m nay</div>';
        html+='<div style="font-size:36px;font-weight:800;line-height:1.1">'+data.today+'<span style="font-size:14px;opacity:.6">/'+data.today_target+'</span></div></div>';
        html+='<div style="text-align:right"><div style="font-size:11px;opacity:.8">S\u1ed1 d\u01b0 v\u00ed</div>';
        html+='<div style="font-size:16px;font-weight:700">'+fmtM(data.balance)+'</div></div></div>';
        
        html+='<div style="margin-top:10px"><div style="display:flex;justify-content:space-between;font-size:10px;opacity:.8"><span>'+data.today_progress+'%</span><span>\ud83c\udfaf '+fmtM(data.today_reward)+' th\u01b0\u1edfng</span></div>';
        html+='<div style="background:rgba(255,255,255,.2);border-radius:4px;height:8px;margin-top:3px"><div style="background:#FBBF24;border-radius:4px;height:8px;width:'+data.today_progress+'%;transition:width .5s"></div></div></div>';
        
        if(data.can_claim_daily){
          html+='<button onclick="claimDailyReward(this)" style="width:100%;padding:12px;margin-top:10px;background:#FBBF24;color:#92400E;border:none;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer">\ud83c\udf81 Nh\u1eadn '+fmtM(data.today_reward)+' v\u00e0o v\u00ed!</button>';
        }else if(data.today_claimed){
          html+='<div style="text-align:center;margin-top:8px;padding:8px;background:rgba(255,255,255,.15);border-radius:8px;font-size:12px">\u2705 \u0110\u00e3 nh\u1eadn th\u01b0\u1edfng ng\u00e0y \u00b7 Quay l\u1ea1i 00:00</div>';
        }
        
        html+='<div style="margin-top:12px;padding-top:10px;border-top:1px solid rgba(255,255,255,.2)">';
        html+='<div style="display:flex;justify-content:space-between;align-items:center">';
        html+='<div style="font-size:11px;opacity:.8">Th\u00e1ng '+data.month_label+': '+data.month+'/'+fmtN(data.month_target)+' \u0111\u01a1n</div>';
        html+='<div style="font-size:11px;font-weight:600">'+fmtM(data.month_reward)+' th\u01b0\u1edfng</div></div>';
        html+='<div style="background:rgba(255,255,255,.2);border-radius:3px;height:5px;margin-top:4px"><div style="background:#34D399;border-radius:3px;height:5px;width:'+data.month_progress+'%"></div></div>';
        
        if(data.can_claim_monthly){
          html+='<button onclick="claimMonthlyReward(this)" style="width:100%;padding:10px;margin-top:8px;background:#34D399;color:#064E3B;border:none;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer">\ud83c\udfc6 Nh\u1eadn '+fmtM(data.month_reward)+' th\u01b0\u1edfng th\u00e1ng!</button>';
        }else if(data.month_claimed){
          html+='<div style="text-align:center;margin-top:6px;font-size:11px;opacity:.7">\u2705 \u0110\u00e3 nh\u1eadn th\u01b0\u1edfng th\u00e1ng '+data.month_label+'</div>';
        }else{
          html+='<div style="text-align:center;margin-top:4px;font-size:10px;opacity:.6">C\u00f2n '+data.days_left+' ng\u00e0y</div>';
        }
        html+='</div>';
        
        html+='<div style="display:flex;gap:6px;margin-top:10px">';
        html+='<div style="flex:1;background:rgba(255,255,255,.12);border-radius:8px;padding:6px;text-align:center"><div style="font-size:15px;font-weight:700">'+fmtN(data.all_time)+'</div><div style="font-size:9px;opacity:.6">T\u1ed5ng \u0111\u01a1n</div></div>';
        html+='<div style="flex:1;background:rgba(255,255,255,.12);border-radius:8px;padding:6px;text-align:center"><div style="font-size:15px;font-weight:700">'+data.best_day+'</div><div style="font-size:9px;opacity:.6">K\u1ef7 l\u1ee5c/ng\u00e0y</div></div>';
        html+='<div style="flex:1;background:rgba(255,255,255,.12);border-radius:8px;padding:6px;text-align:center"><div style="font-size:15px;font-weight:700">'+data.streak+'</div><div style="font-size:9px;opacity:.6">\ud83d\udd25 Streak</div></div>';
        html+='</div>';
        
        html+='</div>';
        box.innerHTML=html;
        box.style.display='block';
      }).catch(function(){});
    }
  }
}

function claimDailyReward(btn){
  var token=localStorage.getItem('token');
  btn.disabled=true;btn.textContent='\u0110ang nh\u1eadn...';
  fetch('/api/deliveries.php?action=claim_daily',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+token}})
  .then(function(r){return r.json();}).then(function(d){
    if(typeof toast==='function')toast(d.message||'Done',d.success?'success':'error');
    if(d.success)location.reload();
  }).catch(function(){});
}

function claimMonthlyReward(btn){
  var token=localStorage.getItem('token');
  btn.disabled=true;btn.textContent='\u0110ang nh\u1eadn...';
  fetch('/api/deliveries.php?action=claim_monthly',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+token}})
  .then(function(r){return r.json();}).then(function(d){
    if(typeof toast==='function')toast(d.message||'Done',d.success?'success':'error');
    if(d.success)location.reload();
  }).catch(function(){});
}
