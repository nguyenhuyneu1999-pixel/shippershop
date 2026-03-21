/**
 * ShipperShop Page — Account Settings
 * Privacy, data export, blocked users, notification prefs, danger zone
 * Uses: SS.api, SS.ui, SS.BlockUser, SS.NotifPrefs
 */
window.SS = window.SS || {};

SS.AccountSettings = {

  open: function() {
    if (!SS.store || !SS.store.isLoggedIn()) return;

    var html = '<div class="card mb-3"><div class="card-header">Tài khoản</div>'
      + '<div class="list-item" onclick="SS.AccountSettings.exportSummary()" style="cursor:pointer"><i class="fa-solid fa-chart-simple" style="width:20px;color:var(--primary)"></i><div class="flex-1">Thống kê tài khoản</div><i class="fa-solid fa-chevron-right text-muted"></i></div>'
      + '<div class="list-item" onclick="SS.AccountSettings.exportData()" style="cursor:pointer"><i class="fa-solid fa-download" style="width:20px;color:var(--info)"></i><div class="flex-1">Tải xuống dữ liệu</div><i class="fa-solid fa-chevron-right text-muted"></i></div>'
      + '<div class="list-item" onclick="if(SS.VerifiedBadge)SS.VerifiedBadge.requestVerification()" style="cursor:pointer"><i class="fa-solid fa-circle-check" style="width:20px;color:#3b82f6"></i><div class="flex-1">Xác minh tài khoản</div><i class="fa-solid fa-chevron-right text-muted"></i></div>'
      + '</div>'

      + '<div class="card mb-3"><div class="card-header">Quyền riêng tư</div>'
      + '<div class="list-item" onclick="if(SS.NotifPrefs)SS.NotifPrefs.open()" style="cursor:pointer"><i class="fa-solid fa-bell" style="width:20px;color:var(--warning)"></i><div class="flex-1">Cài đặt thông báo</div><i class="fa-solid fa-chevron-right text-muted"></i></div>'
      + '<div class="list-item" onclick="if(SS.BlockUser)SS.BlockUser.showBlockedList()" style="cursor:pointer"><i class="fa-solid fa-ban" style="width:20px;color:var(--danger)"></i><div class="flex-1">Danh sách chặn</div><i class="fa-solid fa-chevron-right text-muted"></i></div>'
      + '<div class="list-item" onclick="if(SS.ScheduledPosts)SS.ScheduledPosts.open()" style="cursor:pointer"><i class="fa-solid fa-clock" style="width:20px;color:var(--accent)"></i><div class="flex-1">Bài hẹn giờ & Nháp</div><i class="fa-solid fa-chevron-right text-muted"></i></div>'
      + '<div class="list-item" onclick="if(SS.Bookmarks)SS.Bookmarks.load(\'as-bookmarks\')" style="cursor:pointer"><i class="fa-solid fa-bookmark" style="width:20px;color:var(--primary)"></i><div class="flex-1">Bài đã lưu</div><i class="fa-solid fa-chevron-right text-muted"></i></div>'
      + '</div>'
      + '<div id="as-bookmarks"></div>'

      + '<div class="card mb-3"><div class="card-header">Giao diện</div>'
      + '<div class="list-item"><i class="fa-solid fa-moon" style="width:20px;color:var(--text-muted)"></i><div class="flex-1">Chế độ tối</div>'
      + '<label class="toggle"><input type="checkbox" ' + (SS.DarkMode && SS.DarkMode.isActive() ? 'checked' : '') + ' onchange="if(SS.DarkMode)SS.DarkMode.toggle()"><span class="toggle-slider"></span></label></div>'
      + '</div>'

      + '<div class="card"><div class="card-header" style="color:var(--danger)">Vùng nguy hiểm</div>'
      + '<div class="list-item" onclick="SS.AuthPage.logout()" style="cursor:pointer"><i class="fa-solid fa-right-from-bracket" style="width:20px;color:var(--danger)"></i><div class="flex-1" style="color:var(--danger)">Đăng xuất</div></div>'
      + '<div class="list-item" onclick="if(SS.ProfileSettings)SS.ProfileSettings.deleteAccount()" style="cursor:pointer"><i class="fa-solid fa-trash" style="width:20px;color:var(--danger)"></i><div class="flex-1" style="color:var(--danger)">Xóa tài khoản</div></div>'
      + '</div>'

      + '<style>.toggle{position:relative;width:44px;height:24px;display:inline-block}.toggle input{opacity:0;width:0;height:0}.toggle-slider{position:absolute;inset:0;background:var(--border);border-radius:24px;cursor:pointer;transition:.3s}.toggle-slider:before{content:"";position:absolute;width:18px;height:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s}.toggle input:checked+.toggle-slider{background:var(--primary)}.toggle input:checked+.toggle-slider:before{transform:translateX(20px)}</style>';

    SS.ui.sheet({title: 'Cài đặt tài khoản', html: html, maxHeight: '90vh'});
  },

  exportSummary: function() {
    SS.ui.loading(true);
    SS.api.get('/export.php?action=summary').then(function(d) {
      SS.ui.loading(false);
      var s = d.data || {};
      SS.ui.modal({
        title: 'Thống kê tài khoản',
        html: '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;text-align:center">'
          + SS.AccountSettings._stat('Bài viết', s.posts, '#7C3AED')
          + SS.AccountSettings._stat('Bình luận', s.comments, '#3b82f6')
          + SS.AccountSettings._stat('Lượt thích', s.likes, '#22c55e')
          + SS.AccountSettings._stat('Đang theo dõi', s.following, '#f59e0b')
          + SS.AccountSettings._stat('Người theo dõi', s.followers, '#ec4899')
          + SS.AccountSettings._stat('Tin nhắn', s.messages_sent, '#8b5cf6')
          + SS.AccountSettings._stat('Bài lưu', s.saved_posts, '#06b6d4')
          + '</div>',
        hideConfirm: true
      });
    }).catch(function() { SS.ui.loading(false); SS.ui.toast('Lỗi', 'error'); });
  },

  _stat: function(label, value, color) {
    return '<div class="card"><div class="card-body" style="padding:12px"><div style="font-size:22px;font-weight:800;color:' + color + '">' + SS.utils.fN(value || 0) + '</div><div class="text-xs text-muted mt-1">' + label + '</div></div></div>';
  },

  exportData: function() {
    SS.ui.confirm('Tải xuống toàn bộ dữ liệu của bạn? File JSON sẽ được tạo.', function() {
      SS.ui.loading(true);
      SS.api.get('/export.php?action=full').then(function(d) {
        SS.ui.loading(false);
        // Download as JSON file
        var blob = new Blob([JSON.stringify(d.data, null, 2)], {type: 'application/json'});
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'shippershop-data-' + new Date().toISOString().split('T')[0] + '.json';
        a.click();
        URL.revokeObjectURL(url);
        SS.ui.toast('Đã tải xuống!', 'success');
      }).catch(function() { SS.ui.loading(false); SS.ui.toast('Lỗi xuất dữ liệu', 'error'); });
    });
  }
};
