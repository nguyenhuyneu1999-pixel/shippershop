// ShipperShop Marketplace Page

var currentCat = '', currentSearch = '', currentPage = 1, totalPages = 1;
var uploadedImgUrls = [];
var searchTimer = null;

// ============ LOAD LISTINGS ============
async function loadListings(append) {
    if (!append) { currentPage = 1; document.getElementById('mkGrid').innerHTML = '<div class="mk-loading"></div>'; }
    var url = '/api/marketplace.php?page=' + currentPage;
    if (currentCat) url += '&category=' + currentCat;
    if (currentSearch) url += '&search=' + encodeURIComponent(currentSearch);
    try {
        var r = await fetch(url);
        var d = await r.json();
        if (d.success) {
            totalPages = d.data.pages;
            var html = d.data.items.map(function(item) {
                var imgs = []; try { imgs = JSON.parse(item.images || '[]'); } catch(e) {}
                var img = imgs.length > 0 ? imgs[0] : '';
                var priceStr = item.price > 0 ? Number(item.price).toLocaleString('vi-VN') + ' ₫' : 'Miễn phí';
                return '<div class="mk-item" onclick="goListing('+item.id+')">'
                    + (img ? '<img class="mk-item-img" src="' + img + '" onerror="this.src=\'data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 1 1%22 loading=\"lazy\"><rect fill=%22%23e4e6eb%22 width=%221%22 height=%221%22/></svg>\'" loading=\"lazy\">' : '<div class="mk-item-img" style="display:flex;align-items:center;justify-content:center;font-size:40px;color:#ccc"><i class="fas fa-image"></i></div>')
                    + '<div class="mk-item-info">'
                    + '<div class="mk-item-price">' + priceStr + '</div>'
                    + '<div class="mk-item-title">' + esc(item.title) + '</div>'
                    + (item.location ? '<div class="mk-item-loc"><i class="fas fa-map-marker-alt"></i>' + esc(item.location) + '</div>' : '')
                    + '</div></div>';
            }).join('');
            if (append) document.getElementById('mkGrid').innerHTML += html;
            else document.getElementById('mkGrid').innerHTML = html || '<div class="mk-empty" style="grid-column:1/-1"><i class="fas fa-store-slash"></i><p>Chưa có tin đăng nào</p><p style="margin-top:8px;font-size:13px">Hãy là người đầu tiên đăng bán!</p></div>';
            document.getElementById('mkMore').style.display = currentPage < totalPages ? 'block' : 'none';
        }
    } catch(e) { document.getElementById('mkGrid').innerHTML = '<div class="mk-empty" style="grid-column:1/-1"><i class="fas fa-exclamation-circle"></i><p>Lỗi tải dữ liệu</p></div>'; }
}

function loadMore() { currentPage++; loadListings(true); }

function filterCat(cat, el) {
    currentCat = cat;
    document.querySelectorAll('.mk-cat').forEach(function(c) { c.classList.remove('active'); });
    el.classList.add('active');
    loadListings();
}

function debounceSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function() {
        currentSearch = document.getElementById('mkSearch').value.trim();
        loadListings();
    }, 400);
}

function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

// ============ CREATE LISTING ============
function goListing(id){location.href="listing.html?id="+id;}

function openCreate() {
    var user = localStorage.getItem('user');
    if (!user) { alert('Vui lòng đăng nhập để đăng tin'); window.location.href = 'login.html'; return; }
    document.getElementById('createOverlay').classList.add('active');
    document.getElementById('createModal').classList.add('active');
}
function closeCreate() {
    document.getElementById('createOverlay').classList.remove('active');
    document.getElementById('createModal').classList.remove('active');
}

async function handleImgUpload(input) {
    if (!input.files || input.files.length === 0) return;
    var fd = new FormData();
    for (var i = 0; i < Math.min(input.files.length, 10); i++) { fd.append('images[]', input.files[i]); }
    // Show loading
    var addBtn = document.getElementById('imgAddBtn');
    addBtn.innerHTML = '<div class="mk-loading" style="width:20px;height:20px;margin:0"></div>';
    try {
        var r = await fetch('/api/marketplace.php?action=upload', {
            credentials: 'include',
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') },
            body: fd
        });
        var d = await r.json();
        if (d.success && d.data.urls) {
            uploadedImgUrls = uploadedImgUrls.concat(d.data.urls);
            renderImgPreviews();
        } else { alert(d.message || 'Lỗi upload'); }
    } catch(e) { alert('Lỗi kết nối'); }
    addBtn.innerHTML = '<i class="fas fa-camera"></i><span>Thêm ảnh</span><input type="file" accept="image/*" multiple style="display:none" onchange="handleImgUpload(this)">';
}

