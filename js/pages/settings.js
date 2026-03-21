/**
 * ShipperShop Page — Settings
 * Central hub: notifications, blocked users, data export, dark mode, account
 * Uses: SS.api, SS.ui, SS.DarkMode, SS.NotifPrefs, SS.BlockUser
 */
window.SS = window.SS || {};

SS.SettingsPage = {

  open: function() {
    if (!SS.store || !SS.store.isLoggedIn()) return;

    var isDark = SS.DarkMode && SS.DarkMode.isActive();
    var html = '<div class="text-sm font-bold text-muted mb-2" style="text-transform:uppercase;letter-spacing:.5px">Giao diện</div>'
      + '<div class="list-item" onclick="SS.DarkMode&&SS.DarkMode.toggle();SS.ui.closeSheet()"><i class="fa-solid ' + (isDark ? 'fa-sun' : 'fa-moon') + '" style="width:20px;color:var(--primary)"></i><div class="flex-1">Chế độ tối</div><div class="text-sm text-muted">' + (isDark ? 'Đang bật' : 'Đang tắt') + '</div></div>'

      + '<div class="divider"></div>'
      + '<div class="text-sm font-bold text-muted mb-2" style="text-transform:uppercase;letter-spacing:.5px">Thông báo</div>'
      + '<div class="list-item" onclick="SS.NotifPrefs&&SS.NotifPrefs.open()"><i class="fa-solid fa-bell" style="width:20px;color:var(--primary)"></i><div class="flex-1">Cài đặt thông báo</div><i class="fa-solid fa-chevron-right text-muted" style="font-size:12px"></i></div>'

      + '<div class="divider"></div>'
      + '<div class="text-sm font-bold text-muted mb-2" style="text-transform:uppercase;letter-spacing:.5px">Quyền riêng tư</div>'
      + '<div class="list-item" onclick="SS.BlockUser&&SS.BlockUser.showBlockedList()"><i class="fa-solid fa-user-slash" style="width:20px;color:var(--danger)"></i><div class="flex-1">Người đã chặn</div><i class="fa-solid fa-chevron-right text-muted" style="font-size:12px"></i></div>'

      + '<div class="divider"></div>'
      + '<div class="text-sm font-bold text-muted mb-2" style="text-transform:uppercase;letter-spacing:.5px">Dữ liệu</div>'
      + '<div class="list-item" onclick="SS.SettingsPage.exportData()"><i class="fa-solid fa-download" style="width:20px;color:var(--info)"></i><div class="flex-1">Tải dữ liệu của tôi</div><i class="fa-solid fa-chevron-right text-muted" style="font-size:12px"></i></div>'
      + '<div class="list-item" onclick="SS.Bookmarks&&SS.Bookmarks.load(\'settings-bookmarks\')"><i class="fa-solid fa-bookmark" style="width:20px;color:var(--warning)"></i><div class="flex-1">Bài đã lưu</div><i class="fa-solid fa-chevron-right text-muted" style="font-size:12px"></i></div>'
      + '<div class="list-item" onclick="SS.ScheduledPosts&&SS.ScheduledPosts.open()"><i class="fa-solid fa-clock" style="width:20px;color:var(--accent)"></i><div class="flex-1">Bài hẹn giờ & nháp</div><i class="fa-solid fa-chevron-right text-muted" style="font-size:12px"></i></div>'

      + '<div class="divider"></div>'
      + '<div class="text-sm font-bold text-muted mb-2" style="text-transform:uppercase;letter-spacing:.5px">Tài khoản</div>'
      + '<div class="list-item" onclick="window.location.href=\'/profile.html\'"><i class="fa-solid fa-user-pen" style="width:20px;color:var(--text-muted)"></i><div class="flex-1">Chỉnh sửa hồ sơ</div><i class="fa-solid fa-chevron-right text-muted" style="font-size:12px"></i></div>'
      + '<div class="list-item" onclick="SS.AuthPage&&SS.AuthPage.logout()"><i class="fa-solid fa-right-from-bracket" style="width:20px;color:var(--danger)"></i><div class="flex-1" style="color:var(--danger)">Đăng xuất</div></div>';

    SS.ui.sheet({title: 'Cài đặt', html: html});
  },

  exportData: function() {
    SS.ui.confirm('Tải toàn bộ dữ liệu của bạn dưới dạng JSON?', function() {
      SS.ui.loading(true);
      SS.api.get('/export.php?action=summary').then(function(d) {
        SS.ui.loading(false);
        var data = d.data || {};
        var stats = data.stats || {};
        var html = '<div class="card"><div class="card-body">'
          + '<div class="list-item"><div class="flex-1">Bài viết</div><div class="font-bold">' + (stats.posts || 0) + '</div></div>'
          + '<div class="list-item"><div class="flex-1">Bình luận</div><div class="font-bold">' + (stats.comments || 0) + '</div></div>'
          + '<div class="list-item"><div class="flex-1">Lượt thích</div><div class="font-bold">' + (stats.likes || 0) + '</div></div>'
          + '<div class="list-item"><div class="flex-1">Tin nhắn</div><div class="font-bold">' + (stats.messages || 0) + '</div></div>'
          + '<div class="list-item"><div class="flex-1">Nhóm</div><div class="font-bold">' + (stats.groups || 0) + '</div></div>'
          + '</div></div>'
          + '<button class="btn btn-primary btn-block mt-3" onclick="SS.SettingsPage._downloadExport()"><i class="fa-solid fa-download"></i> Tải file JSON</button>';
        SS.ui.sheet({title: 'Dữ liệu của bạn', html: html});
      }).catch(function() { SS.ui.loading(false); SS.ui.toast('Lỗi', 'error'); });
    });
  },

  _downloadExport: function() {
    SS.ui.loading(true);
    SS.api.get('/export.php').then(function(d) {
      SS.ui.loading(false);
      var json = JSON.stringify(d.data, null, 2);
      var blob = new Blob([json], {type: 'application/json'});
      var url = URL.createObjectURL(blob);
      var a = document.createElement('a');
      a.href = url;
      a.download = 'shippershop-data-' + new Date().toISOString().split('T')[0] + '.json';
      a.click();
      URL.revokeObjectURL(url);
      SS.ui.toast('Đã tải xuống!', 'success');
    }).catch(function() { SS.ui.loading(false); SS.ui.toast('Lỗi tải', 'error'); });
  }
};
