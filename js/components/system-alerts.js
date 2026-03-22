window.SS = window.SS || {};
SS.SystemAlerts = {
  show: function() {
    SS.api.get('/system-alerts.php').then(function(d) {
      var data = d.data || {};
      var alerts = data.alerts || [];
      var sevColors = {healthy: 'var(--success)', warning: 'var(--warning)', critical: 'var(--danger)'};
      var sevIcons = {healthy: '✅', warning: '⚠️', critical: '🚨'};
      var html = '<div class="text-center mb-3"><div style="font-size:32px">' + (sevIcons[data.severity] || '✅') + '</div><div class="font-bold" style="color:' + (sevColors[data.severity] || '') + '">' + SS.utils.esc(data.severity || 'healthy') + '</div></div>';
      html += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-bottom:16px;text-align:center;font-size:11px">'
        + '<div class="card" style="padding:6px"><div class="font-bold">' + (data.disk_pct || 0) + '%</div><div class="text-muted">Disk</div></div>'
        + '<div class="card" style="padding:6px"><div class="font-bold">' + (data.db_mb || 0) + 'MB</div><div class="text-muted">DB</div></div>'
        + '<div class="card" style="padding:6px"><div class="font-bold">' + (data.errors_24h || 0) + '</div><div class="text-muted">Errors</div></div></div>';
      for (var i = 0; i < alerts.length; i++) {
        var a = alerts[i];
        var typeColors = {info: 'var(--primary)', warning: 'var(--warning)', critical: 'var(--danger)'};
        html += '<div class="card mb-2" style="padding:10px;border-left:3px solid ' + (typeColors[a.type] || 'var(--primary)') + '">'
          + '<div class="text-sm">' + (a.icon || '') + ' ' + SS.utils.esc(a.message) + '</div>'
          + '<div class="text-xs text-muted">' + SS.utils.esc(a.category || '') + ' · ' + SS.utils.esc(a.type || '') + '</div></div>';
      }
      SS.ui.sheet({title: '🔔 System Alerts', html: html});
    });
  }
};