function renderImgPreviews() {
    var container = document.getElementById('imgPreviews');
    var html = uploadedImgUrls.map(function(url, i) {
        return '<div class="mk-img-preview"><img src="' + url + '" loading=\"lazy\"><button class="mk-img-del" onclick="removeImg(' + i + ')"><i class="fas fa-times"></i></button></div>';
    }).join('');
    html += '<label class="mk-img-add" id="imgAddBtn"><i class="fas fa-camera"></i><span>Thêm</span><input type="file" accept="image/*" multiple style="display:none" onchange="handleImgUpload(this)"></label>';
    container.innerHTML = html;
}

function removeImg(i) { uploadedImgUrls.splice(i, 1); renderImgPreviews(); }

// Video upload
var uploadedVideoUrl = null;
function handleVideoSelect(input) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    if (file.size > 50 * 1024 * 1024) { alert('Video tối đa 50MB'); return; }
    var label = document.getElementById('videoLabel');
    label.textContent = 'Đang tải lên...';
    var fd = new FormData();
    fd.append('video', file);
    fetch('/api/marketplace.php?action=upload', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') },
        credentials: 'include',
        body: fd
    }).then(function(r) { return r.json(); }).then(function(d) {
        if (d.success && d.data.video_url) {
            uploadedVideoUrl = d.data.video_url;
            document.getElementById('videoPreview').style.display = 'block';
            document.getElementById('videoPreviewPlayer').src = uploadedVideoUrl;
            label.textContent = 'Video đã tải ✓';
        } else { label.textContent = 'Lỗi: ' + (d.message || 'Thử lại'); }
    }).catch(function() { label.textContent = 'Lỗi kết nối'; });
}
function removeVideo() {
    uploadedVideoUrl = null;
    document.getElementById('videoPreview').style.display = 'none';
    document.getElementById('videoPreviewPlayer').src = '';
    document.getElementById('videoLabel').textContent = 'Chọn video (MP4, tối đa 50MB)';
}

// Showcase images (Amazon A+ Content style)
var uploadedShowcaseUrls = [];
async function handleShowcaseImgUpload(input) {
    if (!input.files || input.files.length === 0) return;
    var fd = new FormData();
    for (var i = 0; i < Math.min(input.files.length, 10); i++) { fd.append('images[]', input.files[i]); }
    try {
        var r = await fetch('/api/marketplace.php?action=upload', {
            method: 'POST', credentials: 'include',
            headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') },
            body: fd
        });
        var d = await r.json();
        if (d.success && d.data.urls) {
            uploadedShowcaseUrls = uploadedShowcaseUrls.concat(d.data.urls);
            renderShowcasePreviews();
        }
    } catch(e) {}
}
function renderShowcasePreviews() {
    var c = document.getElementById('showcaseImgPreviews');
    var html = uploadedShowcaseUrls.map(function(url, i) {
        return '<div class="mk-img-preview"><img src="' + url + '" loading=\"lazy\"><button class="mk-img-del" onclick="removeShowcaseImg(' + i + ')"><i class="fas fa-times"></i></button></div>';
    }).join('');
    html += '<label class="mk-img-add" id="showcaseImgAddBtn"><i class="fas fa-plus-circle" style="font-size:24px;color:var(--primary)"></i><span>Thêm</span><input type="file" accept="image/*" multiple style="display:none" onchange="handleShowcaseImgUpload(this)"></label>';
    c.innerHTML = html;
}
function removeShowcaseImg(i) { uploadedShowcaseUrls.splice(i, 1); renderShowcasePreviews(); }

