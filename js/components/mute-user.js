/**
 * ShipperShop Component — Mute User
 * Hide a user's posts without blocking (they don't know)
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.MuteUser = {
  _mutedIds: null,

  // Toggle mute
  toggle: function(userId) {
    if (!SS.store || !SS.store.isLoggedIn()) return;
    SS.api.post('/mute.php?action=toggle', {user_id: userId}).then(function(d) {
      var muted = d.data && d.data.muted;
      SS.ui.toast(d.message || (muted ? 'Đã ẩn' : 'Đã bỏ ẩn'), 'success', 2000);
      SS.MuteUser._mutedIds = null; // Invalidate cache
    });
  },

  // Check if muted
  isMuted: function(userId, callback) {
    if (!SS.store || !SS.store.isLoggedIn()) { if (callback) callback(false); return; }
    SS.api.get('/mute.php?action=check&user_id=' + userId).then(function(d) {
      if (callback) callback(d.data && d.data.muted);
    }).catch(function() { if (callback) callback(false); });
  },

  // Get all muted IDs (for feed filtering)
  getMutedIds: function(callback) {
    if (SS.MuteUser._mutedIds !== null) { if (callback) callback(SS.MuteUser._mutedIds); return; }
    if (!SS.store || !SS.store.isLoggedIn()) { if (callback) callback([]); return; }
    SS.api.get('/mute.php?action=ids').then(function(d) {
      SS.MuteUser._mutedIds = (d.data && d.data.ids) || [];
      if (callback) callback(SS.MuteUser._mutedIds);
    }).catch(function() { if (callback) callback([]); });
  },

  // Show muted list
  showList: function() {
    SS.api.get('/mute.php?action=list').then(function(d) {
      var users = d.data || [];
      if (!users.length) { SS.ui.toast('Chưa ẩn ai', 'info'); return; }
      var html = '';
      for (var i = 0; i < users.length; i++) {
        var u = users[i];
        html += '<div class="list-item">'
          + '<img class="avatar avatar-sm" src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy">'
          + '<div class="flex-1"><div class="list-title">' + SS.utils.esc(u.fullname) + '</div>'
          + '<div class="list-subtitle">' + SS.utils.esc(u.shipping_company || '') + '</div></div>'
          + '<button class="btn btn-sm btn-ghost" onclick="SS.MuteUser.toggle(' + u.id + ');this.closest(\'.list-item\').remove()">Bỏ ẩn</button>'
          + '</div>';
      }
      SS.ui.sheet({title: 'Người đã ẩn (' + users.length + ')', html: html});
    });
  }
};
