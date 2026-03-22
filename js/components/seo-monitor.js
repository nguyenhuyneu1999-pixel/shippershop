/**
 * ShipperShop Component — SEO Monitor (Admin)
 */
window.SS = window.SS || {};

SS.SeoMonitor = {
  show: function() {
    SS.api.get('/seo-monitor.php').then(function(d) {
      var data = d.data || {};
      var pages = data.pages || [];
      var html = '<div class="flex gap-2 mb-3 text-center"><div class="card" style="padding:10px;flex:1"><div class="font-bold text-lg" style="color:var(--primary)">' + (data.avg_score || 0) + '</div><div class="text-xs text-muted">SEO Score</div></div>'
        + '<div class="card" style="padding:10px;flex:1"><div class="font-bold text-lg">' + (data.indexable || 0) + '</div><div class="text-xs text-muted">Indexable</div></div>'
        + '<div class="card" style="padding:10px;flex:1"><div class="font-bold text-lg" style="color:var(--warning)">' + (data.issue_count || 0) + '</div><div class="text-xs text-muted">Issues</div></div></div>';
      for (var i = 0; i < pages.length; i++) {
        var p = pages[i];
        var color = p.score >= 80 ? 'var(--success)' : (p.score >= 50 ? 'var(--primary)' : 'var(--warning)');
        html += '<div class="flex justify-between items-center p-2" style="border-bottom:1px solid var(--border-light)"><div><div class="text-sm">' + SS.utils.esc(p.title) + '</div><div class="text-xs text-muted">' + SS.utils.esc(p.url) + '</div></div>'
          + '<span class="font-bold text-sm" style="color:' + color + '">' + p.score + '</span></div>';
      }
      if ((data.issues || []).length) {
        html += '<div class="text-sm font-bold mt-3 mb-1" style="color:var(--warning)">Van de</div>';
        for (var j = 0; j < data.issues.length; j++) html += '<div class="text-xs text-muted">⚠️ ' + SS.utils.esc(data.issues[j]) + '</div>';
      }
      SS.ui.sheet({title: '🔍 SEO Monitor', html: html});
    });
  }
};
