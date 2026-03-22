window.SS = window.SS || {};
SS.WebhookLogs = {
  show: function() {
    SS.api.get('/webhook-logs.php').then(function(d) {
      var data = d.data || {};
      var stats = data.stats || {};
      var html = '<div class="flex gap-2 mb-3 text-center"><div class="card" style="padding:10px;flex:1"><div class="font-bold">' + (stats.total || 0) + '</div><div class="text-xs text-muted">Tong</div></div>'
        + '<div class="card" style="padding:10px;flex:1"><div class="font-bold" style="color:var(--success)">' + (stats.success_rate || 100) + '%</div><div class="text-xs text-muted">Success</div></div>'
        + '<div class="card" style="padding:10px;flex:1"><div class="font-bold" style="color:var(--danger)">' + (stats.failed || 0) + '</div><div class="text-xs text-muted">Failed</div></div></div>';
      var logs = data.deploy_logs || [];
      if (logs.length) {
        html += '<div class="text-sm font-bold mb-2">Deploy logs</div>';
        for (var i = 0; i < Math.min(logs.length, 10); i++) {
          var l = logs[i];
          html += '<div class="text-xs p-1" style="border-bottom:1px solid var(--border-light)">' + SS.utils.esc(l.action) + ' · ' + SS.utils.ago(l.created_at) + '</div>';
        }
      }
      SS.ui.sheet({title: '🔗 Webhook Logs', html: html});
    });
  }
};
