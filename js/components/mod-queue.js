/**
 * ShipperShop Component — Moderation Queue (Admin)
 */
window.SS = window.SS || {};

SS.ModQueue = {
  show: function() {
    SS.api.get('/mod-queue.php').then(function(d) {
      var data = d.data || {};
      var reports = data.reports || [];
      var counts = data.counts || {};

      var html = '<div class="flex gap-3 mb-3 text-center"><div class="card" style="padding:8px;flex:1"><div class="font-bold" style="color:var(--warning)">' + (counts.pending || 0) + '</div><div class="text-xs text-muted">Cho xu ly</div></div>'
        + '<div class="card" style="padding:8px;flex:1"><div class="font-bold" style="color:var(--success)">' + (counts.reviewed || 0) + '</div><div class="text-xs text-muted">Da xu ly</div></div></div>';

      if (!reports.length) {
        html += '<div class="empty-state p-3"><div class="empty-icon">✅</div><div class="empty-text">Khong co bao cao moi</div></div>';
      }

      for (var i = 0; i < reports.length; i++) {
        var r = reports[i];
        html += '<div class="card mb-2" style="padding:10px;border-left:3px solid var(--warning)">'
          + '<div class="text-xs text-muted mb-1">📢 ' + SS.utils.esc(r.reporter_name || '') + ' bao cao bai cua ' + SS.utils.esc(r.author_name || '') + '</div>'
          + '<div class="text-sm">' + SS.utils.esc((r.post_content || '').substring(0, 100)) + '</div>'
          + '<div class="flex gap-2 mt-2"><button class="btn btn-success btn-sm" onclick="SS.ModQueue.approve(' + r.id + ')">Bo qua</button>'
          + '<button class="btn btn-danger btn-sm" onclick="SS.ModQueue.remove(' + r.id + ',' + r.post_id + ')">Go bai</button></div></div>';
      }
      SS.ui.sheet({title: 'Kiem duyet (' + (counts.pending || 0) + ')', html: html});
    });
  },

  approve: function(reportId) {
    SS.api.post('/mod-queue.php?action=approve', {report_id: reportId}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.ModQueue.show(); });
  },
  remove: function(reportId, postId) {
    SS.ui.confirm('Go bai viet nay?', function() {
      SS.api.post('/mod-queue.php?action=remove', {report_id: reportId, post_id: postId}).then(function(d) { SS.ui.toast(d.message, 'success'); SS.ModQueue.show(); });
    });
  }
};
