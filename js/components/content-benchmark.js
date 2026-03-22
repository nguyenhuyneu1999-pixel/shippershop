window.SS = window.SS || {};
SS.ContentBenchmark = {
  show: function(days) {
    days = days || 30;
    SS.api.get('/content-benchmark.php?days=' + days).then(function(d) {
      var data = d.data || {};
      var metrics = data.metrics || [];
      var html = '<div class="flex gap-2 mb-3">';
      [7, 30, 90].forEach(function(dd) { html += '<div class="chip ' + (dd === days ? 'chip-active' : '') + '" onclick="SS.ContentBenchmark.show(' + dd + ')" style="cursor:pointer">' + dd + 'd</div>'; });
      html += '</div>';
      // Rank badge
      html += '<div class="text-center mb-3"><div class="font-bold text-lg" style="color:var(--primary)">Top ' + (data.percentile || 0) + '%</div><div class="text-xs text-muted">Hang #' + (data.rank || 0) + ' / ' + (data.total_users || 0) + ' users</div></div>';
      // Metrics comparison
      for (var i = 0; i < metrics.length; i++) {
        var m = metrics[i];
        var maxVal = Math.max(m.user || 0, m.avg || 0, m.top10 || 0, 1);
        var userPct = Math.round((m.user || 0) / maxVal * 100);
        var avgPct = Math.round((m.avg || 0) / maxVal * 100);
        var topPct = Math.round((m.top10 || 0) / maxVal * 100);
        var userColor = (m.user || 0) >= (m.avg || 0) ? 'var(--success)' : 'var(--warning)';
        html += '<div class="card mb-2" style="padding:10px"><div class="text-sm font-bold mb-2">' + SS.utils.esc(m.name) + '</div>'
          + '<div class="mb-1"><div class="flex justify-between text-xs"><span style="color:' + userColor + '">Ban: ' + m.user + '</span></div><div style="height:8px;background:var(--border-light);border-radius:4px"><div style="width:' + userPct + '%;height:100%;background:' + userColor + ';border-radius:4px"></div></div></div>'
          + '<div class="mb-1"><div class="flex justify-between text-xs"><span>TB: ' + m.avg + '</span></div><div style="height:6px;background:var(--border-light);border-radius:3px"><div style="width:' + avgPct + '%;height:100%;background:var(--text-muted);border-radius:3px;opacity:0.5"></div></div></div>'
          + '<div><div class="flex justify-between text-xs"><span style="color:var(--primary)">Top 10%: ' + m.top10 + '</span></div><div style="height:6px;background:var(--border-light);border-radius:3px"><div style="width:' + topPct + '%;height:100%;background:var(--primary);border-radius:3px;opacity:0.3"></div></div></div></div>';
      }
      SS.ui.sheet({title: '📊 Benchmark', html: html});
    });
  }
};
