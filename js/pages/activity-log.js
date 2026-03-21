/**
 * ShipperShop Page — Activity Log (activity-log.html)
 * User's activity timeline
 * Uses: SS.api, SS.ui, SS.utils
 */
window.SS = window.SS || {};

SS.ActivityLog = {
  _page: 1,
  _loading: false,

  init: function() {
    if (!SS.store || !SS.store.isLoggedIn()) {
      window.location.href = '/login.html';
      return;
    }
    SS.ActivityLog.load(false);
  },

  load: function(append) {
    if (SS.ActivityLog._loading) return;
    SS.ActivityLog._loading = true;

    var el = document.getElementById('al-list');
    if (!el) { SS.ActivityLog._loading = false; return; }
    if (!append) el.innerHTML = '<div class="p-4 text-center"><div class="spin" style="width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';

    SS.api.v1.get('/activity-log.php?page=' + SS.ActivityLog._page + '&limit=20').then(function(d) {
      var items = d.data || [];
      if (!append) el.innerHTML = '';

      if (!items.length) {
        if (!append) el.innerHTML = '<div class="empty-state"><div class="empty-icon">📋</div><div class="empty-text">Chưa có hoạt động nào</div></div>';
        SS.ActivityLog._loading = false;
        return;
      }

      var typeIcons = {post:'📝',comment:'💬',like:'✅',follow:'👤',group_join:'👥',checkin:'🔥',login:'🔑'};
      var html = '';
      for (var i = 0; i < items.length; i++) {
        var a = items[i];
        var icon = typeIcons[a.type] || '📌';
        html += '<div class="list-item" style="align-items:flex-start">'
          + '<div style="width:36px;height:36px;border-radius:50%;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0">' + icon + '</div>'
          + '<div class="flex-1">'
          + '<div class="text-sm">' + SS.utils.esc(a.description || a.message || '') + '</div>'
          + '<div class="text-xs text-muted mt-1">' + SS.utils.ago(a.created_at) + '</div>'
          + '</div></div>';
      }
      el.insertAdjacentHTML('beforeend', html);
      SS.ActivityLog._page++;
      SS.ActivityLog._loading = false;
    }).catch(function() {
      SS.ActivityLog._loading = false;
      if (!append) el.innerHTML = '<div class="empty-state"><div class="empty-text">Lỗi tải hoạt động</div></div>';
    });
  }
};
