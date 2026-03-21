/**
 * ShipperShop Component — Privacy Settings
 * Toggle switches for profile visibility, online status, read receipts, etc.
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.PrivacySettings = {

  show: function() {
    SS.api.get('/privacy.php').then(function(d) {
      var p = d.data || {};

      var toggles = [
        {key: 'profile_visibility', label: 'Ai xem được trang cá nhân', type: 'select', options: [
          {value: 'public', label: 'Tất cả'},
          {value: 'followers', label: 'Người theo dõi'},
          {value: 'private', label: 'Chỉ mình tôi'}
        ]},
        {key: 'allow_messages_from', label: 'Ai nhắn tin được', type: 'select', options: [
          {value: 'everyone', label: 'Tất cả'},
          {value: 'followers', label: 'Người theo dõi'},
          {value: 'nobody', label: 'Không ai'}
        ]},
        {key: 'show_online_status', label: 'Hiện trạng thái online'},
        {key: 'show_read_receipts', label: 'Hiện đã đọc tin nhắn'},
        {key: 'hide_last_seen', label: 'Ẩn lần cuối hoạt động'},
        {key: 'show_activity_log', label: 'Hiện nhật ký hoạt động'},
        {key: 'show_followers_list', label: 'Hiện danh sách người theo dõi'},
        {key: 'show_badges', label: 'Hiện huy hiệu'},
        {key: 'show_reputation', label: 'Hiện điểm uy tín'},
        {key: 'show_in_search', label: 'Hiện trong tìm kiếm'},
        {key: 'allow_group_invites', label: 'Cho phép mời vào nhóm'},
      ];

      var html = '';
      for (var i = 0; i < toggles.length; i++) {
        var t = toggles[i];
        if (t.type === 'select') {
          html += '<div class="list-item"><div class="flex-1 text-sm">' + t.label + '</div>'
            + '<select id="priv-' + t.key + '" class="form-select" style="width:auto;font-size:12px;padding:4px 8px" onchange="SS.PrivacySettings._save()">';
          for (var j = 0; j < t.options.length; j++) {
            var o = t.options[j];
            html += '<option value="' + o.value + '"' + (p[t.key] === o.value ? ' selected' : '') + '>' + o.label + '</option>';
          }
          html += '</select></div>';
        } else {
          var checked = t.key === 'hide_last_seen' ? !!p[t.key] : !!p[t.key];
          html += '<div class="list-item"><div class="flex-1 text-sm">' + t.label + '</div>'
            + '<label class="toggle"><input type="checkbox" id="priv-' + t.key + '"' + (checked ? ' checked' : '') + ' onchange="SS.PrivacySettings._save()"><span class="toggle-slider"></span></label></div>';
        }
      }

      html += '<style>.toggle{position:relative;width:44px;height:24px;display:inline-block}.toggle input{opacity:0;width:0;height:0}.toggle-slider{position:absolute;inset:0;background:var(--border);border-radius:24px;cursor:pointer;transition:.3s}.toggle-slider:before{content:"";position:absolute;width:18px;height:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s}.toggle input:checked+.toggle-slider{background:var(--primary)}.toggle input:checked+.toggle-slider:before{transform:translateX(20px)}</style>';

      SS.ui.sheet({title: 'Cài đặt riêng tư', html: html});
    });
  },

  _save: function() {
    var keys = ['profile_visibility', 'allow_messages_from', 'show_online_status', 'show_read_receipts',
      'hide_last_seen', 'show_activity_log', 'show_followers_list', 'show_badges',
      'show_reputation', 'show_in_search', 'allow_group_invites'];
    var data = {};
    for (var i = 0; i < keys.length; i++) {
      var el = document.getElementById('priv-' + keys[i]);
      if (!el) continue;
      if (el.tagName === 'SELECT') data[keys[i]] = el.value;
      else data[keys[i]] = el.checked;
    }
    SS.api.post('/privacy.php', data).then(function() {
      SS.ui.toast('Đã lưu!', 'success', 1500);
    });
  }
};
