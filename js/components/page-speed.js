window.SS = window.SS || {};
SS.PageSpeed = {
  show: function() {
    SS.api.get('/page-speed.php').then(function(d) {
      var data = d.data || {};
      var pages = data.pages || [];
      var html = '<div class="flex gap-2 mb-3 text-center"><div class="card" style="padding:10px;flex:1"><div class="font-bold text-lg" style="color:' + ((data.avg_score || 0) >= 80 ? 'var(--success)' : 'var(--warning)') + '">' + (data.avg_score || 0) + '</div><div class="text-xs text-muted">Avg Score</div></div>'
        + '<div class="card" style="padding:10px;flex:1"><div class="font-bold text-lg">' + (data.avg_load_ms || 0) + 'ms</div><div class="text-xs text-muted">Avg Load</div></div></div>';
      for (var i = 0; i < pages.length; i++) {
        var p = pages[i];
        var color = p.score >= 80 ? 'var(--success)' : (p.score >= 50 ? 'var(--warning)' : 'var(--danger)');
        html += '<div class="flex justify-between items-center p-2" style="border-bottom:1px solid var(--border-light)"><div><div class="text-sm">' + SS.utils.esc(p.name) + '</div>'
          + '<div class="text-xs text-muted">' + p.load_ms + 'ms · ' + p.size_kb + 'KB</div></div>'
          + '<div class="font-bold" style="color:' + color + '">' + p.score + '</div></div>';
      }
      SS.ui.sheet({title: '⚡ Page Speed', html: html});
    });
  }
};
