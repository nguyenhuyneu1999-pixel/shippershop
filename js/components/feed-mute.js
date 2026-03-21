/**
 * ShipperShop Component — Feed Mute
 * Mute users from feed (without unfollowing), manage muted list
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.FeedMute = {

  // Mute/unmute a user
  toggle: function(userId, userName) {
    if (!SS.store || !SS.store.isLoggedIn()) return;
    SS.api.post('/feed-prefs.php?action=mute_user', {user_id: userId}).then(function(d) {
      var muted = d.data && d.data.muted;
      SS.ui.toast(muted ? SS.utils.esc(userName) + ' đã bị ẩn khỏi feed' : 'Đã hiện lại ' + SS.utils.esc(userName), muted ? 'info' : 'success');
    });
  },

  // Show muted users list
  showMuted: function() {
    SS.api.get('/feed-prefs.php').then(function(d) {
      var prefs = d.data || {};
      var muted = prefs.muted_users || [];
      if (!muted.length) {
        SS.ui.sheet({title: 'Người đã ẩn', html: '<div class="empty-state p-4"><div class="empty-text">Chưa ẩn ai</div></div>'});
        return;
      }
      // Load user details
      var promises = [];
      var html = '';
      // Simple: just show IDs with unmute button
      for (var i = 0; i < muted.length; i++) {
        html += '<div class="list-item" id="muted-' + muted[i] + '">'
          + '<div class="flex-1">User #' + muted[i] + '</div>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.FeedMute.toggle(' + muted[i] + ',\'User #' + muted[i] + '\');document.getElementById(\'muted-' + muted[i] + '\').remove()">Bỏ ẩn</button></div>';
      }
      SS.ui.sheet({title: 'Người đã ẩn (' + muted.length + ')', html: html});
    });
  },

  // Show feed preferences dialog
  showPreferences: function() {
    SS.api.get('/feed-prefs.php').then(function(d) {
      var prefs = d.data || {};
      var verFirst = prefs.show_verified_first || false;
      var compact = prefs.compact_mode || false;

      var html = '<div class="list-item">'
        + '<div class="flex-1"><div class="font-medium text-sm">Hiện bài xác minh trước</div><div class="text-xs text-muted">Ưu tiên bài từ shipper đã xác minh</div></div>'
        + '<label class="toggle"><input type="checkbox" id="fp-verified" ' + (verFirst ? 'checked' : '') + ' onchange="SS.FeedMute._savePrefs()"><span class="toggle-slider"></span></label></div>'
        + '<div class="list-item">'
        + '<div class="flex-1"><div class="font-medium text-sm">Chế độ thu gọn</div><div class="text-xs text-muted">Hiện ít nội dung hơn trên mỗi bài</div></div>'
        + '<label class="toggle"><input type="checkbox" id="fp-compact" ' + (compact ? 'checked' : '') + ' onchange="SS.FeedMute._savePrefs()"><span class="toggle-slider"></span></label></div>'
        + '<div class="divider"></div>'
        + '<div class="list-item" onclick="SS.FeedMute.showMuted()" style="cursor:pointer">'
        + '<i class="fa-solid fa-eye-slash" style="color:var(--text-muted);width:20px"></i>'
        + '<div class="flex-1">Người đã ẩn (' + (prefs.muted_users || []).length + ')</div>'
        + '<i class="fa-solid fa-chevron-right text-muted" style="font-size:12px"></i></div>'
        + '<style>.toggle{position:relative;width:44px;height:24px;display:inline-block}.toggle input{opacity:0;width:0;height:0}.toggle-slider{position:absolute;inset:0;background:var(--border);border-radius:24px;cursor:pointer;transition:.3s}.toggle-slider:before{content:"";position:absolute;width:18px;height:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s}.toggle input:checked+.toggle-slider{background:var(--primary)}.toggle input:checked+.toggle-slider:before{transform:translateX(20px)}</style>';

      SS.ui.sheet({title: 'Tùy chỉnh feed', html: html});
    });
  },

  _savePrefs: function() {
    var data = {
      show_verified_first: document.getElementById('fp-verified') ? document.getElementById('fp-verified').checked : false,
      compact_mode: document.getElementById('fp-compact') ? document.getElementById('fp-compact').checked : false,
    };
    SS.api.post('/feed-prefs.php?action=update', data).then(function() {
      SS.ui.toast('Đã lưu!', 'success', 1500);
    });
  }
};
