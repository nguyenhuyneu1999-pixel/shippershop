/**
 * ShipperShop Component — Database Health (Admin)
 */
window.SS = window.SS || {};

SS.DbHealth = {
  show: function() {
    SS.api.get('/db-health.php').then(function(d) {
      var data = d.data || {};
      var gradeColor = data.grade === 'A' ? 'var(--success)' : (data.grade === 'B' ? 'var(--primary)' : 'var(--warning)');
      var html = '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin-bottom:16px;text-align:center;font-size:11px">'
        + '<div class="card" style="padding:8px"><div class="font-bold text-lg" style="color:' + gradeColor + '">' + (data.grade || '?') + '</div><div class="text-muted">Grade</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (data.total_mb || 0) + '</div><div class="text-muted">MB</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + (data.total_tables || 0) + '</div><div class="text-muted">Tables</div></div>'
        + '<div class="card" style="padding:8px"><div class="font-bold">' + SS.utils.fN(data.total_rows || 0) + '</div><div class="text-muted">Rows</div></div></div>';

      // Top tables
      var tables = data.tables || [];
      if (tables.length) {
        var maxS = parseFloat(tables[0].total_kb) || 1;
        html += '<div class="text-sm font-bold mb-1">Top tables</div>';
        for (var i = 0; i < Math.min(tables.length, 8); i++) {
          var t = tables[i];
          var w = Math.max(5, Math.round(parseFloat(t.total_kb) / maxS * 100));
          html += '<div class="mb-1"><div class="flex justify-between text-xs"><span>' + SS.utils.esc(t.name) + ' (' + SS.utils.fN(parseInt(t.rows || 0)) + ')</span><span class="font-bold">' + t.total_kb + ' KB</span></div>'
            + '<div style="height:6px;background:var(--border-light);border-radius:3px"><div style="width:' + w + '%;height:100%;background:var(--primary);border-radius:3px"></div></div></div>';
        }
      }

      // Fragmented
      var frag = data.fragmented || [];
      if (frag.length) {
        html += '<div class="text-sm font-bold mt-3 mb-1" style="color:var(--warning)">Phan manh</div>';
        for (var j = 0; j < frag.length; j++) html += '<div class="text-xs">' + SS.utils.esc(frag[j].name) + ': ' + frag[j].free_kb + ' KB free</div>';
      }

      SS.ui.sheet({title: '💾 DB Health', html: html});
    });
  }
};
