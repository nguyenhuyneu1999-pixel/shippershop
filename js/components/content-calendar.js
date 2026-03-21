/**
 * ShipperShop Component — Content Calendar
 * Monthly calendar with posts, scheduled, drafts
 */
window.SS = window.SS || {};

SS.ContentCalendar = {
  _month: null,

  show: function(month) {
    month = month || new Date().toISOString().substring(0, 7);
    SS.ContentCalendar._month = month;

    SS.api.get('/content-calendar.php?month=' + month).then(function(d) {
      var data = d.data || {};
      var cal = data.calendar || {};
      var stats = data.stats || {};
      var start = new Date(data.start_date + 'T00:00:00');
      var end = new Date(data.end_date + 'T00:00:00');
      var monthNames = ['Thang 1','Thang 2','Thang 3','Thang 4','Thang 5','Thang 6','Thang 7','Thang 8','Thang 9','Thang 10','Thang 11','Thang 12'];
      var yearMonth = month.split('-');

      // Navigation
      var prevMonth = new Date(start); prevMonth.setMonth(prevMonth.getMonth() - 1);
      var nextMonth = new Date(start); nextMonth.setMonth(nextMonth.getMonth() + 1);
      var prevStr = prevMonth.toISOString().substring(0, 7);
      var nextStr = nextMonth.toISOString().substring(0, 7);

      var html = '<div class="flex justify-between items-center mb-3">'
        + '<button class="btn btn-ghost btn-sm" onclick="SS.ContentCalendar.show(\'' + prevStr + '\')"><i class="fa-solid fa-chevron-left"></i></button>'
        + '<div class="font-bold">' + monthNames[parseInt(yearMonth[1]) - 1] + ' ' + yearMonth[0] + '</div>'
        + '<button class="btn btn-ghost btn-sm" onclick="SS.ContentCalendar.show(\'' + nextStr + '\')"><i class="fa-solid fa-chevron-right"></i></button></div>';

      // Stats
      html += '<div class="flex gap-2 mb-3 text-center">'
        + '<div class="chip">📝 ' + (stats.posts || 0) + '</div>'
        + '<div class="chip">⏰ ' + (stats.scheduled || 0) + '</div>'
        + '<div class="chip">📋 ' + (stats.drafts || 0) + '</div>'
        + '<div class="chip">🔥 ' + (stats.days_active || 0) + ' ngay</div></div>';

      // Calendar grid
      html += '<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;font-size:11px;text-align:center">';
      var dayNames = ['CN','T2','T3','T4','T5','T6','T7'];
      for (var h = 0; h < 7; h++) html += '<div class="text-xs text-muted font-bold" style="padding:4px">' + dayNames[h] + '</div>';

      // Empty cells before first day
      var firstDow = start.getDay();
      for (var e = 0; e < firstDow; e++) html += '<div></div>';

      // Day cells
      var current = new Date(start);
      while (current <= end) {
        var dateStr = current.toISOString().substring(0, 10);
        var items = cal[dateStr] || [];
        var isToday = dateStr === new Date().toISOString().substring(0, 10);
        var bg = items.length ? 'var(--primary-light)' : 'transparent';
        var border = isToday ? '2px solid var(--primary)' : '1px solid var(--border-light)';
        html += '<div style="padding:4px;border-radius:6px;background:' + bg + ';border:' + border + ';min-height:32px;cursor:' + (items.length ? 'pointer' : 'default') + '">'
          + '<div style="font-weight:' + (isToday ? '700' : '400') + '">' + current.getDate() + '</div>';
        if (items.length) {
          var dots = '';
          for (var di = 0; di < Math.min(items.length, 3); di++) {
            var dotColor = items[di].type === 'published' ? 'var(--success)' : (items[di].type === 'scheduled' ? 'var(--primary)' : 'var(--warning)');
            dots += '<span style="width:4px;height:4px;border-radius:50%;background:' + dotColor + ';display:inline-block"></span>';
          }
          html += '<div style="display:flex;gap:1px;justify-content:center;margin-top:1px">' + dots + '</div>';
        }
        html += '</div>';
        current.setDate(current.getDate() + 1);
      }
      html += '</div>';

      // Legend
      html += '<div class="flex gap-3 justify-center mt-3 text-xs text-muted">'
        + '<span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--success)"></span> Da dang</span>'
        + '<span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--primary)"></span> Hen gio</span>'
        + '<span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--warning)"></span> Nhap</span></div>';

      SS.ui.sheet({title: 'Lich noi dung', html: html});
    });
  }
};
