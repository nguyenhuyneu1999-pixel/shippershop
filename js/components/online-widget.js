/**
 * ShipperShop Component — Online Users Widget
 * Shows online users in sidebar, auto-refreshes
 */
window.SS = window.SS || {};

SS.OnlineWidget = {
  _timer: null,

  render: function(containerId) {
    var el = document.getElementById(containerId);
    if (!el) return;

    el.innerHTML = '<div class="card"><div class="card-header flex justify-between items-center">Đang online <span id="ow-count" class="badge badge-success">0</span></div><div id="ow-list" style="max-height:300px;overflow-y:auto"></div></div>';

    SS.OnlineWidget.load();
    // Refresh every 60s
    SS.OnlineWidget._timer = setInterval(SS.OnlineWidget.load, 60000);
  },

  load: function() {
    SS.api.get('/social.php?action=online&limit=15').then(function(d) {
      var users = d.data || [];
      var countEl = document.getElementById('ow-count');
      var listEl = document.getElementById('ow-list');
      if (countEl) countEl.textContent = users.length;
      if (!listEl) return;

      if (!users.length) {
        listEl.innerHTML = '<div class="p-3 text-center text-muted text-sm">Chưa ai online</div>';
        return;
      }

      var html = '';
      for (var i = 0; i < users.length; i++) {
        var u = users[i];
        html += '<a href="/user.html?id=' + u.id + '" class="list-item" style="text-decoration:none;color:var(--text);padding:8px 16px">'
          + '<div style="position:relative">'
          + '<img class="avatar avatar-sm" src="' + (u.avatar || '/assets/img/defaults/avatar.svg') + '" loading="lazy">'
          + '<div style="position:absolute;bottom:0;right:0;width:10px;height:10px;border-radius:50%;background:var(--success);border:2px solid var(--card)"></div>'
          + '</div>'
          + '<div class="flex-1 truncate text-sm font-medium">' + SS.utils.esc(u.fullname) + '</div>'
          + '</a>';
      }
      listEl.innerHTML = html;
    }).catch(function() {});
  },

  destroy: function() {
    if (SS.OnlineWidget._timer) {
      clearInterval(SS.OnlineWidget._timer);
      SS.OnlineWidget._timer = null;
    }
  }
};
