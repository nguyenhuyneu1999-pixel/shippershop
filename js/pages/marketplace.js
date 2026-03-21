/**
 * ShipperShop Page — Marketplace (marketplace.html)
 * Product grid, search, filter, create listing
 * Uses: SS.api, SS.ui, SS.Upload
 */
window.SS = window.SS || {};

SS.MarketplacePage = {
  _page: 1,
  _loading: false,
  _category: '',
  _search: '',
  _sort: 'newest',

  init: function() {
    SS.MarketplacePage.load(false);
  },

  load: function(append) {
    if (SS.MarketplacePage._loading) return;
    SS.MarketplacePage._loading = true;

    var el = document.getElementById('mp-grid');
    if (!el) { SS.MarketplacePage._loading = false; return; }
    if (!append) el.innerHTML = '<div class="p-4 text-center"><div class="spin" style="width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';

    var params = {page: SS.MarketplacePage._page, limit: 12, sort: SS.MarketplacePage._sort};
    if (SS.MarketplacePage._category) params.category = SS.MarketplacePage._category;
    if (SS.MarketplacePage._search) params.search = SS.MarketplacePage._search;

    SS.api.get('/marketplace.php', params).then(function(d) {
      var items = d.data ? d.data.listings : (d.data || []);
      if (!append) el.innerHTML = '';

      if (!items || !items.length) {
        if (!append) el.innerHTML = '<div class="empty-state"><div class="empty-icon">🏪</div><div class="empty-text">Chưa có sản phẩm nào</div></div>';
        SS.MarketplacePage._loading = false;
        return;
      }

      var html = '';
      for (var i = 0; i < items.length; i++) {
        var p = items[i];
        var img = p.images ? (typeof p.images === 'string' ? JSON.parse(p.images)[0] : p.images[0]) : '/assets/img/defaults/no-posts.svg';
        var condition = {new:'Mới',like_new:'Như mới',good:'Tốt',fair:'Khá'}[p.condition] || '';

        html += '<a href="/listing.html?id=' + p.id + '" class="card card-hover" style="text-decoration:none;color:var(--text);overflow:hidden">'
          + '<div style="aspect-ratio:1;overflow:hidden;background:var(--bg)">'
          + '<img src="' + SS.utils.esc(img) + '" style="width:100%;height:100%;object-fit:cover" loading="lazy">'
          + '</div>'
          + '<div style="padding:10px 12px">'
          + '<div class="font-bold text-sm truncate">' + SS.utils.esc(p.title || '') + '</div>'
          + '<div style="color:var(--accent);font-weight:700;font-size:15px;margin:4px 0">' + SS.utils.formatMoney(p.price || 0) + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(p.user_name || '') + (condition ? ' · ' + condition : '') + '</div>'
          + '</div></a>';
      }
      el.insertAdjacentHTML('beforeend', html);
      SS.MarketplacePage._page++;
      SS.MarketplacePage._loading = false;
    }).catch(function() {
      SS.MarketplacePage._loading = false;
      if (!append) el.innerHTML = '<div class="empty-state"><div class="empty-text">Lỗi tải sản phẩm</div></div>';
    });
  },

  search: function(q) {
    SS.MarketplacePage._search = q;
    SS.MarketplacePage._page = 1;
    SS.MarketplacePage.load(false);
  },

  setSort: function(sort) {
    SS.MarketplacePage._sort = sort;
    SS.MarketplacePage._page = 1;
    SS.MarketplacePage.load(false);
  },

  openCreate: function() {
    if (!SS.store || !SS.store.isLoggedIn()) { window.location.href = '/login.html'; return; }

    var html = '<div class="form-group"><label class="form-label">Tên sản phẩm</label><input id="ml-title" class="form-input" placeholder="VD: iPhone 13 Pro Max"></div>'
      + '<div class="form-group"><label class="form-label">Giá (VNĐ)</label><input id="ml-price" type="number" class="form-input" placeholder="1000000"></div>'
      + '<div class="form-group"><label class="form-label">Mô tả</label><textarea id="ml-desc" class="form-textarea" rows="4" placeholder="Mô tả chi tiết..."></textarea></div>'
      + '<div class="form-group"><label class="form-label">Tình trạng</label><select id="ml-cond" class="form-select"><option value="new">Mới</option><option value="like_new">Như mới</option><option value="good">Tốt</option><option value="fair">Khá</option></select></div>'
      + '<div class="form-group"><label class="form-label">Ảnh</label><input type="file" id="ml-files" accept="image/*" multiple style="display:none"><div id="ml-previews" style="display:flex;flex-wrap:wrap;gap:4px"></div><button class="btn btn-ghost btn-sm mt-2" onclick="document.getElementById(\'ml-files\').click()"><i class="fa-solid fa-image"></i> Thêm ảnh</button></div>';

    SS.ui.modal({
      title: 'Đăng bán sản phẩm',
      html: html,
      confirmText: 'Đăng bán',
      onConfirm: function() {
        var title = document.getElementById('ml-title').value.trim();
        var price = parseInt(document.getElementById('ml-price').value);
        if (!title) { SS.ui.toast('Nhập tên sản phẩm', 'warning'); return; }
        if (!price || price < 1000) { SS.ui.toast('Giá tối thiểu 1.000đ', 'warning'); return; }

        var data = {
          title: title,
          price: price,
          description: document.getElementById('ml-desc').value.trim(),
          condition: document.getElementById('ml-cond').value
        };

        SS.api.post('/marketplace.php', data).then(function() {
          SS.ui.toast('Đã đăng bán!', 'success');
          SS.ui.closeModal();
          SS.MarketplacePage._page = 1;
          SS.MarketplacePage.load(false);
        });
      }
    });

    if (SS.Upload) SS.Upload.init('ml-files', 'ml-previews', {maxFiles: 5});
  }
};