async function submitListing() {
    var title = document.getElementById('cTitle').value.trim();
    var price = parseInt(document.getElementById('cPrice').value) || 0;
    if (!title) { alert('Vui lòng nhập tiêu đề'); return; }
    var btn = document.getElementById('cSubmit');
    btn.disabled = true; btn.textContent = 'Đang đăng...';
    try {
        var r = await fetch('/api/marketplace.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + (localStorage.getItem('token')||'') },
            credentials: 'include',
            body: JSON.stringify({
                title: title, price: price,
                category: document.getElementById('cCategory').value,
                condition_type: document.getElementById('cCondition').value,
                description: document.getElementById('cDesc').value,
                description_images: JSON.stringify(uploadedShowcaseUrls),
                showcase_images: JSON.stringify(uploadedShowcaseUrls),
                location: document.getElementById('cLocation').value,
                phone: document.getElementById('cPhone').value,
                images: uploadedImgUrls,
                video_url: uploadedVideoUrl
            })
        });
        var d = await r.json();
        if (d.success) {
            alert('✅ Đăng tin thành công!');
            closeCreate(); uploadedImgUrls = []; uploadedVideoUrl = null; uploadedShowcaseUrls = [];
            document.getElementById('cTitle').value = '';
            document.getElementById('cPrice').value = '';
            document.getElementById('cDesc').value = '';
            document.getElementById('cLocation').value = '';
            document.getElementById('cPhone').value = '';
            renderImgPreviews();
            loadListings();
        } else { if((d.message||'').indexOf('Shipper Plus')>-1){if(confirm(d.message+'\n\nNâng cấp ngay?'))location.href='/wallet.html';}else alert('❌ ' + (d.message || 'Lỗi')); }
    } catch(e) { alert('Lỗi kết nối'); }
    btn.disabled = false; btn.textContent = 'Đăng tin';
}

// ============ DETAIL VIEW ============
async function openDetail(id) {
    document.getElementById('detailOverlay').classList.add('active');
    document.getElementById('detailModal').classList.add('active');
    document.getElementById('detailContent').innerHTML = '<div class="mk-loading" style="margin:60px auto"></div>';
    try {
        var r = await fetch('/api/marketplace.php?id=' + id);
        var d = await r.json();
        if (d.success) {
            var item = d.data;
            var imgs = []; try { imgs = JSON.parse(item.images || '[]'); } catch(e) {}
            var priceStr = item.price > 0 ? Number(item.price).toLocaleString('vi-VN') + ' ₫' : 'Miễn phí';
            var condLabels = {new:'Mới 100%',like_new:'Như mới',good:'Đã qua sử dụng',fair:'Còn dùng được'};
            var currentImg = 0;
            var html = '';
            // Images
            if (imgs.length > 0) {
                html += '<div class="mk-detail-imgs" id="detailImgWrap"><img id="detailMainImg" src="' + imgs[0] + '" loading=\"lazy\"><div class="mk-detail-dots">' + imgs.map(function(_,i){return '<span' + (i===0?' class="active"':'') + '></span>';}).join('') + '</div></div>';
            }
            html += '<div style="padding:16px">';
            html += '<div style="font-size:22px;font-weight:800;color:var(--primary)">' + priceStr + '</div>';
            html += '<h3 style="font-size:17px;margin-top:6px">' + esc(item.title) + '</h3>';
            html += '<div class="mk-cond">' + (condLabels[item.condition_type]||item.condition_type) + '</div>';
            if (item.location) html += '<p style="margin-top:8px;color:var(--text2);font-size:13px"><i class="fas fa-map-marker-alt"></i> ' + esc(item.location) + '</p>';
            html += '<p style="margin-top:4px;color:var(--text2);font-size:12px"><i class="fas fa-eye"></i> ' + (item.views_count||0) + ' lượt xem</p>';
            if (item.description) html += '<div style="margin-top:16px;padding-top:16px;border-top:1px solid #e4e6eb"><h4 style="font-size:14px;margin-bottom:8px">Mô tả</h4><p style="font-size:14px;line-height:1.6;color:var(--text2);white-space:pre-wrap">' + esc(item.description) + '</p></div>';
            // Seller
            html += '<div class="mk-seller">';
            if (item.seller_avatar) html += '<div class="mk-seller-avatar"><img src="' + item.seller_avatar + '" loading=\"lazy\"></div>';
            else html += '<div class="mk-seller-avatar">' + (item.seller_name||'U').charAt(0) + '</div>';
            html += '<div><div style="font-weight:600">' + esc(item.seller_name||'') + '</div>';
            if (item.seller_username) html += '<div style="font-size:12px;color:var(--text2)">@' + esc(item.seller_username) + '</div>';
            html += '</div></div>';
            // Contact
            html += '<a href="messages.html?to=' + item.user_id + '" style="display:block;text-align:center;background:var(--primary);color:#fff;padding:12px;border-radius:10px;font-weight:700;margin-top:12px"><i class="fas fa-comment-dots"></i> Nhắn tin cho người bán</a>';
            html += '</div>';
            document.getElementById('detailContent').innerHTML = html;
            // Swipe images
            if (imgs.length > 1) {
                var wrap = document.getElementById('detailImgWrap');
                var sx = 0;
                wrap.addEventListener('touchstart', function(e) { sx = e.touches[0].clientX; }, {passive:true});
                wrap.addEventListener('touchend', function(e) {
                    var diff = sx - e.changedTouches[0].clientX;
                    if (Math.abs(diff) > 40) {
                        if (diff > 0) currentImg = Math.min(currentImg + 1, imgs.length - 1);
                        else currentImg = Math.max(currentImg - 1, 0);
                        document.getElementById('detailMainImg').src = imgs[currentImg];
                        wrap.querySelectorAll('.mk-detail-dots span').forEach(function(s,i) { s.classList.toggle('active', i === currentImg); });
                    }
                }, {passive:true});
            }
        } else { document.getElementById('detailContent').innerHTML = '<div class="mk-empty"><p>Không tìm thấy tin đăng</p></div>'; }
    } catch(e) { document.getElementById('detailContent').innerHTML = '<div class="mk-empty"><p>Lỗi tải dữ liệu</p></div>'; }
}

