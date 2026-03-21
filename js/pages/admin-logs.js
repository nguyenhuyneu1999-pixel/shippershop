/**
 * ShipperShop Page — Admin Logs
 * View audit logs, cron logs, errors, login attempts
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.AdminLogs = {
  _tab: 'audit',
  _page: 1,

  init: function() {
    SS.AdminLogs.loadTab('audit');
  },

  loadTab: function(tab) {
    SS.AdminLogs._tab = tab;
    SS.AdminLogs._page = 1;
    SS.AdminLogs._load();
  },

  _load: function() {
    var el = document.getElementById('logs-content');
    if (!el) return;
    el.innerHTML = '<div class="p-4 text-center"><div class="spin" style="width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;display:inline-block"></div></div>';

    var tab = SS.AdminLogs._tab;
    SS.api.get('/logs.php?action=' + tab + '&page=' + SS.AdminLogs._page + '&limit=25').then(function(d) {
      var data = d.data || {};
      var logs = data.logs || d.data || [];
      if (!Array.isArray(logs)) logs = [];

      var tabs = '<div class="flex gap-2 mb-3 overflow-x-auto">';
      var tabList = [{id:'audit',label:'Audit',icon:'fa-clipboard'},{id:'cron',label:'Cron',icon:'fa-clock'},{id:'errors',label:'Errors',icon:'fa-bug'},{id:'login_attempts',label:'Login',icon:'fa-key'},{id:'rate_limits',label:'Rate Limits',icon:'fa-shield'}];
      for (var i = 0; i < tabList.length; i++) {
        var t = tabList[i];
        tabs += '<button class="btn btn-sm ' + (tab === t.id ? 'btn-primary' : 'btn-outline') + '" onclick="SS.AdminLogs.loadTab(\'' + t.id + '\')"><i class="fa-solid ' + t.icon + '"></i> ' + t.label + '</button>';
      }
      tabs += '</div>';

      if (!logs.length) {
        el.innerHTML = tabs + '<div class="empty-state p-4"><div class="empty-text">Không có log</div></div>';
        return;
      }

      var html = tabs;
      for (var j = 0; j < logs.length; j++) {
        var l = logs[j];
        html += '<div class="list-item" style="align-items:flex-start;border-bottom:1px solid var(--border-light)">'
          + '<div class="flex-1" style="min-width:0">'
          + '<div class="flex items-center gap-2">'
          + '<span class="text-xs font-mono" style="color:var(--primary)">' + SS.utils.esc(l.action || l.job_name || l.level || l.endpoint || '') + '</span>'
          + (l.user_name ? '<span class="text-xs text-muted">' + SS.utils.esc(l.user_name) + '</span>' : '')
          + (l.status ? '<span class="badge ' + (l.status === 'success' || l.status === 'OK' ? 'badge-success' : 'badge-danger') + '" style="font-size:10px">' + l.status + '</span>' : '')
          + '</div>'
          + '<div class="text-xs mt-1" style="word-break:break-all;color:var(--text-secondary)">' + SS.utils.esc((l.detail || l.message || l.content || l.email || '').substring(0, 200)) + '</div>'
          + '<div class="text-xs text-muted mt-1">' + SS.utils.formatDateTime(l.created_at) + (l.ip ? ' · ' + l.ip : '') + (l.duration_ms ? ' · ' + l.duration_ms + 'ms' : '') + (l.hits ? ' · ' + l.hits + ' hits' : '') + '</div>'
          + '</div></div>';
      }

      // Pagination
      var meta = data.meta || {};
      if (meta.total > 25) {
        html += '<div class="flex justify-center gap-2 mt-3">';
        if (SS.AdminLogs._page > 1) html += '<button class="btn btn-sm btn-ghost" onclick="SS.AdminLogs._page--;SS.AdminLogs._load()">← Trước</button>';
        html += '<span class="text-sm text-muted" style="line-height:32px">Trang ' + SS.AdminLogs._page + '/' + Math.ceil(meta.total / 25) + '</span>';
        if (SS.AdminLogs._page * 25 < meta.total) html += '<button class="btn btn-sm btn-ghost" onclick="SS.AdminLogs._page++;SS.AdminLogs._load()">Sau →</button>';
        html += '</div>';
      }

      el.innerHTML = html;
    }).catch(function(e) {
      el.innerHTML = '<div class="p-4 text-center text-danger">Lỗi: ' + (e && e.message ? SS.utils.esc(e.message) : '') + '</div>';
    });
  }
};
