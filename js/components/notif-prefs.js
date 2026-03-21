/**
 * ShipperShop Component — Notification Preferences
 * Toggle switches for each notification type + quiet hours
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.NotifPrefs = {

  open: function() {
    if (!SS.store || !SS.store.isLoggedIn()) return;

    SS.api.get('/notif-prefs.php').then(function(d) {
      var prefs = d.data || {};
      var types = [
        {key: 'likes', label: 'Lượt thích', icon: '❤️'},
        {key: 'comments', label: 'Bình luận', icon: '💬'},
        {key: 'follows', label: 'Người theo dõi', icon: '👤'},
        {key: 'messages', label: 'Tin nhắn', icon: '✉️'},
        {key: 'groups', label: 'Nhóm', icon: '👥'},
        {key: 'system', label: 'Hệ thống', icon: '🔔'},
        {key: 'marketing', label: 'Khuyến mãi', icon: '🎁'},
      ];

      var html = '';
      for (var i = 0; i < types.length; i++) {
        var t = types[i];
        var checked = prefs[t.key] !== false;
        html += '<div class="list-item">'
          + '<span style="font-size:20px">' + t.icon + '</span>'
          + '<div class="flex-1 text-sm font-medium">' + t.label + '</div>'
          + '<label class="toggle"><input type="checkbox" id="np-' + t.key + '" ' + (checked ? 'checked' : '') + ' onchange="SS.NotifPrefs._save()"><span class="toggle-slider"></span></label>'
          + '</div>';
      }

      // Toggle CSS
      html += '<style>.toggle{position:relative;width:44px;height:24px;display:inline-block}.toggle input{opacity:0;width:0;height:0}.toggle-slider{position:absolute;inset:0;background:var(--border);border-radius:24px;cursor:pointer;transition:.3s}.toggle-slider:before{content:"";position:absolute;width:18px;height:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s}.toggle input:checked+.toggle-slider{background:var(--primary)}.toggle input:checked+.toggle-slider:before{transform:translateX(20px)}</style>';

      // Quiet hours
      html += '<div class="divider"></div>'
        + '<div class="text-sm font-bold mb-2">Giờ im lặng</div>'
        + '<div class="text-xs text-muted mb-3">Không nhận thông báo trong khoảng thời gian này</div>'
        + '<div class="flex gap-2 items-center">'
        + '<input type="time" id="np-quiet-start" class="form-input" value="' + (prefs.quiet_start || '') + '" style="flex:1" onchange="SS.NotifPrefs._save()">'
        + '<span class="text-muted">đến</span>'
        + '<input type="time" id="np-quiet-end" class="form-input" value="' + (prefs.quiet_end || '') + '" style="flex:1" onchange="SS.NotifPrefs._save()">'
        + '</div>';

      SS.ui.sheet({title: 'Cài đặt thông báo', html: html});
    });
  },

  _save: function() {
    var prefs = {};
    var keys = ['likes', 'comments', 'follows', 'messages', 'groups', 'system', 'marketing'];
    for (var i = 0; i < keys.length; i++) {
      var el = document.getElementById('np-' + keys[i]);
      if (el) prefs[keys[i]] = el.checked;
    }
    var qs = document.getElementById('np-quiet-start');
    var qe = document.getElementById('np-quiet-end');
    if (qs) prefs.quiet_start = qs.value || null;
    if (qe) prefs.quiet_end = qe.value || null;

    SS.api.post('/notif-prefs.php', prefs).then(function() {
      SS.ui.toast('Đã lưu!', 'success', 1500);
    });
  }
};
