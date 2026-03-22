window.SS = window.SS || {};
SS.AdminAuditV2 = {
  show: function(page) {
    page = page || 1;
    SS.api.get('/admin-audit-v2.php?page=' + page).then(function(d) {
      var data = d.data || {};
      var logs = data.logs || [];
      var html = '<div class="text-xs text-muted mb-2">' + (data.total || 0) + ' logs · Trang ' + (data.page || 1) + '/' + (data.pages || 1) + '</div>';
      for (var i = 0; i < logs.length; i++) {
        var l = logs[i];
        html += '<div class="text-xs p-1" style="border-bottom:1px solid var(--border-light)"><span class="font-bold">' + SS.utils.esc(l.action || '') + '</span> · ' + SS.utils.esc(l.fullname || 'System') + ' · ' + SS.utils.esc(l.ip || '') + ' · ' + SS.utils.ago(l.created_at) + '</div>';
      }
      if (data.pages > 1) {
        html += '<div class="flex justify-between mt-2">';
        if (page > 1) html += '<button class="btn btn-ghost btn-sm" onclick="SS.AdminAuditV2.show(' + (page - 1) + ')">◀ Truoc</button>'; else html += '<span></span>';
        if (page < data.pages) html += '<button class="btn btn-ghost btn-sm" onclick="SS.AdminAuditV2.show(' + (page + 1) + ')">Sau ▶</button>';
        html += '</div>';
      }
      SS.ui.sheet({title: '📋 Audit Log v2', html: html});
    });
  }
};
