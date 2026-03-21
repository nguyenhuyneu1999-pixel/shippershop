/**
 * ShipperShop Component — Follow Requests
 * Manage pending follow requests for private accounts
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.FollowRequests = {

  show: function() {
    SS.api.get('/follow-requests.php').then(function(d) {
      var data = d.data || {};
      var requests = data.requests || [];

      if (!requests.length) {
        SS.ui.sheet({title: 'Yeu cau theo doi', html: '<div class="empty-state p-4"><div class="empty-icon">👥</div><div class="empty-text">Khong co yeu cau nao</div></div>'});
        return;
      }

      var html = '<div class="text-sm text-muted mb-3">' + requests.length + ' yeu cau dang cho</div>';
      for (var i = 0; i < requests.length; i++) {
        var r = requests[i];
        var u = r.user || {};
        html += '<div id="freq-' + u.id + '" class="flex items-center gap-3 p-3" style="border-bottom:1px solid var(--border-light)">'
          + '<img src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-sm" onclick="window.location.href=\'/user.html?id=' + u.id + '\'" style="cursor:pointer" loading="lazy">'
          + '<div class="flex-1"><div class="font-medium text-sm">' + SS.utils.esc(u.fullname || '') + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(u.shipping_company || '') + ' · ' + SS.utils.ago(r.created_at) + '</div></div>'
          + '<div class="flex gap-2">'
          + '<button class="btn btn-primary btn-sm" onclick="SS.FollowRequests._respond(' + u.id + ',\'accept\')">Chap nhan</button>'
          + '<button class="btn btn-ghost btn-sm" onclick="SS.FollowRequests._respond(' + u.id + ',\'reject\')">Tu choi</button>'
          + '</div></div>';
      }
      SS.ui.sheet({title: 'Yeu cau theo doi (' + requests.length + ')', html: html});
    });
  },

  _respond: function(userId, action) {
    SS.api.post('/follow-requests.php?action=' + action, {user_id: userId}).then(function(d) {
      var el = document.getElementById('freq-' + userId);
      if (el) el.remove();
      SS.ui.toast(d.message || 'OK', 'success', 2000);
    });
  },

  // Badge showing pending count
  renderBadge: function(containerId) {
    SS.api.get('/follow-requests.php').then(function(d) {
      var count = (d.data || {}).count || 0;
      var el = document.getElementById(containerId);
      if (!el) return;
      if (count > 0) {
        el.innerHTML = '<span class="badge badge-danger">' + count + '</span>';
        el.style.display = 'inline';
      } else {
        el.innerHTML = '';
      }
    }).catch(function() {});
  }
};
