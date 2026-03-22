/**
 * ShipperShop Component — Calendar View
 * Monthly content calendar grid
 */
window.SS = window.SS || {};

SS.CalendarView = {
  show: function(month, year, userId) {
    month = month || new Date().getMonth() + 1;
    year = year || new Date().getFullYear();
    var url = '/calendar-view.php?month=' + month + '&year=' + year + (userId ? '&user_id=' + userId : '');
    SS.api.get(url).then(function(d) {
      var data = d.data || {};
      var cal = data.calendar || [];
      var maxP = 1; for (var c = 0; c < cal.length; c++) { if (cal[c].posts > maxP) maxP = cal[c].posts; }

      var months = ['','Thang 1','Thang 2','Thang 3','Thang 4','Thang 5','Thang 6','Thang 7','Thang 8','Thang 9','Thang 10','Thang 11','Thang 12'];
      var html = '<div class="flex justify-between items-center mb-3">'
        + '<button class="btn btn-ghost btn-sm" onclick="SS.CalendarView.show(' + (month === 1 ? 12 : month - 1) + ',' + (month === 1 ? year - 1 : year) + ')">←</button>'
        + '<span class="font-bold">' + months[month] + ' ' + year + '</span>'
        + '<button class="btn btn-ghost btn-sm" onclick="SS.CalendarView.show(' + (month === 12 ? 1 : month + 1) + ',' + (month === 12 ? year + 1 : year) + ')">→</button></div>';

      // Stats
      html += '<div class="flex gap-2 mb-3 text-center"><div class="card" style="padding:6px;flex:1"><div class="font-bold" style="color:var(--primary)">' + (data.total_posts || 0) + '</div><div class="text-xs text-muted">Bai</div></div>'
        + '<div class="card" style="padding:6px;flex:1"><div class="font-bold" style="color:var(--success)">' + (data.active_days || 0) + '</div><div class="text-xs text-muted">Ngay</div></div>'
        + '<div class="card" style="padding:6px;flex:1"><div class="font-bold">' + (data.total_likes || 0) + '</div><div class="text-xs text-muted">Likes</div></div></div>';

      // Grid
      var days = ['T2','T3','T4','T5','T6','T7','CN'];
      html += '<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;font-size:11px">';
      for (var di = 0; di < days.length; di++) html += '<div class="text-center text-muted" style="padding:4px">' + days[di] + '</div>';

      // Empty cells before first day
      var firstDow = data.first_dow || 1;
      for (var e = 1; e < firstDow; e++) html += '<div></div>';

      for (var i = 0; i < cal.length; i++) {
        var day = cal[i];
        var opacity = day.posts > 0 ? (0.3 + 0.7 * day.posts / maxP).toFixed(2) : '0';
        var isToday = day.date === new Date().toISOString().split('T')[0];
        html += '<div style="aspect-ratio:1;display:flex;align-items:center;justify-content:center;border-radius:6px;background:rgba(124,58,237,' + opacity + ');color:' + (day.posts > 0 ? '#fff' : 'var(--text)') + ';font-weight:' + (isToday ? '800' : '400') + (isToday ? ';border:2px solid var(--primary)' : '') + '" title="' + day.date + ': ' + day.posts + ' bai">' + day.day + '</div>';
      }
      html += '</div>';

      SS.ui.sheet({title: 'Lich noi dung', html: html});
    });
  }
};
