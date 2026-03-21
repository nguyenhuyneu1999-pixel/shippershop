/**
 * ShipperShop Component — Post Boost
 * Boost posts for more visibility, pick package, pay from wallet
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.PostBoost = {

  // Show boost dialog for a post
  open: function(postId) {
    if (!SS.store || !SS.store.isLoggedIn()) {
      SS.ui.toast('Đăng nhập để boost', 'warning');
      return;
    }

    SS.api.get('/post-boost.php?action=packages').then(function(d) {
      var pkgs = d.data.packages || [];
      var html = '<div class="text-sm text-muted mb-3">Đẩy bài viết lên đầu feed để nhiều người thấy hơn</div>';

      for (var i = 0; i < pkgs.length; i++) {
        var p = pkgs[i];
        var stars = '';
        for (var s = 0; s < p.priority; s++) stars += '⭐';
        html += '<div class="card mb-2 card-hover" style="cursor:pointer;border:1px solid var(--border)" onclick="SS.PostBoost._boost(' + postId + ',' + p.id + ')">'
          + '<div class="card-body" style="padding:12px">'
          + '<div class="flex justify-between items-center">'
          + '<div><div class="font-bold text-sm">' + stars + ' ' + SS.utils.esc(p.name) + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(p.desc) + '</div></div>'
          + '<div class="font-bold" style="color:var(--primary)">' + SS.utils.formatMoney(p.price) + '</div>'
          + '</div></div></div>';
      }

      SS.ui.sheet({title: 'Boost bài viết', html: html});
    });
  },

  _boost: function(postId, packageId) {
    SS.ui.closeSheet();
    SS.ui.confirm('Xác nhận boost bài viết?', function() {
      SS.api.post('/post-boost.php?action=boost', {post_id: postId, package_id: packageId}).then(function(d) {
        SS.ui.toast(d.message || 'Đã boost!', 'success');
      }).catch(function(e) {
        SS.ui.toast(e.message || 'Lỗi', 'error');
      });
    });
  },

  // Render boost badge on post card
  badge: function() {
    return '<span style="display:inline-flex;align-items:center;gap:2px;padding:1px 6px;border-radius:8px;font-size:10px;font-weight:700;background:#FEF3C7;color:#D97706">🚀 Boost</span>';
  }
};
