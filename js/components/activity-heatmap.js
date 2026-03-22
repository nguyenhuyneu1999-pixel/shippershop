window.SS = window.SS || {};
SS.ActivityHeatmap = {
  show: function(days) {
    days = days || 30;
    SS.api.get('/activity-heatmap.php?days=' + days).then(function(d) {
      var data = d.data || {};
      var grid = data.grid || [];
      var maxVal = data.max_value || 1;
      var html = '<div class="flex gap-2 mb-3">';
      [7, 30, 90].forEach(function(dd) { html += '<div class="chip ' + (dd === days ? 'chip-active' : '') + '" onclick="SS.ActivityHeatmap.show(' + dd + ')" style="cursor:pointer">' + dd + 'd</div>'; });
      html += '</div>';
      // Heatmap grid
      html += '<div style="overflow-x:auto"><table style="width:100%;border-spacing:2px;font-size:9px"><tr><td></td>';
      for (var h = 0; h < 24; h += 2) html += '<td style="text-align:center;color:var(--text-muted)">' + h + '</td>';
      html += '</tr>';
      for (var i = 0; i < grid.length; i++) {
        html += '<tr><td style="font-weight:600;padding-right:4px;white-space:nowrap">' + grid[i].day + '</td>';
        for (var j = 0; j < 24; j += 2) {
          var val = (grid[i].hours[j] || 0) + (grid[i].hours[j + 1] || 0);
          var intensity = maxVal > 0 ? Math.min(1, val / maxVal) : 0;
          var bg = intensity === 0 ? 'var(--border-light)' : 'rgba(124,58,237,' + (0.15 + intensity * 0.85) + ')';
          html += '<td style="width:20px;height:18px;background:' + bg + ';border-radius:3px" title="' + grid[i].day + ' ' + j + '-' + (j + 2) + 'h: ' + val + '"></td>';
        }
        html += '</tr>';
      }
      html += '</table></div>';
      // Peak times
      var peaks = data.peak_times || [];
      if (peaks.length) {
        html += '<div class="text-sm font-bold mb-1 mt-3">Peak</div>';
        for (var p = 0; p < Math.min(peaks.length, 3); p++) html += '<div class="text-xs p-1">' + peaks[p].day + ' ' + peaks[p].hour + 'h — ' + peaks[p].posts + ' bai</div>';
      }
      SS.ui.sheet({title: '🗓️ Activity Heatmap', html: html});
    });
  }
};