function closeDetail() {
    document.getElementById('detailOverlay').classList.remove('active');
    document.getElementById('detailModal').classList.remove('active');
}

function shareListing() {
    if (navigator.share) navigator.share({title:'Xem trên ShipperShop', url:location.href});
    else { navigator.clipboard.writeText(location.href); alert('Đã copy link!'); }
}

function openMyListings() {
    var user = JSON.parse(localStorage.getItem('user') || 'null');
    if (!user) { window.location.href = 'login.html'; return; }
    currentSearch = '';
    document.getElementById('mkSearch').value = '';
    currentCat = '';
    document.querySelectorAll('.mk-cat').forEach(function(c) { c.classList.remove('active'); });
    document.querySelectorAll('.mk-cat')[0].classList.add('active');
    // Load user's listings
    document.getElementById('mkGrid').innerHTML = '<div class="mk-loading"></div>';
    fetch('/api/marketplace.php?user_id=' + user.id).then(function(r){return r.json();}).then(function(d) {
        if (d.success) {
            if (d.data.items.length === 0) {
                document.getElementById('mkGrid').innerHTML = '<div class="mk-empty" style="grid-column:1/-1"><i class="fas fa-box-open"></i><p>Bạn chưa đăng tin nào</p></div>';
            } else {
                document.getElementById('mkGrid').innerHTML = d.data.items.map(function(item) {
                    var imgs = []; try { imgs = JSON.parse(item.images || '[]'); } catch(e) {}
                    var img = imgs.length > 0 ? imgs[0] : '';
                    var priceStr = item.price > 0 ? Number(item.price).toLocaleString('vi-VN') + ' ₫' : 'Miễn phí';
                    return '<div class="mk-item" onclick="goListing('+item.id+')">' + (img ? '<img class="mk-item-img" src="' + img + '" loading=\"lazy\">' : '<div class="mk-item-img" style="display:flex;align-items:center;justify-content:center;font-size:40px;color:#ccc"><i class="fas fa-image"></i></div>') + '<div class="mk-item-info"><div class="mk-item-price">' + priceStr + '</div><div class="mk-item-title">' + esc(item.title) + '</div></div></div>';
                }).join('');
            }
        }
    }).catch(function() {});
}

// Init
loadListings();
