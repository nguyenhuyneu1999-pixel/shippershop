/**
 * ShipperShop Component — Share to Group
 * Pick a group to share a post into, with optional comment
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.ShareToGroup = {

  open: function(postId) {
    if (!SS.store || !SS.store.isLoggedIn()) {
      SS.ui.toast('Đăng nhập để chia sẻ', 'warning');
      return;
    }

    SS.api.get('/share-to-group.php').then(function(d) {
      var groups = d.data || [];
      if (!groups.length) {
        SS.ui.toast('Bạn chưa tham gia nhóm nào', 'info');
        return;
      }

      var html = '<div class="form-group"><label class="form-label">Thêm ghi chú (tùy chọn)</label>'
        + '<textarea id="stg-comment" class="form-textarea" rows="2" placeholder="Nói gì đó..."></textarea></div>'
        + '<div class="text-sm font-bold mb-2">Chọn nhóm</div>';

      for (var i = 0; i < groups.length; i++) {
        var g = groups[i];
        html += '<div class="list-item" style="cursor:pointer" onclick="SS.ShareToGroup._share(' + postId + ',' + g.id + ')">'
          + '<img class="avatar avatar-sm" src="' + (g.avatar || '/assets/img/defaults/avatar.svg') + '" style="border-radius:8px" loading="lazy">'
          + '<div class="flex-1 font-medium text-sm">' + SS.utils.esc(g.name) + '</div>'
          + '<i class="fa-solid fa-share text-muted" style="font-size:12px"></i></div>';
      }

      SS.ui.sheet({title: 'Chia sẻ vào nhóm', html: html});
    });
  },

  _share: function(postId, groupId) {
    var comment = '';
    var inp = document.getElementById('stg-comment');
    if (inp) comment = inp.value.trim();

    SS.ui.closeSheet();
    SS.api.post('/share-to-group.php', {
      post_id: postId,
      group_id: groupId,
      comment: comment
    }).then(function(d) {
      SS.ui.toast(d.message || 'Đã chia sẻ!', 'success');
    }).catch(function() {
      SS.ui.toast('Lỗi chia sẻ', 'error');
    });
  }
};
