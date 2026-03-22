/**
 * ShipperShop Component — Admin Audit Trail
 */
window.SS = window.SS || {};

SS.AdminAudit = {
  show: function(page) {
    page = page || 1;
    SS.api.get('/admin-audit.php?page=' + page + '&limit=20').then(function(d) {
      var data = d.data || {};
      var logs = data.logs || [];
      var html = '';
      for (var i = 0; i < logs.length; i++) {
        var l = logs[i];
        html += '<div class="flex gap-2 p-2" style="border-bottom:1px solid var(--border-light);font-size:12px">'
          + '<img src="' + (l.avatar || '/assets/img/defaults/avatar.svg') + '" class="avatar avatar-xs" loading="lazy">'
          + '<div class="flex-1"><div><span class="font-medium">' + SS.utils.esc(l.fullname || 'System') + '</span> <span class="chip" style="font-size:10px">' + SS.utils.esc(l.action) + '</span></div>'
          + '<div class="text-muted mt-1">' + SS.utils.ago(l.created_at) + (l.ip ? ' · ' + l.ip : '') + '</div></div></div>';
      }
      if (data.has_more) html += '<div class="text-center mt-2"><button class="btn btn-ghost btn-sm" onclick="SS.AdminAudit.show(' + (page + 1) + ')">Xem them</button></div>';
      if (!logs.length) html = '<div class="empty-state p-3"><div class="empty-text">Khong co log</div></div>';
      SS.ui.sheet({title: 'Nhat ky he thong (' + (data.total || 0) + ')', html: html});
    });
  }
};
