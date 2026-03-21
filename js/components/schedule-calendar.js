/**
 * ShipperShop Component — Schedule Calendar
 * Monthly grid view of published + scheduled posts
 * Uses: SS.api, SS.ui
 */
window.SS = window.SS || {};

SS.ScheduleCalendar = {

  _month: new Date().getMonth() + 1,
  _year: new Date().getFullYear(),

  show: function() {
    SS.ScheduleCalendar._load();
  },

  _load: function() {
    var m = SS.ScheduleCalendar._month;
    var y = SS.ScheduleCalendar._year;
    var months = ['','Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6','Tháng 7','Tháng 8','Tháng 9','Tháng 10','Tháng 11','Tháng 12'];

    SS.api.get('/schedule-calendar.php?month=' + m + '&year=' + y).then(function(d) {
      var data = d.data || {};
      var days = data.days || {};
      var dim = data.days_in_month || 30;
      var firstDay = new Date(y, m - 1, 1).getDay(); // 0=Sun

      var html = '<div class="flex justify-between items-center mb-3">'
        + '<button class="btn btn-ghost btn-sm" onclick="SS.ScheduleCalendar._prev()"><i class="fa-solid fa-chevron-left"></i></button>'
        + '<span class="font-bold">' + months[m] + ' ' + y + '</span>'
        + '<button class="btn btn-ghost btn-sm" onclick="SS.ScheduleCalendar._next()"><i class="fa-solid fa-chevron-right"></i></button></div>';

      // Stats
      html += '<div class="flex gap-3 mb-3 text-center text-xs">'
        + '<div class="flex-1 card" style="padding:8px"><div class="font-bold" style="color:var(--success)">' + (data.total_published || 0) + '</div>Đã đăng</div>'
        + '<div class="flex-1 card" style="padding:8px"><div class="font-bold" style="color:var(--primary)">' + (data.total_scheduled || 0) + '</div>Đang chờ</div></div>';

      // Day headers
      html += '<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;text-align:center">';
      var dayNames = ['CN','T2','T3','T4','T5','T6','T7'];
      for (var h = 0; h < 7; h++) {
        html += '<div class="text-xs text-muted font-bold" style="padding:4px">' + dayNames[h] + '</div>';
      }

      // Empty cells before month starts
      for (var e = 0; e < firstDay; e++) {
        html += '<div></div>';
      }

      // Day cells
      var today = new Date();
      var isCurrentMonth = m === (today.getMonth() + 1) && y === today.getFullYear();

      for (var day = 1; day <= dim; day++) {
        var info = days[day] || {published: [], scheduled: []};
        var pub = info.published.length;
        var sched = info.scheduled.length;
        var isToday = isCurrentMonth && day === today.getDate();

        var bg = isToday ? 'var(--primary)' : (pub ? 'var(--success-light)' : (sched ? 'var(--primary-light)' : 'var(--card)'));
        var textColor = isToday ? '#fff' : 'var(--text)';

        html += '<div style="padding:4px;text-align:center;border-radius:8px;background:' + bg + ';color:' + textColor + ';font-size:12px;min-height:36px;cursor:' + ((pub + sched) ? 'pointer' : 'default') + '"'
          + ((pub + sched) ? ' onclick="SS.ScheduleCalendar._showDay(' + day + ',' + m + ',' + y + ')"' : '') + '>'
          + '<div style="font-weight:600">' + day + '</div>';
        if (pub) html += '<div style="width:6px;height:6px;border-radius:50%;background:var(--success);margin:1px auto"></div>';
        if (sched) html += '<div style="width:6px;height:6px;border-radius:50%;background:var(--primary);margin:1px auto"></div>';
        html += '</div>';
      }
      html += '</div>';

      SS.ui.sheet({title: 'Lịch đăng bài', html: html});
    });
  },

  _prev: function() {
    SS.ScheduleCalendar._month--;
    if (SS.ScheduleCalendar._month < 1) { SS.ScheduleCalendar._month = 12; SS.ScheduleCalendar._year--; }
    SS.ui.closeSheet();
    SS.ScheduleCalendar._load();
  },

  _next: function() {
    SS.ScheduleCalendar._month++;
    if (SS.ScheduleCalendar._month > 12) { SS.ScheduleCalendar._month = 1; SS.ScheduleCalendar._year++; }
    SS.ui.closeSheet();
    SS.ScheduleCalendar._load();
  },

  _showDay: function(day, month, year) {
    SS.api.get('/schedule-calendar.php?month=' + month + '&year=' + year).then(function(d) {
      var info = (d.data || {}).days || {};
      var dayData = info[day] || {published: [], scheduled: []};
      var html = '';
      if (dayData.published.length) {
        html += '<div class="text-sm font-bold mb-2">Đã đăng</div>';
        for (var i = 0; i < dayData.published.length; i++) {
          var p = dayData.published[i];
          html += '<div class="card mb-2" style="padding:8px;cursor:pointer" onclick="window.location.href=\'/post-detail.html?id=' + p.id + '\'">'
            + '<div class="text-xs text-muted">' + p.time + '</div>'
            + '<div class="text-sm">' + SS.utils.esc(p.preview) + '</div></div>';
        }
      }
      if (dayData.scheduled.length) {
        html += '<div class="text-sm font-bold mb-2 mt-3">Đang chờ</div>';
        for (var j = 0; j < dayData.scheduled.length; j++) {
          var s = dayData.scheduled[j];
          html += '<div class="card mb-2" style="padding:8px;border-left:3px solid var(--primary)">'
            + '<div class="text-xs text-muted">' + s.time + ' · ' + s.status + '</div>'
            + '<div class="text-sm">' + SS.utils.esc(s.preview) + '</div></div>';
        }
      }
      if (!html) html = '<div class="text-center text-muted p-3">Không có bài viết</div>';
      SS.ui.sheet({title: 'Ngày ' + day + '/' + month, html: html});
    });
  }
};
