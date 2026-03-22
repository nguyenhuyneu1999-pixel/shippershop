window.SS = window.SS || {};
SS.ContentCalendarV2 = {
  show: function(month) {
    month = month || new Date().toISOString().substring(0, 7);
    SS.api.get('/content-calendar-v2.php?month=' + month).then(function(d) {
      var data = d.data || {};
      var cal = data.calendar || [];
      var html = '<div class="flex justify-between items-center mb-3"><button class="btn btn-ghost btn-sm" onclick="SS.ContentCalendarV2.show(\'' + SS.ContentCalendarV2._prevMonth(month) + '\')">◀</button><span class="font-bold">' + SS.utils.esc(month) + '</span><button class="btn btn-ghost btn-sm" onclick="SS.ContentCalendarV2.show(\'' + SS.ContentCalendarV2._nextMonth(month) + '\')">▶</button></div>';
      html += '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:6px;margin-bottom:12px;text-align:center;font-size:11px">'
        + '<div class="card" style="padding:6px"><div class="font-bold">' + (data.total_posts || 0) + '</div><div class="text-muted">Bai</div></div>'
        + '<div class="card" style="padding:6px"><div class="font-bold" style="color:' + ((data.consistency || 0) >= 70 ? 'var(--success)' : 'var(--warning)') + '">' + (data.consistency || 0) + '%</div><div class="text-muted">Deu dan</div></div></div>';
      // Calendar grid
      var dayNames = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
      html += '<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;text-align:center;font-size:10px">';
      for (var h = 0; h < 7; h++) html += '<div class="text-muted font-bold">' + dayNames[h] + '</div>';
      // Padding for first day
      var firstDow = data.first_dow || 1;
      for (var p = 1; p < firstDow; p++) html += '<div></div>';
      for (var i = 0; i < cal.length; i++) {
        var c = cal[i];
        var bg = c.is_today ? 'var(--primary)' : (c.posts > 0 ? 'var(--success)' : (c.has_gap ? '#ef444430' : 'var(--border-light)'));
        var color = c.is_today ? '#fff' : (c.posts > 0 ? '#fff' : 'var(--text-muted)');
        html += '<div style="width:100%;aspect-ratio:1;border-radius:6px;background:' + bg + ';color:' + color + ';display:flex;flex-direction:column;align-items:center;justify-content:center;font-size:10px"><div>' + c.day + '</div>' + (c.posts > 0 ? '<div style="font-size:8px">' + c.posts + '</div>' : '') + '</div>';
      }
      html += '</div>';
      if (data.best_day) html += '<div class="text-xs text-muted mt-2">🏆 Best: ' + SS.utils.esc(data.best_day) + ' (' + (data.best_engagement || 0) + ' eng)</div>';
      SS.ui.sheet({title: '📅 Content Calendar', html: html});
    });
  },
  _prevMonth: function(m) { var d = new Date(m + '-15'); d.setMonth(d.getMonth() - 1); return d.toISOString().substring(0, 7); },
  _nextMonth: function(m) { var d = new Date(m + '-15'); d.setMonth(d.getMonth() + 1); return d.toISOString().substring(0, 7); }
};
