/**
 * ShipperShop Component — Post Utilities
 * Copy post link, post type badges, save confirmation animation
 */
window.SS = window.SS || {};

SS.PostUtils = {

  // Copy post link to clipboard
  copyLink: function(postId) {
    var url = 'https://shippershop.vn/post-detail.html?id=' + postId;
    SS.utils.copyText(url);
    SS.ui.toast('Đã sao chép link!', 'success', 1500);
  },

  // Post type badge HTML
  typeBadge: function(type) {
    if (!type) return '';
    var badges = {
      'ghtk': {label: 'GHTK', color: '#00b14f'},
      'ghn': {label: 'GHN', color: '#ff6600'},
      'jnt': {label: 'J&T', color: '#d32f2f'},
      'spx': {label: 'SPX', color: '#EE4D2D'},
      'viettel': {label: 'Viettel Post', color: '#e21a1a'},
      'ninja': {label: 'Ninja Van', color: '#c41230'},
      'best': {label: 'BEST', color: '#ffc107'},
      'ahamove': {label: 'Ahamove', color: '#f5a623'},
      'grab': {label: 'Grab', color: '#00b14f'},
      'be': {label: 'Be', color: '#5bc500'},
      'gojek': {label: 'GoJek', color: '#00aa13'},
      'tip': {label: 'Mẹo', color: '#7C3AED'},
      'question': {label: 'Hỏi đáp', color: '#3b82f6'},
      'review': {label: 'Đánh giá', color: '#f59e0b'},
      'news': {label: 'Tin tức', color: '#22c55e'},
      'job': {label: 'Tuyển dụng', color: '#ec4899'},
    };
    var b = badges[type];
    if (!b) return '<span style="display:inline-block;font-size:10px;font-weight:700;padding:1px 6px;border-radius:4px;background:#f0f2f5;color:#666">' + SS.utils.esc(type) + '</span>';
    return '<span style="display:inline-block;font-size:10px;font-weight:700;padding:1px 6px;border-radius:4px;background:' + b.color + '20;color:' + b.color + '">' + b.label + '</span>';
  },

  // Save/unsave with animation
  toggleSave: function(postId, btnEl) {
    SS.api.post('/posts.php?action=save', {post_id: postId}).then(function(d) {
      var saved = d.data && d.data.saved;
      if (btnEl) {
        btnEl.innerHTML = saved
          ? '<i class="fa-solid fa-bookmark" style="color:var(--primary)"></i>'
          : '<i class="fa-regular fa-bookmark"></i>';
        // Bounce animation
        btnEl.style.transform = 'scale(1.3)';
        setTimeout(function() { btnEl.style.transform = 'scale(1)'; btnEl.style.transition = 'transform .2s'; }, 150);
      }
      SS.ui.toast(saved ? 'Đã lưu!' : 'Đã bỏ lưu', 'success', 1500);
      if (saved && SS.NotifSound) SS.NotifSound.play('success');
    });
  },

  // Post menu (3 dots) options
  showMenu: function(postId, isOwner) {
    var items = '';
    items += '<div class="list-item" onclick="SS.PostUtils.copyLink(' + postId + ');SS.ui.closeSheet()" style="cursor:pointer"><i class="fa-solid fa-link" style="width:20px;color:var(--primary)"></i><div class="flex-1">Sao chép link</div></div>';
    items += '<div class="list-item" onclick="SS.PostUtils.toggleSave(' + postId + ');SS.ui.closeSheet()" style="cursor:pointer"><i class="fa-regular fa-bookmark" style="width:20px;color:var(--warning)"></i><div class="flex-1">Lưu bài viết</div></div>';

    if (SS.Bookmarks) {
      items += '<div class="list-item" onclick="SS.Bookmarks.saveToCollection(' + postId + ')" style="cursor:pointer"><i class="fa-solid fa-folder-plus" style="width:20px;color:var(--info)"></i><div class="flex-1">Lưu vào collection</div></div>';
    }

    if (SS.ShareSheet) {
      items += '<div class="list-item" onclick="SS.ShareSheet.share({postId:' + postId + ',url:\'https://shippershop.vn/post-detail.html?id=' + postId + '\'});SS.ui.closeSheet()" style="cursor:pointer"><i class="fa-solid fa-share-nodes" style="width:20px;color:var(--success)"></i><div class="flex-1">Chia sẻ</div></div>';
    }

    if (SS.PostAnalytics) {
      items += '<div class="list-item" onclick="SS.PostAnalytics.showPost(' + postId + ');SS.ui.closeSheet()" style="cursor:pointer"><i class="fa-solid fa-chart-simple" style="width:20px;color:var(--accent)"></i><div class="flex-1">Xem thống kê</div></div>';
    }

    if (isOwner) {
      items += '<div class="divider"></div>';
      if (SS.PostEdit) {
        items += '<div class="list-item" onclick="SS.PostEdit.open(' + postId + ');SS.ui.closeSheet()" style="cursor:pointer"><i class="fa-solid fa-pen" style="width:20px;color:var(--text-muted)"></i><div class="flex-1">Sửa bài viết</div></div>';
      }
      items += '<div class="list-item" onclick="SS.PostUtils._pinPost(' + postId + ');SS.ui.closeSheet()" style="cursor:pointer"><i class="fa-solid fa-thumbtack" style="width:20px;color:var(--text-muted)"></i><div class="flex-1">Ghim bài viết</div></div>';
      items += '<div class="list-item" onclick="SS.PostUtils._deletePost(' + postId + ')" style="cursor:pointer"><i class="fa-solid fa-trash" style="width:20px;color:var(--danger)"></i><div class="flex-1" style="color:var(--danger)">Xóa bài viết</div></div>';
    } else {
      items += '<div class="divider"></div>';
      if (SS.ReportDialog) {
        items += '<div class="list-item" onclick="SS.ReportDialog.open(' + postId + ');SS.ui.closeSheet()" style="cursor:pointer"><i class="fa-solid fa-flag" style="width:20px;color:var(--danger)"></i><div class="flex-1">Báo cáo</div></div>';
      }
    }

    SS.ui.sheet({title: 'Tùy chọn', html: items});
  },

  _pinPost: function(postId) {
    SS.api.post('/posts.php?action=pin', {post_id: postId}).then(function(d) {
      SS.ui.toast(d.message || 'Đã cập nhật!', 'success');
    });
  },

  _deletePost: function(postId) {
    SS.ui.closeSheet();
    SS.ui.confirm('Xóa bài viết này?', function() {
      SS.api.post('/posts.php?action=delete', {post_id: postId}).then(function() {
        SS.ui.toast('Đã xóa', 'success');
        var card = document.querySelector('[data-post-id="' + postId + '"]');
        if (card) { card.style.opacity = '0'; card.style.transition = 'opacity .3s'; setTimeout(function() { card.remove(); }, 300); }
      });
    }, {danger: true, confirmText: 'Xóa'});
  }
};
