window.SS = window.SS || {};
SS.PlatformHealthV2 = {
  show: function() {
    SS.api.get('/platform-health-v2.php').then(function(d) {
      var data = d.data || {};
      var checks = data.checks || [];
      var statusColors = {ok: 'var(--success)', warning: 'var(--warning)', critical: 'var(--danger)'};
      var overallColors = {healthy: 'var(--success)', degraded: 'var(--warning)', unhealthy: 'var(--danger)'};
      var html = '<div class="text-center mb-3"><div class="font-bold text-lg" style="color:' + (overallColors[data.overall] || '') + '">' + (data.overall || '?').toUpperCase() + '</div><div class="text-xs text-muted">' + (data.ok_count || 0) + '/' + (data.total_checks || 0) + ' OK · ' + (data.check_time_ms || 0) + 'ms</div></div>';
      for (var i = 0; i < checks.length; i++) {
        var c = checks[i];
        html += '<div class="flex justify-between items-center p-2" style="border-bottom:1px solid var(--border-light)"><div class="flex items-center gap-2"><div style="width:8px;height:8px;border-radius:50%;background:' + (statusColors[c.status] || '') + '"></div><span class="text-sm">' + SS.utils.esc(c.name) + '</span></div><span class="text-sm font-bold">' + SS.utils.esc(String(c.value)) + '</span></div>';
      }
      SS.ui.sheet({title: '💚 Platform Health v2', html: html});
    });
  }
};
