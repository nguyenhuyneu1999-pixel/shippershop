/**
 * ShipperShop Component — User Block Dialog
 * Block/unblock users, view blocked list
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.BlockUser = {

  toggle: function(userId, userName) {
    if (!SS.store || !SS.store.isLoggedIn()) return;

    SS.api.get('/social.php?action=is_blocked&user_id=' + userId).then(function(d) {
      var data = d.data || {};
      if (data.blocked) {
        SS.ui.confirm('Bỏ chặn ' + SS.utils.esc(userName || 'user') + '?', function() {
          SS.api.post('/social.php?action=block', {user_id: userId}).then(function(r) {
            SS.ui.toast(r.data && r.data.blocked ? 'Đã chặn' : 'Đã bỏ chặn', 'success');
          });
        });
      } else {
        SS.ui.confirm('Chặn ' + SS.utils.esc(userName || 'user') + '? Họ sẽ không thể xem bài viết và nhắn tin cho bạn.', function() {
          SS.api.post('/social.php?action=block', {user_id: userId}).then(function(r) {
            SS.ui.toast('Đã chặn', 'success');
          });
        }, {danger: true, confirmText: 'Chặn'});
      }
    });
  },

  showBlockedList: function() {
    if (!SS.store || !SS.store.isLoggedIn()) return;

    SS.api.get('/social.php?action=blocked').then(function(d) {
      var blocked = d.data || [];
      if (!blocked.length) {
        SS.ui.sheet({title: 'Danh sách chặn', html: '<div class="empty-state p-4"><div class="empty-text">Chưa chặn ai</div></div>'});
        return;
      }
      var html = '';
      for (var i = 0; i < blocked.length; i++) {
        var u = blocked[i];
        html += '<div class="list-item">'
          + '<img class="avatar avatar-sm" src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy">'
          + '<div class="flex-1"><div class="list-title">' + SS.utils.esc(u.fullname) + '</div>'
          + '<div class="list-subtitle">' + SS.utils.ago(u.blocked_at) + '</div></div>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.BlockUser.toggle(' + u.id + ',\'' + SS.utils.esc(u.fullname).replace(/'/g, '\\x27') + '\')">Bỏ chặn</button></div>';
      }
      SS.ui.sheet({title: 'Danh sách chặn (' + blocked.length + ')', html: html});
    });
  }
};
