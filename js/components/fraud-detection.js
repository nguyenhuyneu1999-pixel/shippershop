window.SS = window.SS || {};
SS.FraudDetection = {
  show: function() {
    SS.api.get('/fraud-detection.php').then(function(d) {
      var data = d.data || {};
      var alerts = data.alerts || [];
      var riskColors = {low: 'var(--success)', medium: 'var(--warning)', high: 'var(--danger)', critical: '#dc2626'};
      var typeIcons = {high_liker: '👍', rapid_post: '⚡', duplicate: '📋', brute_force: '🔐'};
      var html = '<div class="text-center mb-3"><div class="font-bold text-lg" style="color:' + (riskColors[data.risk_level] || '') + '">' + (data.risk_level || 'low').toUpperCase() + '</div><div class="text-xs text-muted">' + (data.total_alerts || 0) + ' alerts</div></div>';
      var sc = data.severity_counts || {};
      html += '<div class="flex gap-2 mb-3 text-center">';
      ['critical', 'high', 'medium', 'low'].forEach(function(s) { html += '<div class="chip" style="background:' + riskColors[s] + '15;color:' + riskColors[s] + ';font-size:10px">' + s + ': ' + (sc[s] || 0) + '</div>'; });
      html += '</div>';
      for (var i = 0; i < alerts.length; i++) {
        var a = alerts[i];
        html += '<div class="card mb-2" style="padding:10px;border-left:3px solid ' + (riskColors[a.severity] || '') + '"><div class="flex justify-between"><span class="text-sm font-bold">' + (typeIcons[a.type] || '⚠️') + ' ' + SS.utils.esc(a.type) + '</span><span class="text-xs" style="color:' + (riskColors[a.severity] || '') + '">' + SS.utils.esc(a.severity) + '</span></div>'
          + '<div class="text-xs mt-1">' + SS.utils.esc(a.detail) + '</div>'
          + (a.user_name ? '<div class="text-xs text-muted">👤 ' + SS.utils.esc(a.user_name) + '</div>' : '') + '</div>';
      }
      if (!alerts.length) html += '<div class="text-center text-sm" style="color:var(--success)">✅ Khong phat hien bat thuong</div>';
      SS.ui.sheet({title: '🔍 Fraud Detection', html: html});
    });
  }
};
