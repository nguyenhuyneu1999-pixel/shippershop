/**
 * ShipperShop Page — Listing Detail (listing.html)
 * Product detail with image gallery, seller info
 * Uses: SS.api, SS.ImageViewer, SS.ui
 */
window.SS = window.SS || {};

SS.ListingDetail = {
  _listingId: null,

  init: function(listingId) {
    SS.ListingDetail._listingId = listingId;
    if (!listingId) return;
    SS.ListingDetail.load();
  },

  load: function() {
    var el = document.getElementById('ld-content');
    if (!el) return;
    el.innerHTML = '<div class="p-4 text-center"><div class="spin" style="width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';

    SS.api.get('/marketplace.php?action=detail&id=' + SS.ListingDetail._listingId).then(function(d) {
      var p = d.data;
      if (!p) { el.innerHTML = '<div class="empty-state"><div class="empty-text">Sản phẩm không tồn tại</div></div>'; return; }

      var images = p.images;
      if (typeof images === 'string') { try { images = JSON.parse(images); } catch(e) { images = images ? [images] : []; } }
      if (!images) images = [];

      // Gallery
      var gallery = '';
      if (images.length) {
        gallery = '<div style="position:relative;aspect-ratio:1;background:var(--bg);border-radius:12px;overflow:hidden;margin-bottom:16px">'
          + '<img id="ld-main-img" src="' + images[0] + '" style="width:100%;height:100%;object-fit:contain;cursor:pointer" onclick="SS.ImageViewer&&SS.ImageViewer.open(this.src,' + JSON.stringify(images).replace(/"/g, '\\x22') + ')" loading="lazy">'
          + '</div>';
        if (images.length > 1) {
          gallery += '<div style="display:flex;gap:8px;overflow-x:auto;margin-bottom:16px;padding-bottom:4px">';
          for (var i = 0; i < images.length; i++) {
            gallery += '<img src="' + images[i] + '" style="width:64px;height:64px;object-fit:cover;border-radius:8px;cursor:pointer;border:2px solid ' + (i === 0 ? 'var(--primary)' : 'transparent') + '" onclick="document.getElementById(\'ld-main-img\').src=this.src;this.parentNode.querySelectorAll(\'img\').forEach(function(i){i.style.borderColor=\'transparent\'});this.style.borderColor=\'var(--primary)\'" loading="lazy">';
          }
          gallery += '</div>';
        }
      }

      var condMap = {new:'Mới 100%',like_new:'Như mới',good:'Tình trạng tốt',fair:'Khá'};
      var isSelf = SS.store && SS.store.userId() === parseInt(p.user_id);

      el.innerHTML = gallery
        + '<div class="card"><div class="card-body">'
        + '<h1 style="font-size:20px;font-weight:700;margin:0 0 8px">' + SS.utils.esc(p.title) + '</h1>'
        + '<div style="font-size:24px;font-weight:800;color:var(--accent);margin-bottom:12px">' + SS.utils.formatMoney(p.price) + '</div>'
        + (p.condition ? '<span class="badge badge-primary mb-3">' + (condMap[p.condition] || p.condition) + '</span> ' : '')
        + (p.description ? '<div class="text-sm mt-3" style="line-height:1.7;white-space:pre-wrap">' + SS.utils.esc(p.description) + '</div>' : '')
        + '<div class="divider"></div>'
        + '<div class="flex items-center gap-3">'
        + '<a href="/user.html?id=' + p.user_id + '"><img class="avatar" src="' + (p.user_avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy"></a>'
        + '<div class="flex-1"><a href="/user.html?id=' + p.user_id + '" class="font-bold" style="color:var(--text);text-decoration:none">' + SS.utils.esc(p.user_name || '') + '</a>'
        + '<div class="text-sm text-muted">' + SS.utils.ago(p.created_at) + '</div></div>'
        + (isSelf ? '<button class="btn btn-ghost btn-sm" onclick="SS.ListingDetail.edit()"><i class="fa-solid fa-pen"></i></button><button class="btn btn-ghost btn-sm" onclick="SS.ListingDetail.del()" style="color:var(--danger)"><i class="fa-solid fa-trash"></i></button>'
          : '<a href="/messages.html?to=' + p.user_id + '" class="btn btn-primary"><i class="fa-solid fa-envelope"></i> Nhắn tin</a>')
        + '</div></div></div>';

      document.title = p.title + ' | ShipperShop Chợ';
    }).catch(function() {
      el.innerHTML = '<div class="empty-state"><div class="empty-text">Lỗi tải sản phẩm</div></div>';
    });
  },

  edit: function() {
    SS.ui.toast('Tính năng đang phát triển', 'info');
  },

  del: function() {
    SS.ui.confirm('Xóa sản phẩm này?', function() {
      SS.api.post('/marketplace.php?action=delete', {listing_id: SS.ListingDetail._listingId}).then(function() {
        SS.ui.toast('Đã xóa', 'success');
        window.location.href = '/marketplace.html';
      });
    }, {danger: true, confirmText: 'Xóa'});
  }
};
