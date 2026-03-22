/**
 * ShipperShop Component — Engagement Heatmap
 * Hour × Day-of-week engagement visualization
 */
window.SS = window.SS || {};

SS.EngagementHeatmap = {
  show: function(userId) {
    var url = '/engagement-heatmap.php?days=30' + (userId ? '&user_id=' + userId : '');
    SS.api.get(url).then(function(d) {
      var data = d.data || {};
      var best = data.best_slots || [];
      var maxEng = data.max_engagement || 1;

      var html = '<div class="text-sm font-bold mb-2">Thoi diem tuong tac cao nhat</div>';
      if (best.length) {
        html += '<div class="flex gap-2 mb-3" style="overflow-x:auto">';
        for (var i = 0; i < best.length; i++) {
          var b = best[i];
          var intensity = Math.min(1, parseFloat(b.avg_eng) / maxEng);
          var bg = 'rgba(124,58,237,' + (0.2 + intensity * 0.8).toFixed(2) + ')';
          html += '<div style="min-width:60px;padding:8px;border-radius:8px;background:' + bg + ';color:' + (intensity > 0.5 ? '#fff' : 'var(--text)') + ';text-align:center">'
            + '<div class="font-bold text-sm">' + b.day_name + '</div>'
            + '<div style="font-size:11px">' + b.h + ':00</div>'
            + '<div class="text-xs">' + b.avg_eng + '</div></div>';
        }
        html += '</div>';
      }

      // Grid visualization (simplified)
      var grid = data.grid || [];
      if (grid.length) {
        var days = ['', 'CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
        html += '<div class="text-sm font-bold mb-2">Bieu do nhiet</div>';
        html += '<div style="display:grid;grid-template-columns:auto repeat(7,1fr);gap:1px;font-size:10px">';
        html += '<div></div>';
        for (var dw = 1; dw <= 7; dw++) html += '<div class="text-center text-muted">' + days[dw] + '</div>';

        for (var h = 6; h <= 23; h++) {
          html += '<div class="text-muted text-right" style="padding-right:4px">' + h + 'h</div>';
          for (var dd = 1; dd <= 7; dd++) {
            var cell = null;
            for (var g = 0; g < grid.length; g++) {
              if (grid[g].dow === dd && grid[g].hour === h) { cell = grid[g]; break; }
            }
            var val = cell ? cell.avg_engagement : 0;
            var op = maxEng > 0 ? (0.1 + (val / maxEng) * 0.9).toFixed(2) : '0.1';
            html += '<div style="width:100%;aspect-ratio:1;background:rgba(124,58,237,' + op + ');border-radius:2px" title="' + days[dd] + ' ' + h + ':00 — ' + val + '"></div>';
          }
        }
        html += '</div>';
      }

      SS.ui.sheet({title: 'Heatmap tuong tac', html: html});
    });
  }
};
