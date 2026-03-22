window.SS = window.SS || {};
SS.LoginMonitor = {
  show: function() {
    SS.api.get('/login-monitor.php').then(function(d) {
      var data = d.data || {};
      var html = '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px;text-align:center">'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg">' + (data.today || 0) + '</div><div class="text-xs text-muted">Hom nay</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg" style="color:var(--danger)">' + (data.fail_rate || 0) + '%</div><div class="text-xs text-muted">Fail rate</div></div>'
        + '<div class="card" style="padding:10px"><div class="font-bold text-lg" style="color:var(--warning)">' + ((data.suspicious_ips || []).length) + '</div><div class="text-xs text-muted">Suspicious</div></div></div>';
      var recent = data.recent || [];
      html += '<div class="text-sm font-bold mb-2">Gan day</div>';
      for (var i = 0; i < Math.min(recent.length, 10); i++) {
        var r = recent[i];
        html += '<div class="flex justify-between text-xs p-1" style="border-bottom:1px solid var(--border-light)"><span>' + SS.utils.esc(r.email || '').substring(0, 20) + ' · ' + SS.utils.esc(r.ip || '') + '</span><span style="color:' + (r.success ? 'var(--success)' : 'var(--danger)') + '">' + (r.success ? '✅' : '❌') + '</span></div>';
      }
      SS.ui.sheet({title: '🔐 Login Monitor', html: html});
    });
  }
};
