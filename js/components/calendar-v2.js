/**
 * ShipperShop Component — Content Calendar V2
 */
window.SS = window.SS || {};

SS.CalendarV2 = {
  show: function(month, year) {
    month = month || new Date().getMonth() + 1;
    year = year || new Date().getFullYear();
    SS.api.get('/calendar-v2.php?month=' + month + '&year=' + year).then(function(d) {
      var data = d.data || {};
      var cal = data.calendar || [];
      var stats = data.stats || {};
      var months = ['','Th1','Th2','Th3','Th4','Th5','Th6','Th7','Th8','Th9','Th10','Th11','Th12'];

      var html = '<div class="flex justify-between items-center mb-3">'
        + '<button class="btn btn-ghost btn-sm" onclick="SS.CalendarV2.show(' + (month === 1 ? 12 : month - 1) + ',' + (month === 1 ? year - 1 : year) + ')">←</button>'
        + '<span class="font-bold">' + months[month] + ' ' + year + '</span>'
        + '<button class="btn btn-ghost btn-sm" onclick="SS.CalendarV2.show(' + (month === 12 ? 1 : month + 1) + ',' + (month === 12 ? year + 1 : year) + ')">→</button></div>';

      // Stats
      html += '<div class="flex gap-2 mb-3 text-center" style="font-size:11px">'
        + '<div class="card" style="padding:6px;flex:1"><div class="font-bold">' + (stats.total_posts || 0) + '</div><div class="text-muted">Bai</div></div>'
        + '<div class="card" style="padding:6px;flex:1"><div class="font-bold">' + (stats.active_days || 0) + '</div><div class="text-muted">Ngay</div></div>'
        + '<div class="card" style="padding:6px;flex:1"><div class="font-bold">🔥' + (stats.max_streak || 0) + '</div><div class="text-muted">Streak</div></div>'
        + '<div class="card" style="padding:6px;flex:1"><div class="font-bold">⏰' + (stats.scheduled_count || 0) + '</div><div class="text-muted">Hen</div></div></div>';

      // Grid
      var days = ['T2','T3','T4','T5','T6','T7','CN'];
      html += '<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;font-size:11px">';
      for (var di = 0; di < 7; di++) html += '<div class="text-center text-muted" style="padding:3px">' + days[di] + '</div>';
      var firstDow = cal.length ? new Date(cal[0].date).getDay() : 1;
      firstDow = firstDow === 0 ? 7 : firstDow;
      for (var e = 1; e < firstDow; e++) html += '<div></div>';
      for (var i = 0; i < cal.length; i++) {
        var c = cal[i];
        var bg = c.posts > 0 ? 'var(--primary)' : (c.scheduled > 0 ? 'var(--warning)' : 'transparent');
        var color = c.posts > 0 || c.scheduled > 0 ? '#fff' : 'var(--text)';
        var today = c.date === new Date().toISOString().split('T')[0];
        html += '<div style="aspect-ratio:1;display:flex;align-items:center;justify-content:center;border-radius:6px;background:' + bg + ';color:' + color + ';font-weight:' + (today ? '800' : '400') + (today ? ';box-shadow:0 0 0 2px var(--primary)' : '') + '" title="' + c.posts + ' bai, ' + c.scheduled + ' hen">' + c.day + '</div>';
      }
      html += '</div>';
      SS.ui.sheet({title: '📅 Lich v2', html: html});
    });
  }
};
