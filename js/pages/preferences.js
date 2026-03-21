/**
 * ShipperShop Page — User Preferences
 * App-level settings: font size, feed sort, video autoplay, dark mode, etc.
 * Uses: SS.api, SS.ui, SS.DarkMode
 */
window.SS = window.SS || {};

SS.PrefsPage = {
  _prefs: null,

  open: function() {
    if (!SS.store || !SS.store.isLoggedIn()) return;
    SS.api.get('/preferences.php').then(function(d) {
      SS.PrefsPage._prefs = d.data || {};
      SS.PrefsPage._render();
    });
  },

  _render: function() {
    var p = SS.PrefsPage._prefs;
    if (!p) return;

    var html = '<div class="text-sm font-bold text-muted mb-2" style="text-transform:uppercase;letter-spacing:.5px">Giao diện</div>'
      + SS.PrefsPage._toggle('dark_mode', 'Chế độ tối', p.dark_mode === 'dark', 'fa-moon')
      + SS.PrefsPage._select('font_size', 'Cỡ chữ', p.font_size, [{v:'small',l:'Nhỏ'},{v:'normal',l:'Bình thường'},{v:'large',l:'Lớn'}], 'fa-text-height')
      + SS.PrefsPage._toggle('compact_mode', 'Chế độ thu gọn', p.compact_mode, 'fa-compress')

      + '<div class="divider"></div>'
      + '<div class="text-sm font-bold text-muted mb-2" style="text-transform:uppercase;letter-spacing:.5px">Feed</div>'
      + SS.PrefsPage._select('feed_sort', 'Sắp xếp mặc định', p.feed_sort, [{v:'hot',l:'Nổi bật'},{v:'new',l:'Mới nhất'},{v:'following',l:'Đang theo dõi'}], 'fa-sort')
      + SS.PrefsPage._toggle('auto_play_video', 'Tự động phát video', p.auto_play_video, 'fa-play')
      + SS.PrefsPage._toggle('show_read_time', 'Hiện thời gian đọc', p.show_read_time, 'fa-clock')
      + SS.PrefsPage._toggle('link_previews', 'Link preview trong bài', p.link_previews, 'fa-link')

      + '<div class="divider"></div>'
      + '<div class="text-sm font-bold text-muted mb-2" style="text-transform:uppercase;letter-spacing:.5px">Riêng tư</div>'
      + SS.PrefsPage._toggle('show_online_status', 'Hiện trạng thái online', p.show_online_status, 'fa-circle')
      + SS.PrefsPage._toggle('notif_sound', 'Âm thanh thông báo', p.notif_sound, 'fa-volume-high');

    SS.ui.sheet({title: 'Tùy chỉnh ứng dụng', html: html});
  },

  _toggle: function(key, label, value, icon) {
    var checked = value ? 'checked' : '';
    return '<div class="list-item" style="cursor:pointer" onclick="SS.PrefsPage._onToggle(\'' + key + '\',this)">'
      + '<i class="fa-solid ' + icon + '" style="width:20px;color:var(--primary)"></i>'
      + '<div class="flex-1">' + label + '</div>'
      + '<div style="width:44px;height:24px;border-radius:12px;background:' + (value ? 'var(--primary)' : 'var(--border)') + ';position:relative;transition:background .2s;cursor:pointer" data-key="' + key + '" data-on="' + (value ? '1' : '0') + '">'
      + '<div style="width:20px;height:20px;border-radius:50%;background:#fff;position:absolute;top:2px;' + (value ? 'right:2px' : 'left:2px') + ';box-shadow:0 1px 3px rgba(0,0,0,.2);transition:all .2s"></div></div></div>';
  },

  _select: function(key, label, value, options, icon) {
    var optHtml = '';
    for (var i = 0; i < options.length; i++) {
      var o = options[i];
      optHtml += '<option value="' + o.v + '"' + (value === o.v ? ' selected' : '') + '>' + o.l + '</option>';
    }
    return '<div class="list-item">'
      + '<i class="fa-solid ' + icon + '" style="width:20px;color:var(--primary)"></i>'
      + '<div class="flex-1">' + label + '</div>'
      + '<select class="form-select" style="width:auto;font-size:12px;padding:4px 8px" onchange="SS.PrefsPage._onSelect(\'' + key + '\',this.value)">' + optHtml + '</select></div>';
  },

  _onToggle: function(key, el) {
    var toggle = el.querySelector('[data-key]');
    if (!toggle) return;
    var isOn = toggle.getAttribute('data-on') === '1';
    var newVal = !isOn;

    // Update UI
    toggle.setAttribute('data-on', newVal ? '1' : '0');
    toggle.style.background = newVal ? 'var(--primary)' : 'var(--border)';
    var dot = toggle.querySelector('div');
    if (dot) { dot.style.left = newVal ? 'auto' : '2px'; dot.style.right = newVal ? '2px' : 'auto'; }

    // Save
    var data = {};
    data[key] = newVal;
    if (key === 'dark_mode') data[key] = newVal ? 'dark' : 'light';
    SS.PrefsPage._save(data);

    // Apply immediately
    if (key === 'dark_mode' && SS.DarkMode) SS.DarkMode.toggle();
    if (key === 'notif_sound' && SS.NotifSound) SS.NotifSound.toggle();
  },

  _onSelect: function(key, value) {
    var data = {};
    data[key] = value;
    SS.PrefsPage._save(data);
  },

  _save: function(data) {
    SS.api.post('/preferences.php', data).then(function(d) {
      SS.PrefsPage._prefs = d.data || SS.PrefsPage._prefs;
    });
  }
};
